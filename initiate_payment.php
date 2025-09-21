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
 * Create pending subscription and payment, then redirect to payment.php
 *
 * @package    local_chapa_subscription
 * @copyright  2025 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login();

global $DB, $USER, $CFG;

// Read and validate inputs
$plan = required_param('plan', PARAM_ALPHA);
$duration = required_param('duration', PARAM_ALPHA);

$valid_plans = array('basic', 'standard', 'premium');
if (!in_array($plan, $valid_plans)) {
    throw new moodle_exception('invalid_plan', 'local_chapa_subscription');
}

$duration_months = array(
    'monthly' => 1,
    'quarterly' => 3,
    'semiannual' => 6,
    'annual' => 12
);

// Load discount percentages from admin settings
$settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
$config = array();
foreach ($settings as $setting) {
    $config[$setting->name] = $setting->value;
}

$discount_percentages = array(
    'monthly' => 0,
    'quarterly' => (int)($config['discount_3_months'] ?? 0),
    'semiannual' => (int)($config['discount_6_months'] ?? 0),
    'annual' => (int)($config['discount_12_months'] ?? 0)
);

if (!array_key_exists($duration, $duration_months)) {
    $duration = 'monthly';
}

// Get plan record
$plan_record = $DB->get_record('local_chapa_plans', array('shortname' => $plan), '*', MUST_EXIST);

// Calculate price (in cents)
$months = $duration_months[$duration];
$discount_percent = $discount_percentages[$duration];
$total = $plan_record->monthlyprice * $months; // in cents
$discount_amount = (int) floor($total * ($discount_percent / 100));
$final_amount = $total - $discount_amount; // in cents

// Compute end time by adding months
$starttime = time();
$endtime = strtotime("+{$months} month", $starttime);

// Create pending subscription
$subscription = new stdClass();
$subscription->userid = $USER->id;
$subscription->planid = $plan_record->id;
$subscription->starttime = $starttime;
$subscription->endtime = $endtime;
$subscription->status = 'pending';
$subscription->auto_renew = 1; // Default to auto-renew
$subscription->timecreated = $starttime;
$subscription->timemodified = $starttime;
$subscription_id = $DB->insert_record('local_chapa_subscriptions', $subscription);

// Log subscription activity
$log_entry = new stdClass();
$log_entry->userid = $USER->id;
$log_entry->subscription_id = $subscription_id;
$log_entry->action = 'subscribe';
$log_entry->from_plan_id = null;
$log_entry->to_plan_id = $plan_record->id;
$log_entry->amount = $final_amount;
$log_entry->currency = 'ETB';
$log_entry->payment_id = null; // Will be updated after payment creation
$log_entry->reason = 'New subscription';
$log_entry->created_at = time();
$log_entry_id = $DB->insert_record('local_chapa_subscription_logs', $log_entry);

// Create payment row
$payment = new stdClass();
$payment->userid = $USER->id;
$payment->subscriptionid = $subscription_id;
$payment->amount = $final_amount; // cents
$payment->currency = 'ETB';
$payment->months = $months;
$payment->discount_percent = $discount_percent;
$payment->chapa_status = 'pending';
$payment->created_at = time();
$payment_id = $DB->insert_record('local_chapa_payments', $payment);

// Update log entry with payment ID
$log_entry->id = $log_entry_id;
$log_entry->payment_id = $payment_id;
$DB->update_record('local_chapa_subscription_logs', $log_entry);

// Optionally link lastpaymentid
$subscription->id = $subscription_id;
$subscription->lastpaymentid = $payment_id;
$subscription->timemodified = time();
$DB->update_record('local_chapa_subscriptions', $subscription);

// Redirect to existing payment flow
$url = new moodle_url('/local/chapa_subscription/payment.php', array(
    'subscription_id' => $subscription_id,
    'payment_id' => $payment_id
));
redirect($url);


