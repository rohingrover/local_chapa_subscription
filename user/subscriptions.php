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
 * User subscription dashboard for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/formslib.php');

// Check if user is logged in
require_login();

$PAGE->set_url('/local/chapa_subscription/user/subscriptions.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('my_subscription', 'local_chapa_subscription'));
$PAGE->set_heading(get_string('my_subscription', 'local_chapa_subscription'));

// Navigation
$PAGE->navbar->add(get_string('pluginname', 'local_chapa_subscription'));
$PAGE->navbar->add(get_string('my_subscription', 'local_chapa_subscription'));

// Get user's current subscription (most recent active if multiple)
$current_subscription = null;
$actives = $DB->get_records('local_chapa_subscriptions', array('userid' => $USER->id, 'status' => 'active'), 'timemodified DESC, id DESC');
if (!empty($actives)) {
    $current_subscription = reset($actives);
}

// Get user's payment history
$payments = $DB->get_records('local_chapa_payments', 
    array('userid' => $USER->id), 'created_at DESC');

// Get available plans for upgrade
$plans = $DB->get_records('local_chapa_plans', array(), 'monthlyprice ASC');

// Handle upgrade/downgrade actions
$action = optional_param('action', '', PARAM_ALPHA);
$target_plan_id = optional_param('plan_id', 0, PARAM_INT);

if ($action && $target_plan_id) {
    $target_plan = $DB->get_record('local_chapa_plans', array('id' => $target_plan_id));
    
    if ($target_plan) {
        if ($action === 'upgrade' && $current_subscription) {
            // Calculate upgrade difference
            $current_plan = $DB->get_record('local_chapa_plans', array('id' => $current_subscription->planid));
            $upgrade_difference = $target_plan->monthlyprice - $current_plan->monthlyprice;
            
            if ($upgrade_difference > 0) {
                // Redirect to payment for upgrade difference
                redirect(new moodle_url('/local/chapa_subscription/upgrade_payment.php', array(
                    'current_subscription_id' => $current_subscription->id,
                    'target_plan_id' => $target_plan_id,
                    'difference_amount' => $upgrade_difference
                )));
            }
        } elseif ($action === 'downgrade' && $current_subscription) {
            // Check if downgrade is allowed (only after current billing period)
            $next_billing_date = $current_subscription->endtime;
            $current_time = time();
            
            if ($next_billing_date > $current_time) {
                // Schedule downgrade for next billing period
                $downgrade_request = new stdClass();
                $downgrade_request->userid = $USER->id;
                $downgrade_request->current_plan_id = $current_subscription->planid;
                $downgrade_request->target_plan_id = $target_plan_id;
                $downgrade_request->requested_at = $current_time;
                $downgrade_request->scheduled_for = $next_billing_date;
                $downgrade_request->status = 'pending';
                
                $DB->insert_record('local_chapa_downgrade_requests', $downgrade_request);
                
                redirect($PAGE->url, get_string('downgrade_scheduled', 'local_chapa_subscription'), null, \core\output\notification::NOTIFY_SUCCESS);
            } else {
                // Immediate downgrade
                redirect(new moodle_url('/local/chapa_subscription/downgrade_payment.php', array(
                    'current_subscription_id' => $current_subscription->id,
                    'target_plan_id' => $target_plan_id
                )));
            }
        }
    }
}

// Get settings
$settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
$config = array();
foreach ($settings as $setting) {
    $config[$setting->name] = $setting->value;
}

// Upgrade form
class upgrade_form extends moodleform {
    
    private $plans;
    private $current_subscription;
    
    public function __construct($plans, $current_subscription) {
        $this->plans = $plans;
        $this->current_subscription = $current_subscription;
        parent::__construct();
    }
    
    public function definition() {
        $mform = $this->_form;
        
        $mform->addElement('header', 'upgrade_selection', get_string('upgrade', 'local_chapa_subscription'));
        
        $planoptions = array();
        foreach ($this->plans as $plan) {
            $price = $plan->monthlyprice / 100;
            $planoptions[$plan->id] = $plan->fullname . ' - ' . $price . ' ETB/month';
        }
        
        $mform->addElement('select', 'planid', get_string('plan', 'local_chapa_subscription'), $planoptions);
        $mform->setType('planid', PARAM_INT);
        $mform->addRule('planid', get_string('required'), 'required', null, 'client');
        
        $mform->addElement('selectyesno', 'auto_renew', get_string('auto_renewal', 'local_chapa_subscription'));
        $mform->setDefault('auto_renew', 1);
        
        $this->add_action_buttons(false, get_string('upgrade', 'local_chapa_subscription'));
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Check if selected plan is different from current
        if ($this->current_subscription && $data['planid'] == $this->current_subscription->planid) {
            $errors['planid'] = get_string('error_same_plan', 'local_chapa_subscription');
        }
        
        return $errors;
    }
}

// Output page
echo $OUTPUT->header();

if ($current_subscription) {
    // Get current plan details
    $current_plan = $DB->get_record('local_chapa_plans', array('id' => $current_subscription->planid));
    
    echo $OUTPUT->heading(get_string('my_subscription', 'local_chapa_subscription'));
    
    // Current subscription details
    echo '<div class="card mb-4">
        <div class="card-header">
            <h4>' . get_string('current_plan', 'local_chapa_subscription') . '</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <strong>' . get_string('plan', 'local_chapa_subscription') . ':</strong>
                    ' . $current_plan->fullname . '
                </div>
                <div class="col-md-6">
                    <strong>' . get_string('status', 'local_chapa_subscription') . ':</strong>
                    <span class="badge badge-success">' . get_string('active', 'local_chapa_subscription') . '</span>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <strong>' . get_string('subscription_start', 'local_chapa_subscription') . ':</strong>
                    ' . date('Y-m-d H:i:s', $current_subscription->starttime) . '
                </div>
                <div class="col-md-6">
                    <strong>' . get_string('subscription_end', 'local_chapa_subscription') . ':</strong>
                    ' . date('Y-m-d H:i:s', $current_subscription->endtime) . '
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <strong>' . get_string('next_payment', 'local_chapa_subscription') . ':</strong>
                    ' . date('Y-m-d', $current_subscription->endtime) . '
                </div>
            </div>
        </div>
    </div>';
    
    // Actions: Upgrade (subscribe page) and Schedule Downgrade (effective next period)
    echo '<div class="mt-3 d-flex flex-wrap gap-2">
        <a href="' . new moodle_url('/local/chapa_subscription/subscribe.php') . '" class="btn btn-primary">
            ' . get_string('upgrade_plan', 'local_chapa_subscription') . '
        </a>
        <a href="' . new moodle_url('/local/chapa_subscription/user/schedule_downgrade.php', array('current_subscription_id' => $current_subscription->id)) . '" class="btn btn-outline-warning">
            ' . get_string('scheduled_downgrade', 'local_chapa_subscription') . '
        </a>
    </div>';
    
} else {
    // No active subscription
    echo $OUTPUT->heading(get_string('no_active_subscription', 'local_chapa_subscription'));
    echo '<div class="alert alert-info">
        <p>' . get_string('no_active_subscription_message', 'local_chapa_subscription') . '</p>
        <a href="' . new moodle_url('/local/chapa_subscription/subscribe.php') . '" class="btn btn-primary">
            ' . get_string('subscribe_now', 'local_chapa_subscription') . '
        </a>
    </div>';
}

// Payment history
if (!empty($payments)) {
    echo '<div class="card mt-4">
        <div class="card-header">
            <h4>' . get_string('payment_history', 'local_chapa_subscription') . '</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>' . get_string('date', 'local_chapa_subscription') . '</th>
                            <th>' . get_string('amount', 'local_chapa_subscription') . '</th>
                            <th>' . get_string('duration', 'local_chapa_subscription') . '</th>
                            <th>' . get_string('discount', 'local_chapa_subscription') . '</th>
                            <th>' . get_string('status', 'local_chapa_subscription') . '</th>
                            <th>' . get_string('payment_method', 'local_chapa_subscription') . '</th>
                        </tr>
                    </thead>
                    <tbody>';
    
    foreach ($payments as $payment) {
        $status_class = '';
        $status_text = '';
        
        switch ($payment->chapa_status) {
            case 'success':
                $status_class = 'badge-success';
                $status_text = get_string('success', 'local_chapa_subscription');
                break;
            case 'pending':
                $status_class = 'badge-warning';
                $status_text = get_string('pending', 'local_chapa_subscription');
                break;
            case 'failed':
                $status_class = 'badge-danger';
                $status_text = get_string('failed', 'local_chapa_subscription');
                break;
            default:
                $status_class = 'badge-secondary';
                $status_text = $payment->chapa_status;
        }
        
        echo '<tr>
            <td>' . date('Y-m-d H:i:s', $payment->created_at) . '</td>
            <td>' . ($payment->amount / 100) . ' ' . $payment->currency . '</td>
            <td>' . $payment->months . ' ' . get_string('months', 'local_chapa_subscription') . '</td>
            <td>' . $payment->discount_percent . '%</td>
            <td><span class="badge ' . $status_class . '">' . $status_text . '</span></td>
            <td>
                ' . ($payment->payment_method ?: '-') . '<br>
                <div class="mt-2">
                    <a class="btn btn-sm btn-outline-secondary" href="' . new moodle_url('/local/chapa_subscription/user/invoice.php', array('payment_id' => $payment->id)) . '">Invoice</a>
                    <a class="btn btn-sm btn-outline-primary" href="' . new moodle_url('/local/chapa_subscription/user/resend_receipt.php', array('payment_id' => $payment->id)) . '">Resend Email</a>
                </div>
            </td>
        </tr>';
    }
    
    echo '</tbody>
                </table>
            </div>
        </div>
    </div>';
}

echo $OUTPUT->footer();
