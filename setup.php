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
 * Setup script for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Check permissions
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/local/chapa_subscription/setup.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Chapa Subscription Setup');
$PAGE->set_heading('Chapa Subscription Setup');

echo $OUTPUT->header();

echo '<div class="card">
    <div class="card-header">
        <h4>Chapa Subscription Plugin Setup</h4>
    </div>
    <div class="card-body">
        <h5>Installation Steps:</h5>
        <ol>
            <li><strong>Run Moodle Upgrade:</strong>
                <p>Execute the following command in your Moodle root directory:</p>
                <code>php admin/cli/upgrade.php</code>
            </li>
            <li><strong>Configure Settings:</strong>
                <p>Go to <a href="' . new moodle_url('/local/chapa_subscription/admin/settings.php') . '">Admin Settings</a> and configure:</p>
                <ul>
                    <li>Chapa API Keys (Test keys provided in README)</li>
                    <li>Plan prices and discounts</li>
                    <li>Cohort mappings</li>
                    <li>Email templates</li>
                </ul>
            </li>
            <li><strong>Create Cohorts:</strong>
                <p>Create the following cohorts in Moodle:</p>
                <ul>
                    <li>Free Preview</li>
                    <li>Basic Plan</li>
                    <li>Standard Plan</li>
                    <li>Premium Plan</li>
                </ul>
            </li>
            <li><strong>Configure Course Access:</strong>
                <p>Use Moodle's cohort enrollment to control course access. Users will be automatically assigned to cohorts based on their subscription level.</p>
            </li>
            <li><strong>Configure Webhook:</strong>
                <p>Add the following webhook URL to your Chapa dashboard:</p>
                <code>' . $CFG->wwwroot . '/local/chapa_subscription/webhook.php</code>
            </li>
        </ol>
        
        <h5>Test Configuration:</h5>
        <p>Use the provided test keys to test the payment flow:</p>
        <ul>
            <li><strong>Test Public Key:</strong> CHAPUBK_TEST-zsCUh5SGBAR7pEOjwbK1CKAvF9xiQlAD</li>
            <li><strong>Test Secret Key:</strong> CHASECK_TEST-dyKbInlxxqqWxd2tKezFITafEglUUON0</li>
            <li><strong>Encryption Key:</strong> gPWWefsIGQAkH8LmMf5VdGqx</li>
        </ul>
        
        <h5>Plugin Features:</h5>
        <ul>
            <li>✅ Subscription plans (Basic, Standard, Premium)</li>
            <li>✅ Upfront payment discounts (3, 6, 12 months)</li>
            <li>✅ Automatic cohort assignment</li>
            <li>✅ Course access restrictions</li>
            <li>✅ Payment processing via Chapa</li>
            <li>✅ Webhook integration</li>
            <li>✅ Email notifications</li>
            <li>✅ Renewal reminders</li>
            <li>✅ Subscription expiration handling</li>
            <li>✅ User subscription dashboard</li>
            <li>✅ Admin management interface</li>
        </ul>
        
        <div class="mt-4">
            <a href="' . new moodle_url('/local/chapa_subscription/admin/settings.php') . '" class="btn btn-primary">
                Configure Settings
            </a>
            <a href="' . new moodle_url('/local/chapa_subscription/modern_subscribe.php') . '" class="btn btn-secondary">
                Test Subscription Page
            </a>
        </div>
    </div>
</div>';

echo $OUTPUT->footer();
