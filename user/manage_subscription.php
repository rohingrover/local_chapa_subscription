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
 * User subscription management page for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_login();

$PAGE->set_url('/local/chapa_subscription/user/manage_subscription.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Manage My Subscription');
$PAGE->set_heading('Manage My Subscription');

// Navigation
$PAGE->navbar->add(get_string('pluginname', 'local_chapa_subscription'));
$PAGE->navbar->add('Manage Subscription');

// Get user's current subscription
$current_subscription = $DB->get_record('local_chapa_subscriptions', 
    array('userid' => $USER->id, 'status' => 'active'));

// Get user's payment history
$payments = $DB->get_records('local_chapa_payments', 
    array('userid' => $USER->id), 'created_at DESC', '*', 0, 10);

// Get available plans for upgrade/downgrade
$plans = $DB->get_records('local_chapa_plans', array(), 'monthlyprice ASC');

// Get downgrade requests
$downgrade_requests = $DB->get_records('local_chapa_downgrade_requests', 
    array('userid' => $USER->id, 'status' => 'pending'), 'requested_at DESC');

echo $OUTPUT->header();

if (!$current_subscription) {
    // No active subscription
    echo '<div class="alert alert-info">';
    echo '<h4><i class="fa fa-info-circle"></i> No Active Subscription</h4>';
    echo '<p>You don\'t have an active subscription. <a href="subscribe.php" class="btn btn-primary">Subscribe Now</a></p>';
    echo '</div>';
} else {
    // Get current plan details
    $current_plan = $DB->get_record('local_chapa_plans', array('id' => $current_subscription->planid));
    
    // Subscription details
    echo '<div class="row">';
    echo '<div class="col-md-8">';
    echo '<div class="card">';
    echo '<div class="card-header">';
    echo '<h5><i class="fa fa-credit-card"></i> Current Subscription</h5>';
    echo '</div>';
    echo '<div class="card-body">';
    
    echo '<div class="row">';
    echo '<div class="col-md-6">';
    echo '<p><strong>Plan:</strong> ' . $current_plan->fullname . '</p>';
    echo '<p><strong>Price:</strong> ' . number_format($current_plan->monthlyprice / 100, 0) . ' ETB/month</p>';
    echo '<p><strong>Status:</strong> <span class="badge badge-success">Active</span></p>';
    echo '<p><strong>Start Date:</strong> ' . date('Y-m-d', $current_subscription->timecreated) . '</p>';
    echo '</div>';
    echo '<div class="col-md-6">';
    echo '<p><strong>Next Billing:</strong> ' . ($current_subscription->endtime ? date('Y-m-d', $current_subscription->endtime) : 'N/A') . '</p>';
    echo '<p><strong>Auto Renewal:</strong> ' . ($current_subscription->auto_renew ? 'Yes' : 'No') . '</p>';
    echo '<p><strong>Last Modified:</strong> ' . date('Y-m-d H:i', $current_subscription->timemodified) . '</p>';
    echo '</div>';
    echo '</div>';
    
    // Plan features - use same features as subscribe.php
    $plan_features = array(
        'basic' => array(
            'Full access to video lessons',
            'Short notes',
            'Basic support'
        ),
        'standard' => array(
            'Full access to video lessons',
            'Short notes',
            'Access to AI assistant',
            'Review Question Videos',
            'Entrance Exam Question Videos',
            'Question Bank'
        ),
        'premium' => array(
            'Full access to video lessons',
            'Short notes',
            'Access to AI assistant',
            'Review Question Videos',
            'Entrance Exam Question Videos',
            'Question Bank',
            'Access to special Telegram channel',
            'Ability to forward questions and receive tailored responses',
            'Priority support'
        )
    );
    
    echo '<div class="mt-3">';
    echo '<h6>Plan Features:</h6>';
    echo '<ul class="list-unstyled">';
    
    $current_features = isset($plan_features[$current_plan->shortname]) ? $plan_features[$current_plan->shortname] : array();
    foreach ($current_features as $feature) {
        echo '<li><i class="fa fa-check text-success"></i> ' . $feature . '</li>';
    }
    
    echo '</ul>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Management options
    echo '<div class="col-md-4">';
    echo '<div class="card">';
    echo '<div class="card-header">';
    echo '<h5><i class="fa fa-cog"></i> Manage Subscription</h5>';
    echo '</div>';
    echo '<div class="card-body">';
    
    // Upgrade options
    $upgrade_plans = array();
    foreach ($plans as $plan) {
        if ($plan->monthlyprice > $current_plan->monthlyprice) {
            $upgrade_plans[] = $plan;
        }
    }
    
    if (!empty($upgrade_plans)) {
        echo '<h6>Upgrade Plan:</h6>';
        echo '<p class="text-muted small">Upgrade to a higher plan for more features and access.</p>';
        echo '<a href="../subscribe.php" class="btn btn-success">Upgrade Plan</a>';
    }
    
    // Downgrade options
    $downgrade_plans = array();
    foreach ($plans as $plan) {
        if ($plan->monthlyprice < $current_plan->monthlyprice) {
            $downgrade_plans[] = $plan;
        }
    }
    
    if (!empty($downgrade_plans)) {
        echo '<hr>';
        echo '<h6>Downgrade Plan:</h6>';
        foreach ($downgrade_plans as $plan) {
            echo '<div class="mb-2">';
            echo '<strong>' . $plan->fullname . '</strong><br>';
            echo '<small class="text-muted">' . number_format($plan->monthlyprice / 100, 0) . ' ETB/month</small><br>';
            
            // Check if downgrade is allowed
            $next_billing_date = $current_subscription->endtime;
            $current_time = time();
            
            if ($next_billing_date > $current_time) {
                // Schedule downgrade
                echo '<small class="text-warning">Will take effect on next billing date</small><br>';
                echo '<a href="schedule_downgrade.php?current_subscription_id=' . $current_subscription->id . '&target_plan_id=' . $plan->id . '" class="btn btn-sm btn-warning">Schedule Downgrade</a>';
            } else {
                // Immediate downgrade
                echo '<a href="downgrade_payment.php?current_subscription_id=' . $current_subscription->id . '&target_plan_id=' . $plan->id . '" class="btn btn-sm btn-warning">Downgrade Now</a>';
            }
            echo '</div>';
        }
    }
    
    // Invoice and receipt options
    echo '<hr>';
    echo '<h6>Invoices & Receipts:</h6>';
    echo '<div class="mb-2">';
    echo '<a href="invoice.php?subscription_id=' . $current_subscription->id . '" class="btn btn-sm btn-outline-primary">View Latest Invoice</a> ';
    echo '<a href="resend_receipt.php?subscription_id=' . $current_subscription->id . '" class="btn btn-sm btn-outline-secondary">Resend Receipt</a>';
    echo '</div>';
    
    // Cancel subscription
    echo '<hr>';
    echo '<h6>Cancel Subscription:</h6>';
    echo '<p class="text-muted small">Cancelling will end your subscription at the next billing date.</p>';
    echo '<a href="cancel_subscription.php?subscription_id=' . $current_subscription->id . '" class="btn btn-sm btn-danger">Cancel Subscription</a>';
    
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Pending downgrade requests
    if (!empty($downgrade_requests)) {
        echo '<div class="row mt-4">';
        echo '<div class="col-12">';
        echo '<div class="card">';
        echo '<div class="card-header">';
        echo '<h5><i class="fa fa-clock"></i> Pending Downgrade Requests</h5>';
        echo '</div>';
        echo '<div class="card-body">';
        
        foreach ($downgrade_requests as $request) {
            $target_plan = $DB->get_record('local_chapa_plans', array('id' => $request->target_plan_id));
            echo '<div class="alert alert-warning">';
            echo '<div class="row">';
            echo '<div class="col-md-8">';
            echo '<strong>Downgrade to ' . $target_plan->fullname . '</strong><br>';
            echo '<small>Requested on: ' . date('Y-m-d H:i', $request->requested_at) . '</small><br>';
            echo '<small>Scheduled for: ' . date('Y-m-d H:i', $request->scheduled_for) . '</small>';
            echo '</div>';
            echo '<div class="col-md-4 text-right">';
            echo '<a href="cancel_downgrade.php?request_id=' . $request->id . '" class="btn btn-sm btn-outline-danger" onclick="return confirm(\'Are you sure you want to cancel this downgrade request?\')">';
            echo '<i class="fa fa-times"></i> Cancel Request';
            echo '</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
}

// Payment history
echo '<div class="row mt-4">';
echo '<div class="col-12">';
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5><i class="fa fa-history"></i> Recent Payments</h5>';
echo '</div>';
echo '<div class="card-body">';

if (empty($payments)) {
    echo '<p class="text-muted">No payment history found.</p>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Date</th>';
    echo '<th>Amount</th>';
    echo '<th>Status</th>';
    echo '<th>Type</th>';
    echo '<th>Transaction ID</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($payments as $payment) {
        $status_class = $payment->chapa_status == 'success' ? 'badge-success' : 'badge-warning';
        echo '<tr>';
        echo '<td>' . date('Y-m-d H:i', $payment->created_at) . '</td>';
        echo '<td>' . number_format($payment->amount / 100, 2) . ' ETB</td>';
        echo '<td><span class="badge ' . $status_class . '">' . ucfirst($payment->chapa_status ?: 'pending') . '</span></td>';
        echo '<td>' . ucfirst($payment->payment_method ?: 'chapa') . '</td>';
        echo '<td>' . ($payment->chapa_txn_id ?: 'N/A') . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    echo '<div class="mt-3">';
    echo '<a href="payment_history.php" class="btn btn-outline-primary">View All Payments</a>';
    echo '</div>';
}

echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="mt-3">';
echo '<a href="subscriptions.php" class="btn btn-secondary">Back to My Subscriptions</a>';
echo '</div>';

echo $OUTPUT->footer();
