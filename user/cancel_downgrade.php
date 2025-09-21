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
 * Cancel downgrade request page for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_login();

$PAGE->set_url('/local/chapa_subscription/user/cancel_downgrade.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Cancel Downgrade Request');
$PAGE->set_heading('Cancel Downgrade Request');

// Navigation
$PAGE->navbar->add(get_string('pluginname', 'local_chapa_subscription'));
$PAGE->navbar->add('Manage Subscription', new moodle_url('/local/chapa_subscription/user/manage_subscription.php'));
$PAGE->navbar->add('Cancel Downgrade');

// Get request ID
$request_id = required_param('request_id', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

// Get downgrade request
$downgrade_request = $DB->get_record('local_chapa_downgrade_requests', array(
    'id' => $request_id,
    'userid' => $USER->id,
    'status' => 'pending'
));

if (!$downgrade_request) {
    redirect(new moodle_url('/local/chapa_subscription/user/manage_subscription.php'), 
        'Downgrade request not found or already processed', 
        null, \core\output\notification::NOTIFY_ERROR);
}

// Get plan details
$target_plan = $DB->get_record('local_chapa_plans', array('id' => $downgrade_request->target_plan_id));
$current_plan = $DB->get_record('local_chapa_plans', array('id' => $downgrade_request->current_plan_id));

if (!$target_plan || !$current_plan) {
    redirect(new moodle_url('/local/chapa_subscription/user/manage_subscription.php'), 
        'Plan information not found', 
        null, \core\output\notification::NOTIFY_ERROR);
}

// Handle cancellation
if ($confirm && confirm_sesskey()) {
    // Update request status to cancelled
    $downgrade_request->status = 'cancelled';
    $downgrade_request->cancelled_at = time();
    $downgrade_request->timemodified = time();
    $DB->update_record('local_chapa_downgrade_requests', $downgrade_request);
    
    // Log the cancellation
    $log_entry = new stdClass();
    $log_entry->userid = $USER->id;
    $log_entry->action = 'cancel_downgrade_request';
    $log_entry->request_id = $request_id;
    $log_entry->from_plan_id = $current_plan->id;
    $log_entry->to_plan_id = $target_plan->id;
    $log_entry->reason = 'User cancelled downgrade request';
    $log_entry->created_at = time();
    $DB->insert_record('local_chapa_subscription_logs', $log_entry);
    
    redirect(new moodle_url('/local/chapa_subscription/user/manage_subscription.php'), 
        'Downgrade request has been cancelled successfully', 
        null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

// Confirmation form
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5><i class="fa fa-times-circle"></i> Cancel Downgrade Request</h5>';
echo '</div>';
echo '<div class="card-body">';

echo '<div class="alert alert-warning">';
echo '<h6><i class="fa fa-exclamation-triangle"></i> Confirm Cancellation</h6>';
echo '<p>Are you sure you want to cancel your downgrade request? This will keep your current plan active.</p>';
echo '</div>';

echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<h6>Current Plan (Will Remain Active):</h6>';
echo '<div class="card bg-light">';
echo '<div class="card-body">';
echo '<h6>' . $current_plan->fullname . '</h6>';
echo '<p class="mb-1"><strong>Price:</strong> ' . number_format($current_plan->monthlyprice / 100, 0) . ' ETB/month</p>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-6">';
echo '<h6>Requested Downgrade (Will Be Cancelled):</h6>';
echo '<div class="card bg-secondary text-white">';
echo '<div class="card-body">';
echo '<h6>' . $target_plan->fullname . '</h6>';
echo '<p class="mb-1"><strong>Price:</strong> ' . number_format($target_plan->monthlyprice / 100, 0) . ' ETB/month</p>';
echo '<p class="mb-0"><strong>Savings:</strong> ' . number_format(($current_plan->monthlyprice - $target_plan->monthlyprice) / 100, 0) . ' ETB/month</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="mt-4">';
echo '<h6>Downgrade Request Details:</h6>';
echo '<ul class="list-unstyled">';
echo '<li><strong>Requested on:</strong> ' . date('Y-m-d H:i', $downgrade_request->requested_at) . '</li>';
echo '<li><strong>Scheduled for:</strong> ' . date('Y-m-d H:i', $downgrade_request->scheduled_for) . '</li>';
echo '<li><strong>Status:</strong> <span class="badge badge-warning">Pending</span></li>';
echo '</ul>';
echo '</div>';

echo '<div class="mt-4">';
echo '<form method="post" action="">';
echo '<input type="hidden" name="request_id" value="' . $request_id . '">';
echo '<input type="hidden" name="confirm" value="1">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
echo '<div class="btn-group" role="group">';
echo '<button type="submit" class="btn btn-danger" onclick="return confirm(\'Are you sure you want to cancel this downgrade request?\')">';
echo '<i class="fa fa-times"></i> Cancel Downgrade Request';
echo '</button>';
echo '<a href="manage_subscription.php" class="btn btn-secondary">';
echo '<i class="fa fa-arrow-left"></i> Back to Manage Subscription';
echo '</a>';
echo '</div>';
echo '</form>';
echo '</div>';

echo '</div>';
echo '</div>';

echo $OUTPUT->footer();
