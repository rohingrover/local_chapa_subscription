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
 * Downgrade payment processing for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();

$current_subscription_id = required_param('current_subscription_id', PARAM_INT);
$target_plan_id = required_param('target_plan_id', PARAM_INT);

$PAGE->set_url('/local/chapa_subscription/downgrade_payment.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('downgrade_plan', 'local_chapa_subscription'));
$PAGE->set_heading(get_string('downgrade_plan', 'local_chapa_subscription'));

// Navigation
$PAGE->navbar->add(get_string('pluginname', 'local_chapa_subscription'));
$PAGE->navbar->add(get_string('my_subscription', 'local_chapa_subscription'));
$PAGE->navbar->add(get_string('downgrade_plan', 'local_chapa_subscription'));

// Get subscription and plan details
$current_subscription = $DB->get_record('local_chapa_subscriptions', array('id' => $current_subscription_id));
$current_plan = $DB->get_record('local_chapa_plans', array('id' => $current_subscription->planid));
$target_plan = $DB->get_record('local_chapa_plans', array('id' => $target_plan_id));

// Verify user owns the subscription
if ($current_subscription->userid != $USER->id) {
    throw new moodle_exception('invalid_subscription', 'local_chapa_subscription');
}

// Process immediate downgrade
$subscription->planid = $target_plan->id;
$subscription->timemodified = time();
$DB->update_record('local_chapa_subscriptions', $subscription);

// Handle cohort transition for downgrade
local_chapa_subscription_handle_cohort_transition($USER->id, $target_plan->id, 'downgrade');

// Create downgrade record
$downgrade_record = new stdClass();
$downgrade_record->userid = $USER->id;
$downgrade_record->subscription_id = $current_subscription_id;
$downgrade_record->from_plan_id = $current_plan->id;
$downgrade_record->to_plan_id = $target_plan->id;
$downgrade_record->downgraded_at = time();
$downgrade_record->status = 'completed';

$DB->insert_record('local_chapa_downgrades', $downgrade_record);

// Redirect to success page
redirect(new moodle_url('/local/chapa_subscription/downgrade_success.php', array(
    'subscription_id' => $current_subscription_id
)));

echo $OUTPUT->header();
echo $OUTPUT->footer();
