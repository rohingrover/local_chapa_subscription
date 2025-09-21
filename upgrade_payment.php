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
 * Upgrade payment processing for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();

$current_subscription_id = required_param('current_subscription_id', PARAM_INT);
$target_plan_id = required_param('target_plan_id', PARAM_INT);
$difference_amount = required_param('difference_amount', PARAM_FLOAT);

$PAGE->set_url('/local/chapa_subscription/upgrade_payment.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('upgrade_plan', 'local_chapa_subscription'));
$PAGE->set_heading(get_string('upgrade_plan', 'local_chapa_subscription'));

// Navigation
$PAGE->navbar->add(get_string('pluginname', 'local_chapa_subscription'));
$PAGE->navbar->add(get_string('my_subscription', 'local_chapa_subscription'));
$PAGE->navbar->add(get_string('upgrade_plan', 'local_chapa_subscription'));

// Get subscription and plan details
$current_subscription = $DB->get_record('local_chapa_subscriptions', array('id' => $current_subscription_id));
$current_plan = $DB->get_record('local_chapa_plans', array('id' => $current_subscription->planid));
$target_plan = $DB->get_record('local_chapa_plans', array('id' => $target_plan_id));

// Verify user owns the subscription
if ($current_subscription->userid != $USER->id) {
    throw new moodle_exception('invalid_subscription', 'local_chapa_subscription');
}

// Get Chapa settings
$settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
$config = array();
foreach ($settings as $setting) {
    $config[$setting->name] = $setting->value;
}

$chapa_secret_key = $config['chapa_secret_key'];
$chapa_base_url = $config['sandbox_mode'] ? 'https://api.chapa.co/v1' : 'https://api.chapa.co/v1';

// Create upgrade payment record
$upgrade_payment = new stdClass();
$upgrade_payment->userid = $USER->id;
$upgrade_payment->subscriptionid = $current_subscription_id;
$upgrade_payment->amount = $difference_amount;
$upgrade_payment->currency = 'ETB';
$upgrade_payment->payment_type = 'upgrade';
$upgrade_payment->target_plan_id = $target_plan_id;
$upgrade_payment->status = 'pending';
$upgrade_payment->created_at = time();
$upgrade_payment->chapa_txn_id = null;
$upgrade_payment->chapa_status = 'pending';

$payment_id = $DB->insert_record('local_chapa_payments', $upgrade_payment);

// Prepare Chapa payment data
$callback_url = $CFG->wwwroot . '/local/chapa_subscription/upgrade_success.php';
$return_url = $CFG->wwwroot . '/local/chapa_subscription/user/subscriptions.php';

$payment_data = array(
    'amount' => $difference_amount,
    'currency' => 'ETB',
    'email' => $USER->email,
    'first_name' => $USER->firstname,
    'last_name' => $USER->lastname,
    'phone_number' => $USER->phone1 ?: '251900000000',
    'tx_ref' => 'upgrade_' . $payment_id . '_' . time(),
    'callback_url' => $callback_url,
    'return_url' => $return_url,
    'customization' => array(
        'title' => 'LucyBridge',
        'description' => 'Plan Upgrade Payment',
    'logo' => $OUTPUT->image_url('logo', 'theme')->out(false)
    ),
    'meta' => array(
        'payment_id' => $payment_id,
        'subscription_id' => $current_subscription_id,
        'target_plan_id' => $target_plan_id,
        'payment_type' => 'upgrade'
    )
);

// Make API request to Chapa
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $chapa_base_url . '/transaction/initialize');
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $chapa_secret_key,
    'Content-Type: application/json'
));
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

// Log debug information
error_log("Chapa Upgrade Payment Debug:");
error_log("URL: " . $chapa_base_url . '/transaction/initialize');
error_log("Request Data: " . json_encode($payment_data));
error_log("HTTP Code: " . $http_code);
error_log("Response: " . $response);
error_log("cURL Error: " . $curl_error);

if ($http_code == 200) {
    $chapa_response = json_decode($response, true);
    
    if ($chapa_response['status'] == 'success' && isset($chapa_response['data']['checkout_url'])) {
        // Update payment record with Chapa transaction ID
        $upgrade_payment->chapa_txn_id = $chapa_response['data']['tx_ref'];
        $DB->update_record('local_chapa_payments', $upgrade_payment);
        
        // Redirect to Chapa checkout
        redirect($chapa_response['data']['checkout_url']);
    } else {
        throw new moodle_exception('error_payment_failed', 'local_chapa_subscription', '', 
            'Chapa API Error: ' . ($chapa_response['message'] ?? 'Unknown error'));
    }
} else {
    throw new moodle_exception('error_payment_failed', 'local_chapa_subscription', '', 
        'HTTP Error: ' . $http_code . ' - ' . $response);
}

echo $OUTPUT->header();
echo $OUTPUT->footer();
