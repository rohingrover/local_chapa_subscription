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
 * Downgrade success page for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_login();

$subscription_id = required_param('subscription_id', PARAM_INT);

$PAGE->set_url('/local/chapa_subscription/downgrade_success.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Downgrade Successful');
$PAGE->set_heading('Downgrade Successful');

// Get subscription details
$subscription = $DB->get_record('local_chapa_subscriptions', array('id' => $subscription_id));
$current_plan = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid));

// Get downgrade record
$downgrade = $DB->get_record('local_chapa_downgrades', array(
    'subscription_id' => $subscription_id,
    'status' => 'completed'
), '*', 'downgraded_at DESC');

$previous_plan = $DB->get_record('local_chapa_plans', array('id' => $downgrade->from_plan_id));

// Verify user owns the subscription
if ($subscription->userid != $USER->id) {
    throw new moodle_exception('invalid_subscription', 'local_chapa_subscription');
}

echo $OUTPUT->header();

echo '<div class="alert alert-info">';
echo '<h4><i class="fa fa-info-circle"></i> Downgrade Successful!</h4>';
echo '<p>Your subscription has been successfully downgraded from <strong>' . $previous_plan->fullname . '</strong> to <strong>' . $current_plan->fullname . '</strong>.</p>';
echo '<p>Your access has been updated to reflect your new plan features.</p>';
echo '</div>';

echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5>Downgrade Details</h5>';
echo '</div>';
echo '<div class="card-body">';
echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<p><strong>Previous Plan:</strong> ' . $previous_plan->fullname . '</p>';
echo '<p><strong>New Plan:</strong> ' . $current_plan->fullname . '</p>';
echo '</div>';
echo '<div class="col-md-6">';
echo '<p><strong>Downgrade Date:</strong> ' . date('Y-m-d H:i:s', $downgrade->downgraded_at) . '</p>';
echo '<p><strong>Status:</strong> <span class="badge badge-success">Completed</span></p>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="alert alert-warning">';
echo '<h6><i class="fa fa-exclamation-triangle"></i> Important Notes:</h6>';
echo '<ul>';
echo '<li>Some features from your previous plan may no longer be available</li>';
echo '<li>You can upgrade your plan again at any time</li>';
echo '<li>Your next billing cycle will reflect the new plan pricing</li>';
echo '</ul>';
echo '</div>';

echo '<div class="mt-3">';
echo '<a href="/local/chapa_subscription/user/subscriptions.php" class="btn btn-primary">View My Subscriptions</a>';
echo '<a href="/local/chapa_subscription/subscribe.php" class="btn btn-success ml-2">Upgrade Plan</a>';
echo '</div>';

echo $OUTPUT->footer();
