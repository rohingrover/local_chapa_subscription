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
 * Webhook handler for Chapa payment notifications.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/chapa_subscription/lib.php');

// Log webhook for debugging
$log_data = array(
    'timestamp' => time(),
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'body' => file_get_contents('php://input')
);

// Get raw input
$input = file_get_contents('php://input');
$webhook_data = json_decode($input, true);

// Log the webhook data
error_log('Chapa Webhook Received: ' . json_encode($webhook_data));

// Verify webhook signature (if provided by Chapa)
$headers = getallheaders();
$signature = $headers['X-Chapa-Signature'] ?? '';

// Get settings
$settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
$config = array();
foreach ($settings as $setting) {
    $config[$setting->name] = $setting->value;
}

$chapa_secret_key = $config['chapa_secret_key'];

// Verify signature (implement according to Chapa's documentation)
$expected_signature = hash_hmac('sha256', $input, $chapa_secret_key);
if ($signature && !hash_equals($expected_signature, $signature)) {
    http_response_code(400);
    error_log('Chapa Webhook: Invalid signature');
    exit('Invalid signature');
}

// Process webhook data
if ($webhook_data && isset($webhook_data['tx_ref'])) {
    $tx_ref = $webhook_data['tx_ref'];
    $status = $webhook_data['status'] ?? '';
    $chapa_txn_id = $webhook_data['data']['id'] ?? '';
    
    // Extract subscription ID from transaction reference
    if (preg_match('/chapa_subscription_(\d+)_/', $tx_ref, $matches)) {
        $subscription_id = $matches[1];
        
        // Get subscription and payment records
        $subscription = $DB->get_record('local_chapa_subscriptions', array('id' => $subscription_id));
        $payment = $DB->get_record('local_chapa_payments', array('subscriptionid' => $subscription_id));
        
        if ($subscription && $payment) {
            // Update payment record
            $payment->chapa_txn_id = $chapa_txn_id;
            $payment->chapa_status = $status;
            $payment->payment_method = $webhook_data['data']['payment_method'] ?? '';
            $DB->update_record('local_chapa_payments', $payment);
            
            if ($status == 'success') {
                // Payment successful
                $subscription->status = 'active';
                $subscription->chapa_customer_id = $webhook_data['data']['customer']['id'] ?? '';
                $subscription->chapa_subscription_id = $webhook_data['data']['subscription']['id'] ?? '';
                $subscription->recurring_token = $webhook_data['data']['recurring_token'] ?? '';
                $subscription->timemodified = time();
                
                $DB->update_record('local_chapa_subscriptions', $subscription);
                
                // Assign user to appropriate cohort
                $plan = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid));
                $cohort_id = null;
                
                switch ($plan->shortname) {
                    case 'basic':
                        $cohort_id = $config['basic_cohort'];
                        break;
                    case 'standard':
                        $cohort_id = $config['standard_cohort'];
                        break;
                    case 'premium':
                        $cohort_id = $config['premium_cohort'];
                        break;
                }
                
                if ($cohort_id) {
                    // Remove user from free preview cohort
                    $free_preview_cohort = $config['free_preview_cohort'];
                    if ($free_preview_cohort) {
                        $DB->delete_records('cohort_members', array(
                            'cohortid' => $free_preview_cohort,
                            'userid' => $subscription->userid
                        ));
                    }
                    
                    // Add user to subscription cohort
                    $existing_member = $DB->get_record('cohort_members', array(
                        'cohortid' => $cohort_id,
                        'userid' => $subscription->userid
                    ));
                    
                    if (!$existing_member) {
                        $cohort_member = new stdClass();
                        $cohort_member->cohortid = $cohort_id;
                        $cohort_member->userid = $subscription->userid;
                        $cohort_member->timeadded = time();
                        $DB->insert_record('cohort_members', $cohort_member);
                    }
                }
                
                // Send receipt email
                local_chapa_subscription_send_receipt($payment->id);
                
                // Generate invoice if enabled
                if ($config['enable_invoices']) {
                    // TODO: Implement PDF invoice generation
                    // This would typically involve creating a PDF with invoice details
                }
                
                error_log('Chapa Webhook: Payment successful for subscription ' . $subscription_id);
                
            } else if ($status == 'failed') {
                // Payment failed
                $subscription->status = 'cancelled';
                $subscription->timemodified = time();
                $DB->update_record('local_chapa_subscriptions', $subscription);
                
                // Send failure notification email
                $user = $DB->get_record('user', array('id' => $subscription->userid));
                if ($user) {
                    $template = $config['renewal_failed_template'] ?? '';
                    if ($template) {
                        $message = str_replace(
                            array('{firstname}', '{lastname}', '{plan}', '{amount}', '{currency}', '{enddate}', '{site}'),
                            array(
                                $user->firstname,
                                $user->lastname,
                                $plan->fullname,
                                $payment->amount / 100,
                                $payment->currency,
                                date('Y-m-d', $subscription->endtime),
                                $CFG->fullname
                            ),
                            $template
                        );
                        
                        $subject = get_string('renewal_failed', 'local_chapa_subscription');
                        email_to_user($user, $CFG->noreplyaddress, $subject, $message);
                    }
                }
                
                error_log('Chapa Webhook: Payment failed for subscription ' . $subscription_id);
            }
        } else {
            error_log('Chapa Webhook: Subscription or payment not found for ID ' . $subscription_id);
        }
    } else {
        error_log('Chapa Webhook: Invalid transaction reference format: ' . $tx_ref);
    }
} else {
    error_log('Chapa Webhook: Invalid webhook data received');
}

// Return success response
http_response_code(200);
echo 'OK';
