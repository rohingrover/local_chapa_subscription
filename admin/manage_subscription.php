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
 * Admin subscription management page for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/local/chapa_subscription/lib.php');
require_once($CFG->libdir.'/xmldb/xmldb_table.php');

// Check admin permissions
require_admin();

$subscription_id = required_param('id', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHANUMEXT);

$PAGE->set_url('/local/chapa_subscription/admin/manage_subscription.php', array('id' => $subscription_id));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Manage Subscription');
$PAGE->set_heading('Manage Subscription');

// Navigation
$PAGE->navbar->add('Site administration', new moodle_url('/admin/search.php'));
$PAGE->navbar->add('Plugins', new moodle_url('/admin/plugins.php'));
$PAGE->navbar->add('Local plugins', new moodle_url('/admin/category.php?category=localplugins'));
$PAGE->navbar->add('Chapa Subscription', new moodle_url('/local/chapa_subscription/admin/reports.php'));
$PAGE->navbar->add('Reports', new moodle_url('/local/chapa_subscription/admin/reports.php'));
$PAGE->navbar->add('Manage Subscription');

// Get subscription details
$subscription = $DB->get_record('local_chapa_subscriptions', array('id' => $subscription_id));
if (!$subscription) {
    throw new moodle_exception('subscription_not_found', 'local_chapa_subscription');
}

$user = $DB->get_record('user', array('id' => $subscription->userid));
$plan = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid));
$plans = $DB->get_records('local_chapa_plans', array(), 'monthlyprice ASC');

// Handle actions
if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'cancel':
            // Admin schedules cancellation at period end: disable auto-renew and create a cancellation record.
            $subscription->auto_renew = 0;
            $subscription->timemodified = time();
            $DB->update_record('local_chapa_subscriptions', $subscription);

            $cancellation = new stdClass();
            $cancellation->subscription_id = $subscription->id;
            $cancellation->userid = $subscription->userid;
            $cancellation->cancelled_at = time();
            $cancellation->reason = 'Admin scheduled cancellation';
            $cancellation->status = 'scheduled';
            $DB->insert_record('local_chapa_cancellations', $cancellation);

            redirect($PAGE->url, 'Cancellation scheduled. Access remains until ' . date('Y-m-d', $subscription->endtime) . '.', null, \core\output\notification::NOTIFY_SUCCESS);
            break;
            
        case 'activate':
            $subscription->status = 'active';
            $subscription->auto_renew = 1; // enforce auto-renew on admin activation
            $subscription->timemodified = time();
            $DB->update_record('local_chapa_subscriptions', $subscription);
            
            // Ensure user is in correct cohort
            local_chapa_subscription_handle_cohort_transition($subscription->userid, $subscription->planid, 'admin_activation');
            
            redirect($PAGE->url, 'Subscription activated successfully', null, \core\output\notification::NOTIFY_SUCCESS);
            break;
            
        case 'change_plan':
            $new_plan_id = required_param('new_plan_id', PARAM_INT);
            $new_plan = $DB->get_record('local_chapa_plans', array('id' => $new_plan_id));
            
            if ($new_plan) {
                $old_plan = $plan;
                // Admin action: apply plan change immediately (both upgrades and downgrades)
                $subscription->planid = $new_plan_id;
                $subscription->auto_renew = 1; // keep auto-renew on plan changes
                $subscription->timemodified = time();
                $DB->update_record('local_chapa_subscriptions', $subscription);

                // Remove any pending downgrade requests for this user since we applied immediately
                $DB->delete_records('local_chapa_downgrade_requests', array('userid' => $subscription->userid));
                
                // Handle cohort transition
                local_chapa_subscription_handle_cohort_transition($subscription->userid, $new_plan_id, 'admin_change');
                
                // Log the change (if table exists)
                $dbman = $DB->get_manager();
                $planchangestable = new xmldb_table('local_chapa_plan_changes');
                if ($dbman->table_exists($planchangestable)) {
                    $change_log = new stdClass();
                    $change_log->subscription_id = $subscription_id;
                    $change_log->old_plan_id = $old_plan->id;
                    $change_log->new_plan_id = $new_plan_id;
                    $change_log->changed_by = $USER->id;
                    $change_log->change_reason = 'Admin change';
                    $change_log->changed_at = time();
                    $DB->insert_record('local_chapa_plan_changes', $change_log);
                }
                
                redirect($PAGE->url, 'Plan changed successfully', null, \core\output\notification::NOTIFY_SUCCESS);
            }
            break;
            
        // Refund feature removed
    }
}

// Get payment history
$payments = $DB->get_records('local_chapa_payments', 
    array('subscriptionid' => $subscription_id), 'created_at DESC');

// Get refunds (table may not exist on older installs)
$dbman = $DB->get_manager();
$refunds = array();
$refundstable = new xmldb_table('local_chapa_refunds');
if ($dbman->table_exists($refundstable)) {
    $refunds = $DB->get_records('local_chapa_refunds', array('subscription_id' => $subscription_id), 'processed_at DESC');
}

echo $OUTPUT->header();

// Subscription details
echo '<div class="row">';
echo '<div class="col-md-8">';
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5>Subscription Details</h5>';
echo '</div>';
echo '<div class="card-body">';

echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<p><strong>User:</strong> ' . fullname($user) . ' (' . $user->email . ')</p>';
echo '<p><strong>Plan:</strong> ' . $plan->fullname . ' (' . number_format($plan->monthlyprice / 100, 0) . ' ETB/month)</p>';
// Compute status label and class
$status_text = ucfirst($subscription->status);
$status_class = 'secondary';
if ($subscription->status === 'active') {
    if (empty($subscription->auto_renew)) {
        $status_text = 'Active (Pending cancellation)';
        $status_class = 'warning';
    } else {
        $status_text = 'Active';
        $status_class = 'success';
    }
} else if ($subscription->status === 'cancelled') {
    $status_class = 'danger';
} else if ($subscription->status === 'expired') {
    $status_class = 'warning';
} else if ($subscription->status === 'pending') {
    $status_class = 'info';
}
echo '<p><strong>Status:</strong> <span class="badge badge-' . $status_class . '">' . $status_text . '</span></p>';
echo '<p><strong>Start Date:</strong> ' . date('Y-m-d H:i:s', $subscription->timecreated) . '</p>';
// Show next billing only if auto-renew is enabled
if (!empty($subscription->auto_renew)) {
    echo '<p><strong>Next Billing:</strong> ' . ($subscription->endtime ? date('Y-m-d', $subscription->endtime) : 'N/A') . '</p>';
}
echo '</div>';
echo '<div class="col-md-6">';
echo '<p><strong>Auto Renewal:</strong> ' . (!empty($subscription->auto_renew) ? 'Yes' : 'No') . '</p>';
echo '<p><strong>Last Modified:</strong> ' . date('Y-m-d H:i:s', $subscription->timemodified) . '</p>';
echo '</div>';
echo '</div>';

echo '</div>';
echo '</div>';
echo '</div>';

// Management actions
echo '<div class="col-md-4">';
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5>Management Actions</h5>';
echo '</div>';
echo '<div class="card-body">';

if ($subscription->status == 'active') {
    $cancelurl = new moodle_url('/local/chapa_subscription/admin/manage_subscription.php', array('id' => $subscription_id, 'action' => 'cancel', 'sesskey' => sesskey()));
    echo '<a href="' . $cancelurl . '" data-href="' . $cancelurl . '" class="btn btn-danger btn-block mb-2 js-open-cancel">Cancel Subscription</a>';
} else {
    echo '<a href="' . new moodle_url('/local/chapa_subscription/admin/manage_subscription.php', array('id' => $subscription_id, 'action' => 'activate', 'sesskey' => sesskey())) . '" class="btn btn-success btn-block mb-2">Activate Subscription</a>';
}

// Plan change form
echo '<form method="post" class="mb-3" id="change-plan-form">';
echo '<input type="hidden" name="action" value="change_plan">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
echo '<div class="form-group">';
echo '<label for="new_plan_id">Change Plan:</label>';
echo '<select name="new_plan_id" id="new_plan_id" class="form-control">';
foreach ($plans as $p) {
    $selected = ($p->id == $plan->id) ? 'selected' : '';
    echo "<option value=\"{$p->id}\" $selected>{$p->fullname} - " . number_format($p->monthlyprice / 100, 0) . " ETB/month</option>";
}
echo '</select>';
echo '</div>';
echo '<button type="submit" id="change-plan-btn" class="btn btn-warning btn-block">Change Plan</button>';
echo '</form>';

// Refund feature removed from UI

echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Plugin-style modals and JS
echo '\n<div class="modal fade" id="confirmCancelModal" tabindex="-1" role="dialog" aria-hidden="true">\n  <div class="modal-dialog" role="document">\n    <div class="modal-content">\n      <div class="modal-header"><h5 class="modal-title">Confirm Cancellation</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>\n      <div class="modal-body">Are you sure you want to cancel this subscription?</div>\n      <div class="modal-footer">\n        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>\n        <a href="#" id="confirm-cancel-go" class="btn btn-danger">Yes, Cancel</a>\n      </div>\n    </div>\n  </div>\n</div>\n';

echo '\n<div class="modal fade" id="confirmChangeModal" tabindex="-1" role="dialog" aria-hidden="true">\n  <div class="modal-dialog" role="document">\n    <div class="modal-content">\n      <div class="modal-header"><h5 class="modal-title">Confirm Plan Change</h5><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>\n      <div class="modal-body">Are you sure you want to change this user\'s plan?</div>\n      <div class="modal-footer">\n        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>\n        <button type="button" id="confirm-change-go" class="btn btn-warning">Yes, Change</button>\n      </div>\n    </div>\n  </div>\n</div>\n';

echo "\n<script>\nrequire(['jquery', 'core/notification'], function($, Notification) {\n  $(function(){\n    // Cancel subscription confirmation (plugin modal)\n    $('.js-open-cancel').on('click', function(e){\n      e.preventDefault();\n      var href = $(this).data('href');\n      Notification.confirm(\n        'Confirm Cancellation',\n        'Are you sure you want to cancel this subscription?',\n        'Yes, Cancel',\n        'No',\n        function(){ window.location.href = href; }\n      );\n    });\n\n    // Change plan confirmation (plugin modal)\n    $('#change-plan-btn').on('click', function(){\n      Notification.confirm(\n        'Confirm Plan Change',\n        'Are you sure you want to change this user\'s plan?',\n        'Yes, Change',\n        'No',\n        function(){ $('#change-plan-form').trigger('submit'); }\n      );\n    });\n  });\n});\n</script>\n";

// Payment history
echo '<div class="row mt-4">';
echo '<div class="col-md-6">';
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5>Payment History</h5>';
echo '</div>';
echo '<div class="card-body">';

if (empty($payments)) {
    echo '<p class="text-muted">No payments found.</p>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-sm">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Date</th>';
    echo '<th>Amount</th>';
    echo '<th>Status</th>';
    echo '<th>Type</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($payments as $payment) {
        $status_class = $payment->chapa_status == 'success' ? 'badge-success' : 'badge-warning';
        echo '<tr>';
        echo '<td>' . date('Y-m-d H:i', $payment->created_at) . '</td>';
        echo '<td>' . number_format($payment->amount / 100, 2) . ' ETB</td>';
        echo '<td><span class="badge ' . $status_class . '">' . ucfirst($payment->chapa_status) . '</span></td>';
        echo '<td>' . ($payment->payment_method ? s($payment->payment_method) : '-') . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

echo '</div>';
echo '</div>';
echo '</div>';

// Refund feature removed from UI
echo '</div>';

echo '<div class="mt-3">';
echo '<a href="reports.php" class="btn btn-secondary">Back to Reports</a>';
echo '</div>';

echo $OUTPUT->footer();
