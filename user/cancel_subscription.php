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
 * User cancel subscription page for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_login();

$subscription_id = required_param('subscription_id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_url('/local/chapa_subscription/user/cancel_subscription.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Cancel Subscription');
$PAGE->set_heading('Cancel Subscription');

// Navigation
$PAGE->navbar->add(get_string('pluginname', 'local_chapa_subscription'));
$PAGE->navbar->add('Manage Subscription');
$PAGE->navbar->add('Cancel Subscription');

// Get subscription details
$subscription = $DB->get_record('local_chapa_subscriptions', array('id' => $subscription_id));
if (!$subscription || $subscription->userid != $USER->id) {
    throw new moodle_exception('invalid_subscription', 'local_chapa_subscription');
}

$plan = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid));

// Handle cancellation
if ($confirm && confirm_sesskey()) {
    // Update subscription status
    $subscription->status = 'cancelled';
    $subscription->cancelled_at = time();
    $subscription->timemodified = time();
    $DB->update_record('local_chapa_subscriptions', $subscription);
    
    // Create cancellation record
    $cancellation = new stdClass();
    $cancellation->subscription_id = $subscription->id;
    $cancellation->userid = $USER->id;
    $cancellation->cancelled_at = time();
    $cancellation->reason = 'User requested cancellation';
    $cancellation->status = 'cancelled';
    $DB->insert_record('local_chapa_cancellations', $cancellation);
    
    // Log subscription activity
    $current_plan = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid));
    $log_entry = new stdClass();
    $log_entry->userid = $USER->id;
    $log_entry->subscription_id = $subscription->id;
    $log_entry->action = 'cancel';
    $log_entry->from_plan_id = $subscription->planid;
    $log_entry->to_plan_id = null;
    $log_entry->amount = 0;
    $log_entry->currency = 'ETB';
    $log_entry->payment_id = null;
    $log_entry->reason = 'User requested cancellation';
    $log_entry->created_at = time();
    $DB->insert_record('local_chapa_subscription_logs', $log_entry);
    
    // Remove user from current plan cohort and add to free preview cohort
    $current_plan_cohort = $DB->get_field('local_chapa_settings', 'value', array('name' => $current_plan->shortname . '_cohort'));
    $free_preview_cohort = $DB->get_field('local_chapa_settings', 'value', array('name' => 'free_preview_cohort'));
    
    if ($current_plan_cohort) {
        // Remove from current plan cohort
        $DB->delete_records('cohort_members', array('userid' => $USER->id, 'cohortid' => $current_plan_cohort));
    }
    
    if ($free_preview_cohort) {
        // Add to free preview cohort
        $cohort_member = new stdClass();
        $cohort_member->cohortid = $free_preview_cohort;
        $cohort_member->userid = $USER->id;
        $cohort_member->timeadded = time();
        $DB->insert_record('cohort_members', $cohort_member);
    }
    
    redirect(new moodle_url('/local/chapa_subscription/user/subscriptions.php'), 
        'Your subscription has been cancelled. You will retain access until ' . date('Y-m-d', $subscription->endtime), 
        null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

// Confirmation form
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5><i class="fa fa-exclamation-triangle"></i> Cancel Subscription</h5>';
echo '</div>';
echo '<div class="card-body">';

echo '<div class="alert alert-warning">';
echo '<h6><i class="fa fa-warning"></i> Important Information</h6>';
echo '<ul>';
echo '<li>Your subscription will remain active until <strong>' . date('Y-m-d', $subscription->endtime) . '</strong></li>';
echo '<li>You will lose access to premium features after the cancellation date</li>';
echo '<li>You can resubscribe at any time</li>';
echo '<li>No refunds will be provided for the current billing period</li>';
echo '</ul>';
echo '</div>';

echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<h6>Current Subscription Details:</h6>';
echo '<p><strong>Plan:</strong> ' . $plan->fullname . '</p>';
echo '<p><strong>Price:</strong> ' . number_format($plan->monthlyprice / 100, 2) . ' ETB/month</p>';
echo '<p><strong>Next Billing:</strong> ' . date('Y-m-d', $subscription->endtime) . '</p>';
echo '<p><strong>Auto Renewal:</strong> ' . ($subscription->auto_renew ? 'Yes' : 'No') . '</p>';
echo '</div>';
echo '<div class="col-md-6">';
echo '<h6>What happens after cancellation:</h6>';
echo '<ul class="list-unstyled">';
echo '<li><i class="fa fa-check text-success"></i> Access until ' . date('Y-m-d', $subscription->endtime) . '</li>';
echo '<li><i class="fa fa-times text-danger"></i> No more automatic billing</li>';
echo '<li><i class="fa fa-arrow-down text-warning"></i> Moved to Free Preview Cohort</li>';
echo '<li><i class="fa fa-refresh text-info"></i> Can resubscribe anytime</li>';
echo '</ul>';
echo '</div>';
echo '</div>';

echo '<div class="mt-4">';
echo '<h6>Are you sure you want to cancel your subscription?</h6>';
echo '<form method="post">';
echo '<input type="hidden" name="confirm" value="1">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
echo '<div class="d-flex gap-3">';
echo '<button type="submit" class="btn btn-danger" onclick="return confirm(\'Are you absolutely sure you want to cancel your subscription?\')">Yes, Cancel My Subscription</button>';
echo '<a href="manage_subscription.php" class="btn btn-secondary">No, Keep My Subscription</a>';
echo '</div>';
echo '</form>';
echo '</div>';

echo '</div>';
echo '</div>';

echo $OUTPUT->footer();
