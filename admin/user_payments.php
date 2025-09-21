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
 * Admin user payments page for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/xmldb/xmldb_table.php');

// Check admin permissions
require_admin();

$user_id = required_param('user_id', PARAM_INT);

$PAGE->set_url('/local/chapa_subscription/admin/user_payments.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('User Payments');
$PAGE->set_heading('User Payments');

// Navigation
$PAGE->navbar->add('Site administration', new moodle_url('/admin/search.php'));
$PAGE->navbar->add('Plugins', new moodle_url('/admin/plugins.php'));
$PAGE->navbar->add('Local plugins', new moodle_url('/admin/category.php?category=localplugins'));
$PAGE->navbar->add('Chapa Subscription', new moodle_url('/local/chapa_subscription/admin/reports.php'));
$PAGE->navbar->add('Reports', new moodle_url('/local/chapa_subscription/admin/reports.php'));
$PAGE->navbar->add('User Payments');

// Get user details
$user = $DB->get_record('user', array('id' => $user_id));
if (!$user) {
    throw new moodle_exception('user_not_found', 'local_chapa_subscription');
}

// Get user's subscriptions
$subscriptions = $DB->get_records('local_chapa_subscriptions', 
    array('userid' => $user_id), 'timecreated DESC');

// Get all payments for this user
$payments = $DB->get_records('local_chapa_payments', 
    array('userid' => $user_id), 'created_at DESC');

// Get refunds for this user (table may not exist on older installs)
$dbman = $DB->get_manager();
$refunds = array();
$refundstable = new xmldb_table('local_chapa_refunds');
if ($dbman->table_exists($refundstable)) {
    $refunds = $DB->get_records('local_chapa_refunds', array('userid' => $user_id), 'processed_at DESC');
}

echo $OUTPUT->header();

// User info
echo '<div class="card mb-4">';
echo '<div class="card-header">';
echo '<h5>User Information</h5>';
echo '</div>';
echo '<div class="card-body">';
echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<p><strong>Name:</strong> ' . fullname($user) . '</p>';
echo '<p><strong>Email:</strong> ' . $user->email . '</p>';
echo '<p><strong>Username:</strong> ' . $user->username . '</p>';
echo '</div>';
echo '<div class="col-md-6">';
echo '<p><strong>User ID:</strong> ' . $user->id . '</p>';
echo '<p><strong>Created:</strong> ' . date('Y-m-d H:i:s', $user->timecreated) . '</p>';
echo '<p><strong>Last Login:</strong> ' . ($user->lastlogin ? date('Y-m-d H:i:s', $user->lastlogin) : 'Never') . '</p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Subscriptions
echo '<div class="card mb-4">';
echo '<div class="card-header">';
echo '<h5>Subscriptions</h5>';
echo '</div>';
echo '<div class="card-body">';

if (empty($subscriptions)) {
    echo '<p class="text-muted">No subscriptions found.</p>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Plan</th>';
    echo '<th>Status</th>';
    echo '<th>Start Date</th>';
    echo '<th>Next Billing</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($subscriptions as $subscription) {
        $plan = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid));
        $status_class = '';
        switch ($subscription->status) {
            case 'active':
                $status_class = 'badge-success';
                break;
            case 'cancelled':
                $status_class = 'badge-danger';
                break;
            case 'expired':
                $status_class = 'badge-warning';
                break;
            case 'pending':
                $status_class = 'badge-info';
                break;
        }
        
        echo '<tr>';
        echo '<td>' . $plan->fullname . ' (' . number_format($plan->monthlyprice / 100, 0) . ' ETB/month)</td>';
        echo '<td><span class="badge ' . $status_class . '">' . ucfirst($subscription->status) . '</span></td>';
        echo '<td>' . date('Y-m-d H:i', $subscription->timecreated) . '</td>';
        echo '<td>' . ($subscription->endtime ? date('Y-m-d', $subscription->endtime) : 'N/A') . '</td>';
        echo '<td>';
        echo '<a href="manage_subscription.php?id=' . $subscription->id . '" class="btn btn-sm btn-outline-primary">Manage</a>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

echo '</div>';
echo '</div>';

// Payment history
echo '<div class="card mb-4">';
echo '<div class="card-header">';
echo '<h5>Payment History</h5>';
echo '</div>';
echo '<div class="card-body">';

if (empty($payments)) {
    echo '<p class="text-muted">No payments found.</p>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Date</th>';
    echo '<th>Amount</th>';
    echo '<th>Status</th>';
    echo '<th>Method</th>';
    echo '<th>Transaction ID</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $total_paid = 0;
    foreach ($payments as $payment) {
        if ($payment->chapa_status === 'success') {
            $total_paid += $payment->amount;
        }
        $status_class = $payment->chapa_status === 'success' ? 'badge-success' : 'badge-warning';
        
        echo '<tr>';
        echo '<td>' . date('Y-m-d H:i', $payment->created_at) . '</td>';
        echo '<td>' . number_format($payment->amount / 100, 2) . ' ETB</td>';
        echo '<td><span class="badge ' . $status_class . '">' . ucfirst($payment->chapa_status) . '</span></td>';
        echo '<td>' . ($payment->payment_method ? s($payment->payment_method) : '-') . '</td>';
        echo '<td>' . ($payment->chapa_txn_id ?: 'N/A') . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    echo '<div class="mt-3">';
    echo '<strong>Total Paid: ' . number_format($total_paid / 100, 2) . ' ETB</strong>';
    echo '</div>';
}

echo '</div>';
echo '</div>';

// Refund history
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5>Refund History</h5>';
echo '</div>';
echo '<div class="card-body">';

if (empty($refunds)) {
    echo '<p class="text-muted">No refunds found.</p>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Date</th>';
    echo '<th>Amount</th>';
    echo '<th>Reason</th>';
    echo '<th>Processed By</th>';
    echo '<th>Status</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $total_refunded = 0;
    foreach ($refunds as $refund) {
        $total_refunded += $refund->amount;
        $processed_by = $DB->get_record('user', array('id' => $refund->processed_by));
        
        echo '<tr>';
        echo '<td>' . date('Y-m-d H:i', $refund->processed_at) . '</td>';
        echo '<td>' . number_format($refund->amount, 0) . ' ETB</td>';
        echo '<td>' . $refund->reason . '</td>';
        echo '<td>' . fullname($processed_by) . '</td>';
        echo '<td><span class="badge badge-info">' . ucfirst($refund->status) . '</span></td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    echo '<div class="mt-3">';
    echo '<strong>Total Refunded: ' . number_format($total_refunded, 0) . ' ETB</strong>';
    echo '</div>';
}

echo '</div>';
echo '</div>';

echo '<div class="mt-3">';
echo '<a href="reports.php" class="btn btn-secondary">Back to Reports</a>';
echo '</div>';

echo $OUTPUT->footer();
