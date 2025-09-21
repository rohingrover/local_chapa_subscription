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

defined('MOODLE_INTERNAL') || die();
function xmldb_local_chapa_subscription_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024120101) {
        // Add reminders table
        $table = new xmldb_table('local_chapa_reminders');
        
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('subscriptionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sent_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('subscriptionid', XMLDB_KEY_FOREIGN, array('subscriptionid'), 'local_chapa_subscriptions', array('id'));
        
        $table->add_index('subscriptionid_type', XMLDB_INDEX_UNIQUE, array('subscriptionid', 'type'));
        
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        upgrade_plugin_savepoint(true, 2024120101, 'local', 'chapa_subscription');
    }

    if ($oldversion < 2024120102) {
        // Insert default plans if they don't exist
        $plans = array(
            array(
                'shortname' => 'basic',
                'fullname' => 'Basic Plan',
                'monthlyprice' => 24900, // 249 birr in cents
                'description' => 'Access to basic courses and features',
                'timecreated' => time(),
                'timemodified' => time()
            ),
            array(
                'shortname' => 'standard',
                'fullname' => 'Standard Plan',
                'monthlyprice' => 29900, // 299 birr in cents
                'description' => 'Access to standard courses and features',
                'timecreated' => time(),
                'timemodified' => time()
            ),
            array(
                'shortname' => 'premium',
                'fullname' => 'Premium Plan',
                'monthlyprice' => 34900, // 349 birr in cents
                'description' => 'Access to all courses and premium features',
                'timecreated' => time(),
                'timemodified' => time()
            )
        );
        
        foreach ($plans as $plan_data) {
            $existing = $DB->get_record('local_chapa_plans', array('shortname' => $plan_data['shortname']));
            if (!$existing) {
                $plan = new stdClass();
                foreach ($plan_data as $key => $value) {
                    $plan->$key = $value;
                }
                $DB->insert_record('local_chapa_plans', $plan);
            }
        }
        
        upgrade_plugin_savepoint(true, 2024120102, 'local', 'chapa_subscription');
    }

    if ($oldversion < 2025091501) {
        // Remove course restrictions table as we're using cohort-based access control
        $table = new xmldb_table('local_chapa_course_restrictions');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }
        
        upgrade_plugin_savepoint(true, 2025091501, 'local', 'chapa_subscription');
    }

    if ($oldversion < 2025091502) {
        // Create downgrade_requests table
        $table = new xmldb_table('local_chapa_downgrade_requests');
        
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('current_plan_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('target_plan_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('requested_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('scheduled_for', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('executed_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('current_plan', XMLDB_KEY_FOREIGN, array('current_plan_id'), 'local_chapa_plans', array('id'));
        $table->add_key('target_plan', XMLDB_KEY_FOREIGN, array('target_plan_id'), 'local_chapa_plans', array('id'));
        
        $table->add_index('userid', XMLDB_INDEX_NOTUNIQUE, array('userid'));
        $table->add_index('status', XMLDB_INDEX_NOTUNIQUE, array('status'));
        
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        upgrade_plugin_savepoint(true, 2025091502, 'local', 'chapa_subscription');
    }

    if ($oldversion < 2025091503) {
        // Add cancelled_at field to downgrade_requests table
        $table = new xmldb_table('local_chapa_downgrade_requests');
        $field = new xmldb_field('cancelled_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'executed_at');
        
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        upgrade_plugin_savepoint(true, 2025091503, 'local', 'chapa_subscription');
    }

    return true;
}
