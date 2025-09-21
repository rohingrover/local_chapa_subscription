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
 * Event observers for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = array(
    // User events
    array(
        'eventname' => '\core\event\user_created',
        'callback' => 'local_chapa_subscription\observer::user_created',
    ),
    array(
        'eventname' => '\core\event\user_loggedin',
        'callback' => 'local_chapa_subscription\observer::user_loggedin',
    ),

    // Course events for modal injection
    array(
        'eventname' => '\core\event\course_viewed',
        'callback' => 'local_chapa_subscription\observer::course_viewed',
    ),
    array(
        'eventname' => '\core\event\course_module_viewed',
        'callback' => 'local_chapa_subscription\observer::course_module_viewed',
    ),

    // Comprehensive modal coverage (kept disabled in code but listed for completeness)
    array(
        'eventname' => '\core\event\base',
        'callback' => 'local_chapa_subscription\observer::any_page_viewed',
    ),
);
