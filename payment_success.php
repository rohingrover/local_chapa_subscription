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
 * Payment success page for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/chapa_subscription/lib.php');

// Check if user is logged in
require_login();

$subscription_id = required_param('subscription_id', PARAM_INT);
$tx_ref = optional_param('tx_ref', '', PARAM_RAW);
$trx_ref = optional_param('trx_ref', '', PARAM_RAW);

$PAGE->set_url('/local/chapa_subscription/payment_success.php', array(
    'subscription_id' => $subscription_id
));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('payment_success', 'local_chapa_subscription'));
$PAGE->set_heading(get_string('payment_success', 'local_chapa_subscription'));

// Navigation
$PAGE->navbar->add(get_string('pluginname', 'local_chapa_subscription'));
$PAGE->navbar->add(get_string('payment_success', 'local_chapa_subscription'));

// Get subscription details
$subscription = $DB->get_record('local_chapa_subscriptions', array('id' => $subscription_id));
// Fetch the most recent payment for this subscription
$payment = $DB->get_record_sql('SELECT * FROM {local_chapa_payments} WHERE subscriptionid = ? ORDER BY id DESC LIMIT 1', array($subscription_id));
$plan = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid));

// Verify ownership
if ($subscription->userid != $USER->id) {
    throw new moodle_exception('error_access_denied', 'local_chapa_subscription');
}

// Auto-check payment status if still pending
$verify_ref = '';
if (!empty($payment)) {
    $verify_ref = !empty($payment->chapa_txn_id) ? $payment->chapa_txn_id : '';
}
if (!$verify_ref) {
    $verify_ref = $tx_ref ?: $trx_ref;
}

if ($subscription->status == 'pending' && !empty($verify_ref)) {
    // Check with Chapa API to verify payment status
    $settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
    $config = array();
    foreach ($settings as $setting) {
        $config[$setting->name] = $setting->value;
    }
    
    $chapa_secret_key = $config['chapa_secret_key'];
    $chapa_base_url = $config['sandbox_mode'] ? 'https://api.chapa.co/v1' : 'https://api.chapa.co/v1';
    
    // Verify payment with Chapa
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $chapa_base_url . '/transaction/verify/' . urlencode($verify_ref));
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
        if (!empty($chapa_response['status']) && $chapa_response['status'] == 'success' &&
            !empty($chapa_response['data']['status']) && $chapa_response['data']['status'] == 'success') {
            // Payment is successful, update subscription
            $subscription->status = 'active';
            $subscription->timemodified = time();
            $DB->update_record('local_chapa_subscriptions', $subscription);
            
            // Update payment status (if record exists)
            if ($payment) {
                $payment->chapa_status = 'success';
                // Ensure we store the reference and payment method if available
                if (empty($payment->chapa_txn_id)) {
                    $payment->chapa_txn_id = $verify_ref;
                }
                if (!empty($chapa_response['data']['payment_method'])) {
                    $payment->payment_method = $chapa_response['data']['payment_method'];
                } else if (empty($payment->payment_method)) {
                    $payment->payment_method = 'Chapa';
                }
                $payment->timemodified = time();
                $DB->update_record('local_chapa_payments', $payment);
            }
            
            // Handle cohort transition
            local_chapa_subscription_handle_cohort_transition($subscription->userid, $subscription->planid, 'new_subscription');
            
            // Send receipt email
            if ($payment) {
                local_chapa_subscription_send_receipt($payment->id);
            }
            
            // Redirect immediately to subscriptions page
            redirect(new moodle_url('/local/chapa_subscription/user/subscriptions.php'));
        }
    }
}

// Output page
echo $OUTPUT->header();

if ($subscription->status == 'active') {
    echo $OUTPUT->notification(get_string('payment_success', 'local_chapa_subscription'), 'success');
    
    echo '<div class="card">
        <div class="card-header">
            <h4>' . get_string('subscription_details', 'local_chapa_subscription') . '</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <strong>' . get_string('plan', 'local_chapa_subscription') . ':</strong>
                    ' . $plan->fullname . '
                </div>
                <div class="col-md-6">
                    <strong>' . get_string('status', 'local_chapa_subscription') . ':</strong>
                    <span class="badge badge-success">' . get_string('active', 'local_chapa_subscription') . '</span>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <strong>' . get_string('subscription_start', 'local_chapa_subscription') . ':</strong>
                    ' . date('Y-m-d H:i:s', $subscription->starttime) . '
                </div>
                <div class="col-md-6">
                    <strong>' . get_string('subscription_end', 'local_chapa_subscription') . ':</strong>
                    ' . date('Y-m-d H:i:s', $subscription->endtime) . '
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <strong>' . get_string('amount', 'local_chapa_subscription') . ':</strong>
                    ' . ($payment->amount / 100) . ' ' . $payment->currency . '
                </div>
                <div class="col-md-6">
                    <strong>' . get_string('duration', 'local_chapa_subscription') . ':</strong>
                    ' . $payment->months . ' ' . get_string('months', 'local_chapa_subscription') . '
                </div>
            </div>';
    
    if ($payment->discount_percent > 0) {
        echo '<div class="row">
            <div class="col-md-6">
                <strong>' . get_string('discount', 'local_chapa_subscription') . ':</strong>
                ' . $payment->discount_percent . '%
            </div>
        </div>';
    }
    
    echo '</div>
    </div>';
    
    echo '<div class="mt-3">
        <a href="' . new moodle_url('/local/chapa_subscription/user/subscriptions.php') . '" class="btn btn-primary">
            ' . get_string('view_subscription', 'local_chapa_subscription') . '
        </a>
        <a href="' . new moodle_url('/') . '" class="btn btn-secondary">
            ' . get_string('continue', 'local_chapa_subscription') . '
        </a>
    </div>';
    
} else {
    echo $OUTPUT->notification(get_string('payment_processing', 'local_chapa_subscription'), 'info');
    echo '<p>' . get_string('payment_processing_message', 'local_chapa_subscription') . '</p>';
    echo '<a href="' . new moodle_url('/local/chapa_subscription/user/subscriptions.php') . '" class="btn btn-primary">
        ' . get_string('check_status', 'local_chapa_subscription') . '
    </a>';
}

echo $OUTPUT->footer();
