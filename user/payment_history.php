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
 * Payment history page for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_login();

$PAGE->set_url('/local/chapa_subscription/user/payment_history.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Payment History');
$PAGE->set_heading('Payment History');

// Navigation
$PAGE->navbar->add(get_string('pluginname', 'local_chapa_subscription'));
$PAGE->navbar->add('Payment History');

// Get user's payment history
$payments = $DB->get_records('local_chapa_payments', 
    array('userid' => $USER->id), 'created_at DESC');

echo $OUTPUT->header();

echo '<div class="row">';
echo '<div class="col-12">';
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5><i class="fa fa-history"></i> Payment History</h5>';
echo '</div>';
echo '<div class="card-body">';

if (empty($payments)) {
    echo '<div class="alert alert-info">';
    echo '<h4><i class="fa fa-info-circle"></i> No Payment History</h4>';
    echo '<p>You don\'t have any payment history yet.</p>';
    echo '<a href="manage_subscription.php" class="btn btn-primary">Back to Subscription</a>';
    echo '</div>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Date</th>';
    echo '<th>Amount</th>';
    echo '<th>Status</th>';
    echo '<th>Payment Method</th>';
    echo '<th>Transaction ID</th>';
    echo '<th>Actions</th>';
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
        echo '<td>';
        if ($payment->chapa_status == 'success') {
            echo '<a href="invoice.php?payment_id=' . $payment->id . '" class="btn btn-sm btn-outline-primary">View Invoice</a> ';
            echo '<a href="resend_receipt.php?payment_id=' . $payment->id . '" class="btn btn-sm btn-outline-secondary">Resend Receipt</a>';
        }
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="mt-3">';
echo '<a href="manage_subscription.php" class="btn btn-secondary">Back to Subscription Management</a>';
echo '</div>';

echo $OUTPUT->footer();
