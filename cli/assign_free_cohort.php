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
 * CLI script to assign existing users to Free Preview Cohort
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Get CLI options
list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'dry-run' => false,
    ),
    array(
        'h' => 'help',
        'd' => 'dry-run',
    )
);

if ($options['help']) {
    $help = "Assign existing users to Free Preview Cohort

Options:
-h, --help          Print out this help
-d, --dry-run       Show what would be done without making changes

Example:
\$sudo -u www-data /usr/bin/php local/chapa_subscription/cli/assign_free_cohort.php
\$sudo -u www-data /usr/bin/php local/chapa_subscription/cli/assign_free_cohort.php --dry-run
";

    echo $help;
    exit(0);
}

// Check if Free Preview Cohort is configured
$free_preview_cohort = $DB->get_field('local_chapa_settings', 'value', array('name' => 'free_preview_cohort'));
if (!$free_preview_cohort) {
    cli_error("Free Preview Cohort not configured. Please set 'free_preview_cohort' in plugin settings.");
}

cli_heading("Assigning users to Free Preview Cohort (ID: $free_preview_cohort)");

if ($options['dry-run']) {
    cli_writeln("DRY RUN MODE - No changes will be made");
}

// Get all users who don't have active subscriptions
$sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email
        FROM {user} u 
        LEFT JOIN {local_chapa_subscriptions} s ON u.id = s.userid AND s.status = 'active'
        WHERE u.deleted = 0 
        AND s.id IS NULL
        AND u.id > 1"; // Exclude guest user (include suspended users)

$users = $DB->get_records_sql($sql);
$total_users = count($users);
$assigned_count = 0;
$already_assigned = 0;

cli_writeln("Found $total_users users without active subscriptions");

foreach ($users as $user) {
    // Check if user is already in Free Preview Cohort
    $existing_member = $DB->get_record('cohort_members', array(
        'cohortid' => $free_preview_cohort,
        'userid' => $user->id
    ));
    
    if ($existing_member) {
        $already_assigned++;
        cli_writeln("User {$user->username} ({$user->email}) already in Free Preview Cohort");
    } else {
        if (!$options['dry-run']) {
            // Add user to Free Preview Cohort
            $cohort_member = new stdClass();
            $cohort_member->cohortid = $free_preview_cohort;
            $cohort_member->userid = $user->id;
            $cohort_member->timeadded = time();
            $DB->insert_record('cohort_members', $cohort_member);
        }
        $assigned_count++;
        cli_writeln("User {$user->username} ({$user->email}) " . ($options['dry-run'] ? 'would be' : '') . " assigned to Free Preview Cohort");
    }
}

cli_heading("Summary");
cli_writeln("Total users processed: $total_users");
cli_writeln("Already in Free Preview Cohort: $already_assigned");
if ($options['dry-run']) {
    cli_writeln("Would assign to Free Preview Cohort: $assigned_count");
} else {
    cli_writeln("Assigned to Free Preview Cohort: $assigned_count");
}

cli_writeln("Done!");
