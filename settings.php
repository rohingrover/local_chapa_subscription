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
 * Settings for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_chapa_subscription',
        get_string('pluginname', 'local_chapa_subscription'),
        new moodle_url('/local/chapa_subscription/admin/settings.php'),
        'moodle/site:config'
    ));
    
    // Add reports page
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_chapa_subscription_reports',
        'Subscription Reports',
        new moodle_url('/local/chapa_subscription/admin/reports.php'),
        'moodle/site:config'
    ));
}

/**
 * Add subscription link to user menu
 */
if (!function_exists('local_chapa_subscription_extend_navigation_user_settings')) {
function local_chapa_subscription_extend_navigation_user_settings($navigation, $user, $usercontext, $course, $coursecontext) {
    global $CFG, $USER;
    
    // Only show for logged in users
    if (!isloggedin() || isguestuser()) {
        return;
    }
    
    // Add subscription link to user menu
    $subscriptionnode = $navigation->add(
        get_string('subscribe', 'local_chapa_subscription'),
        new moodle_url('/local/chapa_subscription/subscribe.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'chapa_subscription',
        new pix_icon('i/settings', '')
    );
    
    // Add my subscriptions link
    $mysubscriptionsnode = $navigation->add(
        get_string('my_subscriptions', 'local_chapa_subscription'),
        new moodle_url('/local/chapa_subscription/user/subscriptions.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'chapa_my_subscriptions',
        new pix_icon('i/portfolio', '')
    );
    
    // Add manage subscription link
    $managesubscriptionnode = $navigation->add(
        'Manage Subscription',
        new moodle_url('/local/chapa_subscription/user/manage_subscription.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'chapa_manage_subscription',
        new pix_icon('i/settings', '')
    );
}
}
