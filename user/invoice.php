<?php
require_once('../../../config.php');

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
} else {
    throw new moodle_exception('missingparam', 'error', '', 'payment_id or subscription_id');
}

$PAGE->set_url('/local/chapa_subscription/user/invoice.php', array('payment_id' => $payment_id ?: $subscription_id));
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Invoice');
$PAGE->set_heading('Invoice');

global $DB, $OUTPUT, $CFG, $USER;

if ($subscription->userid != $USER->id && !is_siteadmin()) {
    throw new moodle_exception('error_access_denied', 'local_chapa_subscription');
}

$plan = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid), '*', MUST_EXIST);
$user = $DB->get_record('user', array('id' => $subscription->userid), '*', MUST_EXIST);

echo $OUTPUT->header();
// Print CSS to print only the invoice area
echo '<style>
@media print {
  body * { visibility: hidden !important; }
  #invoice-print-area, #invoice-print-area * { visibility: visible !important; }
  #invoice-print-area { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>';
?>
<div id="invoice-print-area">
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h4 class="mb-0">Invoice</h4>
            <small class="text-muted">Reference: <?php echo s($payment->chapa_txn_id ?: '-'); ?></small>
        </div>
        <button class="btn btn-secondary" onclick="window.print()">Print</button>
    </div>
    <div class="card-body">
        <?php
            // Load invoice settings
            $settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
            $config = array();
            foreach ($settings as $setting) { $config[$setting->name] = $setting->value; }
            $company = array(
                'name' => $config['invoice_company_name'] ?? 'LucyBridge Academy',
                'address' => $config['invoice_company_address'] ?? '',
                'phone' => $config['invoice_company_phone'] ?? '',
                'email' => $config['invoice_company_email'] ?? ''
            );
        ?>
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Bill To</h5>
                <p class="mb-0"><?php echo fullname($user); ?></p>
                <small><?php echo s($user->email); ?></small>
            </div>
            <div class="col-md-6 text-md-right">
                <h5><?php echo s($company['name']); ?></h5>
                <?php if (!empty($company['address'])) { echo '<div><small>'.nl2br(s($company['address'])).'</small></div>'; }
                if (!empty($company['phone'])) { echo '<div><small>Phone: '.s($company['phone']).'</small></div>'; }
                if (!empty($company['email'])) { echo '<div><small>Email: '.s($company['email']).'</small></div>'; } ?>
            </div>
        </div>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo s($plan->fullname); ?> (<?php echo (int)$payment->months; ?> months)</td>
                    <td class="text-right"><?php echo number_format(($payment->amount/100), 2) . ' ' . s($payment->currency); ?></td>
                </tr>
            </tbody>
        </table>
        <div class="d-flex justify-content-between align-items-end">
            <div>
                <strong>Payment Method:</strong> <?php echo s($payment->payment_method ?: 'Chapa'); ?><br>
                <strong>Status:</strong> <?php echo s($payment->chapa_status ?: '-'); ?><br>
                <strong>Date:</strong> <?php echo userdate($payment->created_at); ?>
            </div>
            <div class="text-right">
                <div class="border rounded p-3 bg-light">
                    <div class="text-muted">Total</div>
                    <div class="h4 mb-0"><?php echo number_format(($payment->amount/100), 2) . ' ' . s($payment->currency); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<div class="mt-4">
    <a href="manage_subscription.php" class="btn btn-secondary">
        <i class="fa fa-arrow-left"></i> Back to My Subscription
    </a>
</div>

<?php
echo $OUTPUT->footer();


