<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Task to expire old subscriptions and move users to free preview.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_chapa_subscription\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task to expire subscriptions.
 */
class expire_subscriptions extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_expire_subscriptions', 'local_chapa_subscription');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB, $CFG;

        // Get settings
        $settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
        $config = array();
        foreach ($settings as $setting) {
            $config[$setting->name] = $setting->value;
        }

        // Get subscriptions that reached endtime and should expire/switch
        $expired_subscriptions = $DB->get_records_sql(
            "SELECT s.*, p.fullname as plan_name, u.firstname, u.lastname, u.email
             FROM {local_chapa_subscriptions} s
             JOIN {local_chapa_plans} p ON s.planid = p.id
             JOIN {user} u ON s.userid = u.id
             WHERE s.endtime < ?",
            array(time())
        );

        $expired_count = 0;
        $downgraded_count = 0;
        foreach ($expired_subscriptions as $subscription) {
            // If auto_renew = 1 keep active (renewal handled by payment webhook). If auto_renew = 0 -> expire.
            if (empty($subscription->auto_renew)) {
                $subscription->status = 'expired';
                $subscription->timemodified = time();
                $DB->update_record('local_chapa_subscriptions', $subscription);
            }

            // Apply scheduled downgrades when endtime passed
            $downgrade = $DB->get_record('local_chapa_downgrade_requests', array(
                'userid' => $subscription->userid,
                'current_plan_id' => $subscription->planid,
                'scheduled_for' => $subscription->endtime,
                'status' => 'pending'
            ));
            if ($downgrade) {
                $subscription->planid = $downgrade->target_plan_id;
                $subscription->status = 'active';
                $subscription->timemodified = time();
                $DB->update_record('local_chapa_subscriptions', $subscription);
                // Update cohorts to new plan
                local_chapa_subscription_handle_cohort_transition($subscription->userid, $subscription->planid, 'cron_downgrade');
                // Mark request executed
                $downgrade->status = 'executed';
                $downgrade->executed_at = time();
                $DB->update_record('local_chapa_downgrade_requests', $downgrade);
                $downgraded_count++;
                continue;
            }

            // If expired (non-renewing), move cohorts accordingly
            if ($subscription->status === 'expired') {
                $cohort_id = null;
                switch ($subscription->planid) {
                    case 1: $cohort_id = $config['basic_cohort']; break;
                    case 2: $cohort_id = $config['standard_cohort']; break;
                    case 3: $cohort_id = $config['premium_cohort']; break;
                }
                if ($cohort_id) {
                    $DB->delete_records('cohort_members', array('cohortid' => $cohort_id, 'userid' => $subscription->userid));
                }
                $free_preview_cohort = $config['free_preview_cohort'];
                if ($free_preview_cohort) {
                    $existing_member = $DB->get_record('cohort_members', array('cohortid' => $free_preview_cohort, 'userid' => $subscription->userid));
                    if (!$existing_member) {
                        $cohort_member = new \stdClass();
                        $cohort_member->cohortid = $free_preview_cohort;
                        $cohort_member->userid = $subscription->userid;
                        $cohort_member->timeadded = time();
                        $DB->insert_record('cohort_members', $cohort_member);
                    }
                }
            }

            // Send expiration notification email
            if (!empty($config['subscription_expired_template'])) {
                $template = $config['subscription_expired_template'];
                $message = str_replace(
                    array('{firstname}', '{lastname}', '{plan}', '{enddate}', '{site}'),
                    array(
                        $subscription->firstname,
                        $subscription->lastname,
                        $subscription->plan_name,
                        date('Y-m-d', $subscription->endtime),
                        $CFG->fullname
                    ),
                    $template
                );

                $subject = get_string('subscription_expired', 'local_chapa_subscription');
                $user = new \stdClass();
                $user->id = $subscription->userid;
                $user->email = $subscription->email;
                $user->firstname = $subscription->firstname;
                $user->lastname = $subscription->lastname;

                email_to_user($user, $CFG->noreplyaddress, $subject, $message);
            }

            $expired_count++;
        }

        mtrace("Expired {$expired_count} subscriptions; applied {$downgraded_count} scheduled downgrades");
    }
}
