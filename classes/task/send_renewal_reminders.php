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
 * Task to send renewal reminders to users.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_chapa_subscription\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task to send renewal reminders.
 */
class send_renewal_reminders extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_send_renewal_reminders', 'local_chapa_subscription');
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

        // Check if renewal reminders are enabled
        if (empty($config['renewal_reminder_template'])) {
            return;
        }

        // Get subscriptions expiring in 7 days
        $reminder_time = time() + (7 * 24 * 60 * 60); // 7 days from now
        $expiring_subscriptions = $DB->get_records_sql(
            "SELECT s.*, p.fullname AS plan_name, p.monthlyprice, u.firstname, u.lastname, u.email
               FROM {local_chapa_subscriptions} s
               JOIN {local_chapa_plans} p ON s.planid = p.id
               JOIN {user} u ON s.userid = u.id
              WHERE s.status = 'active' 
                AND s.endtime BETWEEN ? AND ?
                AND s.auto_renew = 1",
            array($reminder_time - 3600, $reminder_time + 3600) // 1 hour window
        );

        $sent_count = 0;
        foreach ($expiring_subscriptions as $subscription) {
            // Check if we already sent a reminder for this subscription
            $existing_reminder = $DB->get_record('local_chapa_reminders', array(
                'subscriptionid' => $subscription->id,
                'type' => 'renewal_reminder'
            ));

            if (!$existing_reminder) {
                // Send reminder email
                $template = $config['renewal_reminder_template'];
                $amount = isset($subscription->monthlyprice) ? ($subscription->monthlyprice / 100) : 0;
                $currency = 'ETB';
                $message = str_replace(
                    array('{firstname}', '{lastname}', '{plan}', '{enddate}', '{site}', '{amount}', '{currency}'),
                    array(
                        $subscription->firstname,
                        $subscription->lastname,
                        $subscription->plan_name,
                        date('Y-m-d', $subscription->endtime),
                        $CFG->fullname,
                        number_format($amount, 2),
                        $currency
                    ),
                    $template
                );

                $subject = get_string('renewal_reminder', 'local_chapa_subscription');
                $user = new \stdClass();
                $user->id = $subscription->userid;
                $user->email = $subscription->email;
                $user->firstname = $subscription->firstname;
                $user->lastname = $subscription->lastname;

                $from = \core_user::get_noreply_user();
                if (email_to_user($user, $from, $subject, $message)) {
                    // Record that we sent the reminder
                    $reminder = new \stdClass();
                    $reminder->subscriptionid = $subscription->id;
                    $reminder->type = 'renewal_reminder';
                    $reminder->sent_at = time();
                    $reminder->timecreated = time();
                    $DB->insert_record('local_chapa_reminders', $reminder);
                    
                    $sent_count++;
                }
            }
        }

        mtrace("Sent {$sent_count} renewal reminders");
    }
}
