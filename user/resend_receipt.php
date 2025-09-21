<?php
require_once('../../../config.php');
require_once($CFG->dirroot . '/local/chapa_subscription/lib.php');

require_login();

// Handle both payment_id and subscription_id parameters
$payment_id = optional_param('payment_id', 0, PARAM_INT);
$subscription_id = optional_param('subscription_id', 0, PARAM_INT);

if ($payment_id) {
    $payment = $DB->get_record('local_chapa_payments', array('id' => $payment_id), '*', MUST_EXIST);
    $subscription = $DB->get_record('local_chapa_subscriptions', array('id' => $payment->subscriptionid), '*', MUST_EXIST);
} elseif ($subscription_id) {
    $subscription = $DB->get_record('local_chapa_subscriptions', array('id' => $subscription_id), '*', MUST_EXIST);
    // Get the latest payment for this subscription
    $payment = $DB->get_record('local_chapa_payments', 
        array('subscriptionid' => $subscription_id), '*', MUST_EXIST, 'created_at DESC');
    $payment_id = $payment->id; // Set payment_id for the receipt function
} else {
    throw new moodle_exception('missingparam', 'error', '', 'payment_id or subscription_id');
}

if ($subscription->userid != $USER->id && !is_siteadmin()) {
    throw new moodle_exception('error_access_denied', 'local_chapa_subscription');
}

$sent = local_chapa_subscription_send_receipt($payment_id);

redirect(new moodle_url('/local/chapa_subscription/user/subscriptions.php'), $sent ? 'Receipt sent to your email.' : 'Unable to send receipt at this time.', null, $sent ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_ERROR);


