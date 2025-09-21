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
 * User schedule downgrade page for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_login();

$current_subscription_id = required_param('current_subscription_id', PARAM_INT);
$target_plan_id = optional_param('target_plan_id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_url('/local/chapa_subscription/user/schedule_downgrade.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Schedule Downgrade');
$PAGE->set_heading('Schedule Downgrade');

// Navigation
$PAGE->navbar->add(get_string('pluginname', 'local_chapa_subscription'));
$PAGE->navbar->add('Manage Subscription');
$PAGE->navbar->add('Schedule Downgrade');

// Get subscription and plan details
$current_subscription = $DB->get_record('local_chapa_subscriptions', array('id' => $current_subscription_id));
if (!$current_subscription || $current_subscription->userid != $USER->id) {
    throw new moodle_exception('invalid_subscription', 'local_chapa_subscription');
}

$current_plan = $DB->get_record('local_chapa_plans', array('id' => $current_subscription->planid));
if (!$current_plan) {
    throw new moodle_exception('invalid_plan', 'local_chapa_subscription');
}

// Check if user is on the lowest tier plan (Basic Plan) - no downgrade available
if ($current_plan->shortname === 'basic') {
    echo $OUTPUT->header();
    echo '<div class="alert alert-info">';
    echo '<h4>Downgrade Not Available</h4>';
    echo '<p>You are currently on the <strong>' . $current_plan->fullname . '</strong>, which is the lowest tier plan. There are no lower plans available for downgrade.</p>';
    echo '<p>If you need to cancel your subscription, please use the cancellation option instead.</p>';
    echo '<div class="mt-3">';
    echo '<a href="manage_subscription.php" class="btn btn-primary">Back to Subscription Management</a>';
    echo '</div>';
    echo '</div>';
    echo $OUTPUT->footer();
    exit;
}
if (!$target_plan_id) {
    $lower_plans = $DB->get_records_select('local_chapa_plans', 'monthlyprice < :price', array('price' => $current_plan->monthlyprice), 'monthlyprice ASC');
} else {
    $target_plan = $DB->get_record('local_chapa_plans', array('id' => $target_plan_id));
    if (!$target_plan) {
        throw new moodle_exception('invalid_plan', 'local_chapa_subscription');
    }
}

// Handle downgrade scheduling
if ($confirm && confirm_sesskey()) {
    // Check if downgrade is already scheduled
    $existing_request = $DB->get_record('local_chapa_downgrade_requests', array(
        'userid' => $USER->id,
        'status' => 'pending'
    ));
    
    if ($existing_request) {
        redirect(new moodle_url('/local/chapa_subscription/user/manage_subscription.php'), 
            'You already have a pending downgrade request', 
            null, \core\output\notification::NOTIFY_WARNING);
    }
    
    // Schedule downgrade for next billing period
    $downgrade_request = new stdClass();
    $downgrade_request->userid = $USER->id;
    $downgrade_request->current_plan_id = $current_subscription->planid;
    $downgrade_request->target_plan_id = $target_plan_id;
    $downgrade_request->requested_at = time();
    $downgrade_request->scheduled_for = $current_subscription->endtime;
    $downgrade_request->status = 'pending';
    $DB->insert_record('local_chapa_downgrade_requests', $downgrade_request);
    
    redirect(new moodle_url('/local/chapa_subscription/user/manage_subscription.php'), 
        'Downgrade scheduled for ' . date('Y-m-d', $current_subscription->endtime), 
        null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

// Confirmation form
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5><i class="fa fa-arrow-down"></i> Schedule Downgrade</h5>';
echo '</div>';
echo '<div class="card-body">';

echo '<div class="alert alert-info">';
echo '<h6><i class="fa fa-info-circle"></i> Downgrade Information</h6>';
echo '<p>Your downgrade will be scheduled for the next billing date to avoid interrupting your current subscription period.</p>';
echo '</div>';

echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<h6>Current Plan:</h6>';
echo '<div class="card bg-light">';
echo '<div class="card-body">';
echo '<h6>' . $current_plan->fullname . '</h6>';
echo '<p class="mb-1"><strong>Price:</strong> ' . number_format($current_plan->monthlyprice / 100, 0) . ' ETB/month</p>';
echo '<p class="mb-0"><strong>Next Billing:</strong> ' . date('Y-m-d', $current_subscription->endtime) . '</p>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-6">';
echo '<h6>Target Plan:</h6>';
if (!$target_plan_id) {
    echo '<form method="get" class="mb-3">';
    echo '<input type="hidden" name="current_subscription_id" value="' . (int)$current_subscription_id . '">';
    echo '<div class="form-group">';
    echo '<select name="target_plan_id" class="form-control" required>';
    foreach ($lower_plans as $lp) {
        echo '<option value="' . (int)$lp->id . '">' . format_string($lp->fullname) . ' - ' . number_format($lp->monthlyprice/100, 0) . ' ETB/month</option>';
    }
    echo '</select>';
    echo '</div>';
    echo '<button type="submit" class="btn btn-primary">Select Plan</button>';
    echo '</form>';
} else {
    echo '<div class="card bg-light">';
    echo '<div class="card-body">';
    echo '<h6>' . $target_plan->fullname . '</h6>';
    echo '<p class="mb-1"><strong>Price:</strong> ' . number_format($target_plan->monthlyprice / 100, 0) . ' ETB/month</p>';
    echo '<p class="mb-0"><strong>Savings:</strong> ' . number_format(($current_plan->monthlyprice - $target_plan->monthlyprice) / 100, 0) . ' ETB/month</p>';
    echo '</div>';
    echo '</div>';
}
echo '</div>';
echo '</div>';

// Add features comparison section
if (isset($target_plan) && $target_plan) {
    // Define plan features (same as subscribe.php)
    $plan_features = array(
        'basic' => array(
            'features' => array(
                'Full access to video lessons',
                'Short notes',
                'Basic support'
            ),
            'exclusions' => array(
                'No access to AI assistant',
                'No Question Bank',
                'No review/entrance exam videos',
                'No special Telegram channel'
            )
        ),
        'standard' => array(
            'features' => array(
                'Full access to video lessons',
                'Short notes',
                'Access to AI assistant',
                'Review Question Videos',
                'Entrance Exam Question Videos',
                'Question Bank'
            ),
            'exclusions' => array(
                'No special Telegram channel',
                'No tailored question responses'
            )
        ),
        'premium' => array(
            'features' => array(
                'Full access to video lessons',
                'Short notes',
                'Access to AI assistant',
                'Review Question Videos',
                'Entrance Exam Question Videos',
                'Question Bank',
                'Access to special Telegram channel',
                'Ability to forward questions and receive tailored responses',
                'Priority support'
            ),
            'exclusions' => array()
        )
    );
    
    $current_features = isset($plan_features[$current_plan->shortname]) ? $plan_features[$current_plan->shortname] : array();
    $target_features = isset($plan_features[$target_plan->shortname]) ? $plan_features[$target_plan->shortname] : array();
    
    // Calculate features that will be lost
    $features_to_lose = array();
    if (isset($current_features['features']) && isset($target_features['features'])) {
        $features_to_lose = array_diff($current_features['features'], $target_features['features']);
    }
    
    if (!empty($features_to_lose)) {
        echo '<div class="mt-4">';
        echo '<h6 class="text-danger">⚠️ Features You\'ll Lose After Downgrade:</h6>';
        echo '<div class="alert alert-danger">';
        echo '<ul class="mb-0">';
        foreach ($features_to_lose as $feature) {
            echo '<li><i class="fa fa-times text-danger"></i> ' . $feature . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '</div>';
    }
    
    // Show features they'll keep
    if (isset($target_features['features']) && !empty($target_features['features'])) {
        echo '<div class="mt-3">';
        echo '<h6 class="text-success">✅ Features You\'ll Keep:</h6>';
        echo '<div class="alert alert-success">';
        echo '<ul class="mb-0">';
        foreach ($target_features['features'] as $feature) {
            echo '<li><i class="fa fa-check text-success"></i> ' . $feature . '</li>';
        }
        echo '</ul>';
        echo '</div>';
        echo '</div>';
    }
}

echo '<div class="mt-4">';
echo '<h6>Downgrade Schedule:</h6>';
echo '<div class="alert alert-warning">';
echo '<ul class="mb-0">';
echo '<li><strong>Effective Date:</strong> ' . date('Y-m-d', $current_subscription->endtime) . '</li>';
echo '<li><strong>Current Access:</strong> You will keep all current features until the downgrade date</li>';
if (isset($target_plan) && $target_plan) {
    echo '<li><strong>After Downgrade:</strong> You will have access to ' . $target_plan->fullname . ' features</li>';
} else {
    echo '<li><strong>After Downgrade:</strong> You will have access to lower-tier features</li>';
}
echo '<li><strong>Cancellation:</strong> You can cancel this downgrade request anytime before the effective date</li>';
echo '</ul>';
echo '</div>';
echo '</div>';

if ($target_plan_id) {
    // Check if user is on the lowest tier plan (Basic Plan)
    $is_lowest_tier = ($current_plan->shortname === 'basic');
    
    echo '<div class="mt-4">';
    if ($is_lowest_tier) {
        echo '<div class="alert alert-info">';
        echo '<h6>Downgrade Not Available</h6>';
        echo '<p>You are currently on the Basic Plan, which is the lowest tier. There are no lower plans available for downgrade.</p>';
        echo '<p>If you need to cancel your subscription, please use the cancellation option instead.</p>';
        echo '</div>';
        echo '<a href="manage_subscription.php" class="btn btn-secondary">Back to Subscription Management</a>';
    } else {
        echo '<h6>Are you sure you want to schedule this downgrade?</h6>';
        echo '<form method="post">';
        echo '<input type="hidden" name="confirm" value="1">';
        echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
        echo '<div class="d-flex gap-3">';
        echo '<button type="submit" class="btn btn-warning" onclick="return confirm(\'Are you sure you want to schedule this downgrade?\')">Yes, Schedule Downgrade</button>';
        echo '<a href="manage_subscription.php" class="btn btn-secondary">No, Keep Current Plan</a>';
        echo '</div>';
        echo '</form>';
    }
    echo '</div>';
}

echo '</div>';
echo '</div>';

echo $OUTPUT->footer();
