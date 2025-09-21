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
 * Upgrade success page for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/chapa_subscription/lib.php');
require_login();

$payment_id = required_param('payment_id', PARAM_INT);

$PAGE->set_url('/local/chapa_subscription/upgrade_success.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Upgrade Successful');
$PAGE->set_heading('Upgrade Successful');

// Get payment details
$payment = $DB->get_record('local_chapa_payments', array('id' => $payment_id));
$subscription = $DB->get_record('local_chapa_subscriptions', array('id' => $payment->subscriptionid));
$current_plan = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid));
$target_plan = $DB->get_record('local_chapa_plans', array('id' => $payment->target_plan_id));

// Verify user owns the payment
if ($payment->userid != $USER->id) {
    throw new moodle_exception('invalid_payment', 'local_chapa_subscription');
}

// Auto-check payment status if still pending
if ($payment->status == 'pending' && $payment->chapa_txn_id) {
    // Get Chapa settings
    $settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
    $config = array();
    foreach ($settings as $setting) {
        $config[$setting->name] = $setting->value;
    }

    $chapa_secret_key = $config['chapa_secret_key'];
    $chapa_base_url = $config['sandbox_mode'] ? 'https://api.chapa.co/v1' : 'https://api.chapa.co/v1';

    // Verify payment with Chapa
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $chapa_base_url . '/transaction/verify/' . $payment->chapa_txn_id);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Authorization: Bearer ' . $chapa_secret_key,
        'Content-Type: application/json'
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code == 200) {
        $chapa_response = json_decode($response, true);
        if ($chapa_response['status'] == 'success' && $chapa_response['data']['status'] == 'success') {
            // Payment is successful, process upgrade
            $payment->status = 'success';
            $payment->chapa_status = 'success';
            $DB->update_record('local_chapa_payments', $payment);

            // Update subscription to new plan
            $subscription->planid = $target_plan->id;
            $subscription->timemodified = time();
            $DB->update_record('local_chapa_subscriptions', $subscription);

            // Log subscription activity
            $log_entry = new stdClass();
            $log_entry->userid = $USER->id;
            $log_entry->subscription_id = $subscription->id;
            $log_entry->action = 'upgrade';
            $log_entry->from_plan_id = $current_plan->id;
            $log_entry->to_plan_id = $target_plan->id;
            $log_entry->amount = $payment->amount;
            $log_entry->currency = $payment->currency;
            $log_entry->payment_id = $payment_id;
            $log_entry->reason = 'Plan upgrade';
            $log_entry->created_at = time();
            $DB->insert_record('local_chapa_subscription_logs', $log_entry);

            // Handle cohort transition for upgrade
            local_chapa_subscription_handle_cohort_transition($USER->id, $target_plan->id, 'upgrade');

            // Refresh payment data
            $payment = $DB->get_record('local_chapa_payments', array('id' => $payment_id));
        }
    }
}

echo $OUTPUT->header();

if ($payment->status == 'success') {
    echo '<div class="alert alert-success">';
    echo '<h4><i class="fa fa-check-circle"></i> Upgrade Successful!</h4>';
    echo '<p>Your subscription has been successfully upgraded from <strong>' . $current_plan->fullname . '</strong> to <strong>' . $target_plan->fullname . '</strong>.</p>';
    echo '<p>You now have access to all features included in your new plan.</p>';
    echo '</div>';
    
    echo '<div class="card">';
    echo '<div class="card-header">';
    echo '<h5>Upgrade Details</h5>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<div class="row">';
    echo '<div class="col-md-6">';
    echo '<p><strong>Previous Plan:</strong> ' . $current_plan->fullname . '</p>';
    echo '<p><strong>New Plan:</strong> ' . $target_plan->fullname . '</p>';
    echo '</div>';
    echo '<div class="col-md-6">';
    echo '<p><strong>Upgrade Amount:</strong> ' . number_format($payment->amount, 2) . ' ETB</p>';
    echo '<p><strong>Payment Date:</strong> ' . date('Y-m-d H:i:s', $payment->created_at) . '</p>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="mt-3">';
    echo '<a href="/local/chapa_subscription/user/subscriptions.php" class="btn btn-primary">View My Subscriptions</a>';
    echo '<a href="/course/view.php?id=' . $subscription->courseid . '" class="btn btn-secondary ml-2">Continue Learning</a>';
    echo '</div>';
} else {
    echo '<div class="alert alert-warning">';
    echo '<h4><i class="fa fa-clock"></i> Processing Upgrade...</h4>';
    echo '<p>Your upgrade payment is being processed. Please check back in a few minutes.</p>';
    echo '</div>';
}

echo $OUTPUT->footer();
