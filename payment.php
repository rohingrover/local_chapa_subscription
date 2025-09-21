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
 * Payment processing page for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/formslib.php');

// Check if user is logged in
require_login();

$subscription_id = required_param('subscription_id', PARAM_INT);
$payment_id = required_param('payment_id', PARAM_INT);

$PAGE->set_url('/local/chapa_subscription/payment.php', array(
    'subscription_id' => $subscription_id,
    'payment_id' => $payment_id
));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('payment_processing', 'local_chapa_subscription'));
$PAGE->set_heading(get_string('payment_processing', 'local_chapa_subscription'));

// Navigation
$PAGE->navbar->add(get_string('pluginname', 'local_chapa_subscription'));
$PAGE->navbar->add(get_string('payment_processing', 'local_chapa_subscription'));

// Get subscription and payment details
$subscription = $DB->get_record('local_chapa_subscriptions', array('id' => $subscription_id));
$payment = $DB->get_record('local_chapa_payments', array('id' => $payment_id));
$plan = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid));

// Verify ownership
if ($subscription->userid != $USER->id || $payment->userid != $USER->id) {
    throw new moodle_exception('error_access_denied', 'local_chapa_subscription');
}

// Get settings
$settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
$config = array();
foreach ($settings as $setting) {
    $config[$setting->name] = $setting->value;
}

// Check if API keys are configured
if (empty($config['chapa_public_key']) || empty($config['chapa_secret_key'])) {
    throw new moodle_exception('error_api_keys_missing', 'local_chapa_subscription');
}

// Chapa API configuration
$chapa_public_key = $config['chapa_public_key'];
$chapa_secret_key = $config['chapa_secret_key'];
$chapa_encryption_key = $config['chapa_encryption_key'];
$sandbox_mode = $config['sandbox_mode'];

// Chapa API endpoints
$chapa_base_url = $sandbox_mode ? 'https://api.chapa.co/v1' : 'https://api.chapa.co/v1';

// Prepare payment data for Chapa
$amount = $payment->amount / 100; // Convert from cents to birr
$currency = $payment->currency;
$email = $USER->email;
$first_name = $USER->firstname;
$last_name = $USER->lastname;
$phone_number = $USER->phone1 ?: $USER->phone2;

// Generate unique reference
$tx_ref = 'chapa_subscription_' . $subscription_id . '_' . time();

// Prepare callback URLs
$callback_url = $CFG->wwwroot . '/local/chapa_subscription/webhook.php';
$return_url = $CFG->wwwroot . '/local/chapa_subscription/payment_success.php?subscription_id=' . $subscription_id;

// Ensure months is set for description
$months = isset($payment->months) ? (int)$payment->months : 1;

// Chapa payment request data
$payment_data = array(
    'amount' => $amount,
    'currency' => $currency,
    'email' => $email,
    'first_name' => $first_name,
    'last_name' => $last_name,
    'phone_number' => $phone_number,
    'tx_ref' => $tx_ref,
    'callback_url' => $callback_url,
    'return_url' => $return_url,
    'customization' => array(
        'title' => 'LucyBridge',
        'description' => $plan->fullname . ' - ' . $months . ' months',
        'logo' => $OUTPUT->image_url('logo', 'theme')->out(false)
    )
);

// Make API request to Chapa
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $chapa_base_url . '/transaction/initialize');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $chapa_secret_key,
    'Content-Type: application/json'
));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Debug logging
error_log("Chapa API Debug - URL: " . $chapa_base_url . '/transaction/initialize');
error_log("Chapa API Debug - Request Data: " . json_encode($payment_data));
error_log("Chapa API Debug - HTTP Code: $http_code");
error_log("Chapa API Debug - Response: $response");
error_log("Chapa API Debug - cURL Error: $curl_error");

if ($http_code == 200) {
    $chapa_response = json_decode($response, true);
    
    // Extra debug
    error_log('Chapa API Debug - Parsed status: ' . ($chapa_response['status'] ?? 'N/A'));
    error_log('Chapa API Debug - Parsed data keys: ' . (isset($chapa_response['data']) ? implode(',', array_keys((array)$chapa_response['data'])) : 'no data'));

    if (isset($chapa_response['status']) && $chapa_response['status'] === 'success') {
        $reference = $chapa_response['data']['reference'] ?? $tx_ref; // Fallback to our tx_ref
        $checkout_url = $chapa_response['data']['checkout_url'] ?? '';

        if (empty($checkout_url)) {
            $err = 'Missing checkout_url in Chapa response: ' . $response;
            error_log('Chapa API Error - ' . $err);
            throw new moodle_exception('error_payment_failed', 'local_chapa_subscription', '', $err);
        }

        // Update payment record with Chapa transaction ID if available
        $payment->chapa_txn_id = $reference;
        $payment->chapa_status = 'pending';
        $DB->update_record('local_chapa_payments', $payment);
        
        // Redirect to Chapa checkout
        redirect($checkout_url);
    } else {
        // Handle API error
        $error_message = $chapa_response['message'] ?? ($response ?: 'Unknown error occurred');
        error_log('Chapa API Error - Message: ' . $error_message);
        throw new moodle_exception('error_payment_failed', 'local_chapa_subscription', '', $error_message);
    }
} else {
    // Handle HTTP error
    $errmsg = 'HTTP ' . $http_code . ' ' . ($response ?: '');
    error_log('Chapa API HTTP Error - ' . $errmsg);
    throw new moodle_exception('error_payment_failed', 'local_chapa_subscription', '', $errmsg);
}

// Output page (this should not be reached due to redirect)
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('payment_processing', 'local_chapa_subscription'));
echo '<div class="alert alert-info">' . get_string('payment_processing', 'local_chapa_subscription') . '</div>';
echo $OUTPUT->footer();
