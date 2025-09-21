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
 * Library functions for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get user's current subscription
 *
 * @param int $userid User ID
 * @return object|false Subscription object or false if not found
 */
function local_chapa_subscription_get_user_subscription($userid) {
    global $DB;
    
    $subscriptions = $DB->get_records('local_chapa_subscriptions', array(
        'userid' => $userid,
        'status' => 'active'
    ), 'timemodified DESC, id DESC');
    
    return !empty($subscriptions) ? reset($subscriptions) : false;
}

/**
 * Check if user has access to content based on subscription
 *
 * @param int $userid User ID
 * @param string $required_plan Required plan shortname
 * @return bool True if user has access
 */
function local_chapa_subscription_has_access($userid, $required_plan = 'basic') {
    global $DB;
    
    $subscription = local_chapa_subscription_get_user_subscription($userid);
    
    if (!$subscription) {
        return false; // No active subscription
    }
    
    $plan = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid));
    
    if (!$plan) {
        return false;
    }
    
    // Check if subscription is expired
    if ($subscription->endtime < time()) {
        return false;
    }
    
    // Define plan hierarchy
    $plan_hierarchy = array(
        'basic' => 1,
        'standard' => 2,
        'premium' => 3
    );
    
    $user_plan_level = $plan_hierarchy[$plan->shortname] ?? 0;
    $required_plan_level = $plan_hierarchy[$required_plan] ?? 0;
    
    return $user_plan_level >= $required_plan_level;
}

/**
 * Get subscription settings
 *
 * @return array Settings array
 */
function local_chapa_subscription_get_settings() {
    global $DB;
    
    $settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
    $config = array();
    foreach ($settings as $setting) {
        $config[$setting->name] = $setting->value;
    }
    
    return $config;
}

/**
 * Calculate subscription price with discount
 *
 * @param int $monthly_price Monthly price in cents
 * @param int $months Number of months
 * @return array Array with original_total, discount_amount, final_amount, discount_percent
 */
function local_chapa_subscription_calculate_price($monthly_price, $months) {
    $config = local_chapa_subscription_get_settings();
    
    $original_total = $monthly_price * $months;
    $discount_percent = 0;
    
    // Apply discount based on duration
    switch ($months) {
        case 3:
            $discount_percent = $config['discount_3_months'] ?? 0;
            break;
        case 6:
            $discount_percent = $config['discount_6_months'] ?? 0;
            break;
        case 12:
            $discount_percent = $config['discount_12_months'] ?? 0;
            break;
    }
    
    $discount_amount = ($original_total * $discount_percent) / 100;
    $final_amount = $original_total - $discount_amount;
    
    return array(
        'original_total' => $original_total,
        'discount_amount' => $discount_amount,
        'final_amount' => $final_amount,
        'discount_percent' => $discount_percent
    );
}

/**
 * Assign user to cohort based on subscription
 *
 * @param int $userid User ID
 * @param string $plan_shortname Plan shortname
 */
function local_chapa_subscription_assign_cohort($userid, $plan_shortname) {
    global $DB;
    
    $config = local_chapa_subscription_get_settings();
    
    // Remove user from all subscription cohorts
    $cohorts = array(
        $config['basic_cohort'] ?? 0,
        $config['standard_cohort'] ?? 0,
        $config['premium_cohort'] ?? 0
    );
    
    foreach ($cohorts as $cohort_id) {
        if ($cohort_id) {
            $DB->delete_records('cohort_members', array(
                'cohortid' => $cohort_id,
                'userid' => $userid
            ));
        }
    }
    
    // Add user to appropriate cohorts (hierarchical access)
    $cohorts_to_add = array();
    switch ($plan_shortname) {
        case 'basic':
            $cohorts_to_add[] = $config['basic_cohort'] ?? 0;
            break;
        case 'standard':
            $cohorts_to_add[] = $config['basic_cohort'] ?? 0;    // Access to basic content
            $cohorts_to_add[] = $config['standard_cohort'] ?? 0; // Access to standard content
            break;
        case 'premium':
            $cohorts_to_add[] = $config['basic_cohort'] ?? 0;    // Access to basic content
            $cohorts_to_add[] = $config['standard_cohort'] ?? 0; // Access to standard content
            $cohorts_to_add[] = $config['premium_cohort'] ?? 0;  // Access to premium content
            break;
    }
    
    // Add user to all relevant cohorts
    foreach ($cohorts_to_add as $cohort_id) {
        if ($cohort_id) {
            $existing_member = $DB->get_record('cohort_members', array(
                'cohortid' => $cohort_id,
                'userid' => $userid
            ));
            
            if (!$existing_member) {
                $cohort_member = new stdClass();
                $cohort_member->cohortid = $cohort_id;
                $cohort_member->userid = $userid;
                $cohort_member->timeadded = time();
                $DB->insert_record('cohort_members', $cohort_member);
            }
        }
    }
}

/**
 * Move user to free preview cohort
 *
 * @param int $userid User ID
 */
function local_chapa_subscription_move_to_free_preview($userid) {
    global $DB;
    
    $config = local_chapa_subscription_get_settings();
    $free_preview_cohort = $config['free_preview_cohort'] ?? 0;
    
    if ($free_preview_cohort) {
        $existing_member = $DB->get_record('cohort_members', array(
            'cohortid' => $free_preview_cohort,
            'userid' => $userid
        ));
        
        if (!$existing_member) {
            $cohort_member = new stdClass();
            $cohort_member->cohortid = $free_preview_cohort;
            $cohort_member->userid = $userid;
            $cohort_member->timeadded = time();
            $DB->insert_record('cohort_members', $cohort_member);
        }
    }
}

/**
 * Send notification email to user
 *
 * @param int $userid User ID
 * @param string $template_name Template name from settings
 * @param array $replacements Array of placeholder replacements
 */
function local_chapa_subscription_send_notification($userid, $template_name, $replacements = array()) {
    global $DB, $CFG, $SITE;
    
    $user = $DB->get_record('user', array('id' => $userid));
    if (!$user) {
        return false;
    }
    
    $config = local_chapa_subscription_get_settings();
    $template = $config[$template_name] ?? '';
    
    if (!$template) {
        return false;
    }
    
    // Default replacements
    $sitename = !empty($SITE) && !empty($SITE->fullname) ? $SITE->fullname : parse_url($CFG->wwwroot, PHP_URL_HOST);
    $default_replacements = array(
        '{firstname}' => $user->firstname,
        '{lastname}' => $user->lastname,
        '{site}' => $sitename
    );
    
    $replacements = array_merge($default_replacements, $replacements);
    
    $message = str_replace(array_keys($replacements), array_values($replacements), $template);
    $subject = get_string($template_name, 'local_chapa_subscription');
    
    return email_to_user($user, $CFG->noreplyaddress, $subject, $message);
}


/**
 * Get subscription statistics for admin
 *
 * @return array Statistics array
 */
function local_chapa_subscription_get_statistics() {
    global $DB;
    
    $stats = array();
    
    // Total active subscriptions
    $stats['active_subscriptions'] = $DB->count_records('local_chapa_subscriptions', array('status' => 'active'));
    
    // Total revenue
    $revenue = $DB->get_field_sql(
        "SELECT SUM(amount) FROM {local_chapa_payments} WHERE chapa_status = 'success'"
    );
    $stats['total_revenue'] = $revenue ? $revenue / 100 : 0; // Convert from cents
    
    // Subscriptions by plan
    $plan_stats = $DB->get_records_sql(
        "SELECT p.shortname, p.fullname, COUNT(s.id) as count
         FROM {local_chapa_plans} p
         LEFT JOIN {local_chapa_subscriptions} s ON p.id = s.planid AND s.status = 'active'
         GROUP BY p.id, p.shortname, p.fullname"
    );
    $stats['subscriptions_by_plan'] = $plan_stats;
    
    // Monthly revenue
    $monthly_revenue = $DB->get_field_sql(
        "SELECT SUM(amount) FROM {local_chapa_payments} 
         WHERE chapa_status = 'success' 
         AND created_at >= ?",
        array(strtotime('first day of this month'))
    );
    $stats['monthly_revenue'] = $monthly_revenue ? $monthly_revenue / 100 : 0;
    
    return $stats;
}

/**
 * Inject subscription modal JavaScript and CSS into Moodle pages
 */
function local_chapa_subscription_inject_modal_assets() {
    global $PAGE, $CFG, $USER, $DB;
    
    // Compute current user's plan shortname for client logic
    $currentPlanShort = '';
    $subscription = local_chapa_subscription_get_user_subscription($USER->id);
    if ($subscription) {
        $planRec = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid));
        if ($planRec) {
            $currentPlanShort = $planRec->shortname;
        }
    }

    // Get plan prices from admin settings (primary source)
    $settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
    $config_settings = array();
    foreach ($settings as $setting) {
        $config_settings[$setting->name] = $setting->value;
    }
    
    $plan_prices = array();
    $plan_prices['basic'] = ($config_settings['basic_price'] ?? 249); // Admin stores ETB directly
    $plan_prices['standard'] = ($config_settings['standard_price'] ?? 299); // Admin stores ETB directly
    $plan_prices['premium'] = ($config_settings['premium_price'] ?? 349); // Admin stores ETB directly
    
    // Pass config to AMD module
    $config = array(
        'subscribeUrl' => $CFG->wwwroot . '/local/chapa_subscription/modern_subscribe.php',
        'currentPlan' => $currentPlanShort,
        'planHierarchy' => array('basic' => 1, 'standard' => 2, 'premium' => 3),
        'planPrices' => $plan_prices
    );

    $PAGE->requires->js_call_amd('local_chapa_subscription/subscription_modal', 'init', array($config));
    
    // Add CSS
    $PAGE->requires->css('/local/chapa_subscription/styles.css');
}

/**
 * Get user's current subscription plan name
 * 
 * @param int $userid User ID
 * @return string Plan name or 'Free Preview'
 */
function local_chapa_subscription_get_user_plan_name($userid) {
    global $DB;
    
    $subscription = local_chapa_subscription_get_user_subscription($userid);
    
    if (!$subscription) {
        return 'Free Preview';
    }
    
    $plan = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid));
    
    if (!$plan) {
        return 'Free Preview';
    }
    
    return $plan->fullname;
}

/**
 * Get user's primary plan for reporting purposes
 * This determines the highest-tier plan the user is subscribed to
 * 
 * @param int $userid User ID
 * @return string Plan shortname (basic, standard, premium) or 'free'
 */
function local_chapa_subscription_get_user_primary_plan($userid) {
    global $DB;
    
    $subscription = local_chapa_subscription_get_user_subscription($userid);
    
    if (!$subscription) {
        return 'free';
    }
    
    $plan = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid));
    
    if (!$plan) {
        return 'free';
    }
    
    return $plan->shortname;
}

/**
 * Get subscription statistics for admin (handles hierarchical cohorts)
 *
 * @return array Statistics array
 */
function local_chapa_subscription_get_statistics_hierarchical() {
    global $DB;
    
    $stats = array();
    
    // Total active subscriptions
    $stats['active_subscriptions'] = $DB->count_records('local_chapa_subscriptions', array('status' => 'active'));
    
    // Total revenue
    $revenue = $DB->get_field_sql(
        "SELECT SUM(amount) FROM {local_chapa_payments} WHERE chapa_status = 'success'"
    );
    $stats['total_revenue'] = $revenue ? $revenue / 100 : 0; // Convert from cents
    
    // Subscriptions by plan (using primary plan, not cohort membership)
    $plan_stats = $DB->get_records_sql(
        "SELECT p.shortname, p.fullname, COUNT(s.id) as count
         FROM {local_chapa_plans} p
         LEFT JOIN {local_chapa_subscriptions} s ON p.id = s.planid AND s.status = 'active'
         GROUP BY p.id, p.shortname, p.fullname"
    );
    $stats['subscriptions_by_plan'] = $plan_stats;
    
    // Monthly revenue
    $monthly_revenue = $DB->get_field_sql(
        "SELECT SUM(amount) FROM {local_chapa_payments} 
         WHERE chapa_status = 'success' 
         AND created_at >= ?",
        array(strtotime('first day of this month'))
    );
    $stats['monthly_revenue'] = $monthly_revenue ? $monthly_revenue / 100 : 0;
    
    return $stats;
}

/**
 * Check if user needs to upgrade for specific content
 * 
 * @param int $userid User ID
 * @param string $required_plan Required plan shortname
 * @return array Array with 'needs_upgrade' boolean and 'current_plan' string
 */
function local_chapa_subscription_check_upgrade_needed($userid, $required_plan) {
    global $DB;
    
    $subscription = local_chapa_subscription_get_user_subscription($userid);
    
    if (!$subscription) {
        return array(
            'needs_upgrade' => true,
            'current_plan' => 'Free Preview',
            'required_plan' => ucfirst($required_plan) . ' Plan'
        );
    }
    
    $plan = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid));
    
    if (!$plan) {
        return array(
            'needs_upgrade' => true,
            'current_plan' => 'Free Preview',
            'required_plan' => ucfirst($required_plan) . ' Plan'
        );
    }
    
    // Define plan hierarchy
    $plan_hierarchy = array(
        'basic' => 1,
        'standard' => 2,
        'premium' => 3
    );
    
    $user_plan_level = $plan_hierarchy[$plan->shortname] ?? 0;
    $required_plan_level = $plan_hierarchy[$required_plan] ?? 0;
    
    return array(
        'needs_upgrade' => $user_plan_level < $required_plan_level,
        'current_plan' => $plan->fullname,
        'required_plan' => ucfirst($required_plan) . ' Plan'
    );
}

/**
 * Generate cohort restriction message HTML
 * 
 * @param string $required_cohort Required cohort name
 * @param int $userid User ID
 * @return string HTML for restriction message
 */
function local_chapa_subscription_generate_restriction_message($required_cohort, $userid = null) {
    global $USER;
    
    if (!$userid) {
        $userid = $USER->id;
    }
    
    // Get user's current plan
    $current_plan = local_chapa_subscription_get_user_plan_name($userid);
    
    // Determine if this is an upgrade or new subscription
    $is_upgrade = ($current_plan !== 'Free Preview');
    
    // Map cohort names to plan names
    $cohort_plan_map = array(
        'Basic Plan Cohort' => 'Basic',
        'Standard Plan Cohort' => 'Standard', 
        'Premium Plan Cohort' => 'Premium'
    );
    
    $required_plan = $cohort_plan_map[$required_cohort] ?? 'Basic';
    
    $message = '<div class="cohort-restriction-message" ';
    $message .= 'data-is-upgrade="' . ($is_upgrade ? 'true' : 'false') . '" ';
    $message .= 'data-current-plan="' . htmlspecialchars($current_plan) . '" ';
    $message .= 'data-required-plan="' . htmlspecialchars($required_plan) . '">';
    
    $message .= '<div class="restriction-icon">🔒</div>';
    $message .= '<div class="restriction-text">';
    $message .= 'Not available unless: You belong to ' . htmlspecialchars($required_cohort);
    $message .= '</div>';
    
    if ($is_upgrade) {
        $message .= '<div class="upgrade-text">Click to upgrade your plan to access this content</div>';
    } else {
        $message .= '<div class="upgrade-text">Click to subscribe and access this content</div>';
    }
    
    $message .= '</div>';
    
    return $message;
}

/**
 * Inject enhanced modal assets for comprehensive access control
 */
function local_chapa_subscription_inject_enhanced_modal_assets() {
    global $PAGE, $USER, $DB;
    
    // Debug: Log that we're in the function
    // Debug removed
    
    // Only inject on course pages and for logged-in users
    if (!isloggedin() || !$PAGE->context || $PAGE->context->contextlevel != CONTEXT_COURSE) {
        
        return;
    }
    
    // Skip for privileged users (site admins/managers/teachers etc.)
    $coursecontext = $PAGE->context;
    $systemcontext = \context_system::instance();
    
    // Direct check for site admin - never show modal
    if (\is_siteadmin($USER->id)) {
        
        return;
    }
    
    // Check system-level capabilities - never show modal
    if (\has_capability('moodle/site:config', $systemcontext, $USER->id)) {
        
        return;
    }
    
    // Check course-level capabilities - never show modal
    if (\has_capability('moodle/course:manageactivities', $coursecontext, $USER->id) ||
        \has_capability('moodle/course:update', $coursecontext, $USER->id) ||
        \has_capability('moodle/course:viewhiddenactivities', $coursecontext, $USER->id) ||
        \has_capability('moodle/role:assign', $coursecontext, $USER->id) ||
        \has_capability('moodle/course:ignoreavailabilityrestrictions', $coursecontext, $USER->id)) {
        
        return;
    }
    
    // Check if user has any staff roles in system context - never show modal
    $staffroles = array('manager', 'coursecreator', 'editingteacher', 'teacher', 'teacherassistant');
    if (local_chapa_subscription_user_has_any_role($systemcontext, $staffroles, $USER->id)) {
        
        return;
    }
    
    // Check if user has any staff roles in course context - never show modal
    if (local_chapa_subscription_user_has_any_role($coursecontext, $staffroles, $USER->id)) {
        
        return;
    }
    
    // Now check if user has ONLY student role in this course
    if (!local_chapa_subscription_user_has_only_student_role($coursecontext, $USER->id)) {
        
        return;
    }
    
    
    
    // Get cohort names from database for dynamic detection
    $cohort_settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
    $cohort_names = array();
    
    // Get cohort names from database
    if (isset($cohort_settings['basic_cohort'])) {
        $basic_cohort = $DB->get_record('cohort', array('id' => $cohort_settings['basic_cohort']->value));
        if ($basic_cohort) $cohort_names[] = $basic_cohort->name;
    }
    if (isset($cohort_settings['standard_cohort'])) {
        $standard_cohort = $DB->get_record('cohort', array('id' => $cohort_settings['standard_cohort']->value));
        if ($standard_cohort) $cohort_names[] = $standard_cohort->name;
    }
    if (isset($cohort_settings['premium_cohort'])) {
        $premium_cohort = $DB->get_record('cohort', array('id' => $cohort_settings['premium_cohort']->value));
        if ($premium_cohort) $cohort_names[] = $premium_cohort->name;
    }
    if (isset($cohort_settings['free_preview_cohort'])) {
        $free_cohort = $DB->get_record('cohort', array('id' => $cohort_settings['free_preview_cohort']->value));
        if ($free_cohort) $cohort_names[] = $free_cohort->name;
    }
    
    
    
    // Get user's current plan from their cohort membership
    if (!empty($cohort_names)) {
        $placeholders = str_repeat('?,', count($cohort_names) - 1) . '?';
        $user_cohorts = $DB->get_records_sql("
            SELECT c.name, c.id
            FROM {cohort} c
            JOIN {cohort_members} cm ON c.id = cm.cohortid
            WHERE cm.userid = ? AND c.name IN (" . $placeholders . ")
        ", array_merge([$USER->id], $cohort_names));
    } else {
        $user_cohorts = [];
    }
    
    $current_plan = 'free'; // Default
    if (!empty($user_cohorts)) {
        // Get the highest tier plan the user has
        $plan_hierarchy = ['Free Preview' => 0, 'Basic Plan' => 1, 'Standard Plan' => 2, 'Premium Plan' => 3];
        $highest_tier = 0;
        foreach ($user_cohorts as $cohort) {
            if (isset($plan_hierarchy[$cohort->name]) && $plan_hierarchy[$cohort->name] > $highest_tier) {
                $current_plan = $cohort->name;
                $highest_tier = $plan_hierarchy[$cohort->name];
            }
        }
        
        // If user has Premium Plan, they should have access to all lower tiers
        if ($current_plan === 'Premium Plan') {
            
        }
    }
    
    
    
    // Check if user is restricted by availability_cohort
    $is_restricted = false;
    
    
    if ($PAGE->cm && $PAGE->cm->id) {
        
        $cm = get_coursemodule_from_id('', $PAGE->cm->id);
        if ($cm && isset($cm->availability) && !empty($cm->availability)) {
            
            $availability_info = \core_availability\info_module::is_available($cm, true, $USER->id);
            if (!$availability_info) {
                $is_restricted = true;
                
            } else {
                
            }
        } else {
            
        }
    } else {
        
        // Check if user is restricted at course level
        $course_context = \context_course::instance($PAGE->course->id);
        if (has_capability('moodle/course:view', $course_context, $USER->id)) {
            
        } else {
            
            $is_restricted = true;
        }
    }
    
    // Get plan prices from admin settings (primary source)
    $settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
    $config_settings = array();
    foreach ($settings as $setting) {
        $config_settings[$setting->name] = $setting->value;
    }
    
    $plan_prices = array();
    $plan_prices['basic'] = ($config_settings['basic_price'] ?? 249); // Admin stores ETB directly
    $plan_prices['standard'] = ($config_settings['standard_price'] ?? 299); // Admin stores ETB directly
    $plan_prices['premium'] = ($config_settings['premium_price'] ?? 349); // Admin stores ETB directly
    
    // Pass cohort names, user's current plan, restriction status, and plan prices to JavaScript
    $PAGE->requires->js_init_code('
        window.chapaCohortNames = ' . json_encode($cohort_names) . ';
        window.chapaCurrentPlan = "' . $current_plan . '";
        window.chapaIsRestricted = ' . ($is_restricted ? 'true' : 'false') . ';
        window.chapaPlanPrices = ' . json_encode($plan_prices) . ';
        console.log("CHAPA DEBUG: Cohort names passed to JS:", window.chapaCohortNames);
        console.log("CHAPA DEBUG: User current plan:", window.chapaCurrentPlan);
        console.log("CHAPA DEBUG: User is restricted:", window.chapaIsRestricted);
        console.log("CHAPA DEBUG: Plan prices:", window.chapaPlanPrices);
        console.log("CHAPA DEBUG: Auto-inject script will be loaded");
    ');
    
    // Inject auto-inject script
    $PAGE->requires->js('/local/chapa_subscription/js/auto_inject.js');
    
    
}

/**
 * Check if user has any of the given roles in the context (including parents).
 *
 * @param \context $context
 * @param array $shortnames
 * @param int $userid
 * @return bool
 */
function local_chapa_subscription_user_has_any_role($context, array $shortnames, $userid) {
    $roles = \get_user_roles($context, $userid, true);
    foreach ($roles as $ra) {
        if (!empty($ra->shortname) && in_array($ra->shortname, $shortnames, true)) {
            return true;
        }
    }
    
    // Also check if user has any of these roles in parent contexts
    $parentcontext = $context->get_parent_context();
    if ($parentcontext && $parentcontext->id != $context->id) {
        return local_chapa_subscription_user_has_any_role($parentcontext, $shortnames, $userid);
    }
    
    return false;
}

/**
 * Check if user has ONLY student role in the given context.
 * Returns true if user has student role and no other roles.
 *
 * @param \context $context
 * @param int $userid
 * @return bool
 */
function local_chapa_subscription_user_has_only_student_role($context, $userid) {
    $roles = \get_user_roles($context, $userid, true);
    
    
    foreach ($roles as $role) {
        
    }
    
    // If no roles at all, not a student
    if (empty($roles)) {
        
        return false;
    }
    
    // Check if user has exactly one role and it's student
    if (count($roles) == 1) {
        $role = reset($roles);
        $is_student = !empty($role->shortname) && $role->shortname === 'student';
        
        return $is_student;
    }
    
    // If user has multiple roles, check if one is student and others are not privileged
    $has_student = false;
    $has_privileged = false;
    $privileged_roles = array('manager', 'coursecreator', 'editingteacher', 'teacher', 'teacherassistant');
    
    foreach ($roles as $ra) {
        if (!empty($ra->shortname)) {
            if ($ra->shortname === 'student') {
                $has_student = true;
            } elseif (in_array($ra->shortname, $privileged_roles, true)) {
                $has_privileged = true;
            }
        }
    }
    
    
    
    // Only show modal if user has student role but no privileged roles
    $result = $has_student && !$has_privileged;
    
    return $result;
}

/**
 * New hook system for before footer
 */
function local_chapa_subscription_before_footer_html_generation(\core\hook\output\before_footer_html_generation $hook) {
    local_chapa_subscription_inject_enhanced_modal_assets();
}

/**
 * Hook for course page to inject modal assets
 */
function local_chapa_subscription_course_page() {
    local_chapa_subscription_inject_enhanced_modal_assets();
}


/**
 * Hook to inject modal assets on course page
 */
function local_chapa_subscription_course_content_after_activity() {
    local_chapa_subscription_inject_enhanced_modal_assets();
}

/**
 * Handle cohort transitions when user purchases a plan
 * 
 * @param int $userid User ID
 * @param int $planid Plan ID
 * @param string $action 'upgrade', 'downgrade', or 'new_subscription'
 */
function local_chapa_subscription_handle_cohort_transition($userid, $planid, $action = 'new_subscription') {
    global $DB;
    
    // Get plan details
    $plan = $DB->get_record('local_chapa_plans', array('id' => $planid));
    if (!$plan) {
        return false;
    }
    
    // Get settings
    $settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
    $config = array();
    foreach ($settings as $setting) {
        $config[$setting->name] = $setting->value;
    }
    
    // Determine target cohorts based on plan (hierarchical access)
    $cohorts_to_add = array();
    switch ($plan->shortname) {
        case 'basic':
            $cohorts_to_add[] = $config['basic_cohort'];
            break;
        case 'standard':
            $cohorts_to_add[] = $config['basic_cohort'];    // Access to basic content
            $cohorts_to_add[] = $config['standard_cohort']; // Access to standard content
            break;
        case 'premium':
            $cohorts_to_add[] = $config['basic_cohort'];    // Access to basic content
            $cohorts_to_add[] = $config['standard_cohort']; // Access to standard content
            $cohorts_to_add[] = $config['premium_cohort'];  // Access to premium content
            break;
    }
    
    // Remove from Free Preview Cohort
    $free_preview_cohort = $config['free_preview_cohort'];
    if ($free_preview_cohort) {
        $DB->delete_records('cohort_members', array(
            'cohortid' => $free_preview_cohort,
            'userid' => $userid
        ));
        
    }
    
    // Remove from all subscription cohorts first
    $subscription_cohorts = array(
        $config['basic_cohort'],
        $config['standard_cohort'],
        $config['premium_cohort']
    );
    
    foreach ($subscription_cohorts as $cohort_id) {
        if ($cohort_id) {
            $DB->delete_records('cohort_members', array(
                'cohortid' => $cohort_id,
                'userid' => $userid
            ));
        }
    }
    
    // Add to all relevant cohorts (hierarchical access)
    foreach ($cohorts_to_add as $cohort_id) {
        if ($cohort_id) {
            $existing_member = $DB->get_record('cohort_members', array(
                'cohortid' => $cohort_id,
                'userid' => $userid
            ));
            
            if (!$existing_member) {
                $cohort_member = new stdClass();
                $cohort_member->cohortid = $cohort_id;
                $cohort_member->userid = $userid;
                $cohort_member->timeadded = time();
                $DB->insert_record('cohort_members', $cohort_member);
                
                
            }
        }
    }
    
    return true;
}

/**
 * Ensure all existing users are in Free Preview Cohort
 * This function can be run manually or via CLI
 */
function local_chapa_subscription_assign_existing_users_to_free_cohort() {
    global $DB;
    
    // Get Free Preview Cohort ID
    $free_preview_cohort = $DB->get_field('local_chapa_settings', 'value', array('name' => 'free_preview_cohort'));
    if (!$free_preview_cohort) {
        
        return false;
    }
    
    // Get all users who don't have active subscriptions (including suspended users)
    $sql = "SELECT u.id 
            FROM {user} u 
            LEFT JOIN {local_chapa_subscriptions} s ON u.id = s.userid AND s.status = 'active'
            WHERE u.deleted = 0 
            AND s.id IS NULL
            AND u.id > 1"; // Exclude guest user (include suspended users)
    
    $users = $DB->get_records_sql($sql);
    $assigned_count = 0;
    
    foreach ($users as $user) {
        // Check if user is already in Free Preview Cohort
        $existing_member = $DB->get_record('cohort_members', array(
            'cohortid' => $free_preview_cohort,
            'userid' => $user->id
        ));
        
        if (!$existing_member) {
            // Add user to Free Preview Cohort
            $cohort_member = new stdClass();
            $cohort_member->cohortid = $free_preview_cohort;
            $cohort_member->userid = $user->id;
            $cohort_member->timeadded = time();
            $DB->insert_record('cohort_members', $cohort_member);
            $assigned_count++;
        }
    }
    
    
    return $assigned_count;
}

/**
 * Send a payment receipt email to the user for a given payment.
 *
 * @param int $paymentid Payment record ID
 * @return bool True if sent, false otherwise
 */
function local_chapa_subscription_send_receipt($paymentid) {
    global $DB, $CFG, $SITE;

    $payment = $DB->get_record('local_chapa_payments', array('id' => $paymentid));
    if (!$payment) {
        return false;
    }

    $subscription = $DB->get_record('local_chapa_subscriptions', array('id' => $payment->subscriptionid));
    if (!$subscription) {
        return false;
    }

    $user = $DB->get_record('user', array('id' => $subscription->userid));
    $plan = $DB->get_record('local_chapa_plans', array('id' => $subscription->planid));
    if (!$user || !$plan) {
        return false;
    }

    $amount = number_format(($payment->amount / 100), 2) . ' ' . $payment->currency;
    $duration = !empty($payment->months) ? $payment->months . ' month' . ($payment->months > 1 ? 's' : '') : '1 month';
    $discount = isset($payment->discount_percent) ? $payment->discount_percent . '%' : '0%';
    $method = !empty($payment->payment_method) ? $payment->payment_method : 'Chapa';
    $reference = !empty($payment->chapa_txn_id) ? $payment->chapa_txn_id : '-';

    $subject = 'Payment receipt - ' . $plan->fullname;
    $invoiceurl = new moodle_url('/local/chapa_subscription/user/invoice.php', array('payment_id' => $payment->id));

    // Load optional HTML template from admin settings
    $config = local_chapa_subscription_get_settings();
    $template = $config['receipt_email_template'] ?? '';

    if ($template) {
        // Build replacements
    $sitename = !empty($SITE) && !empty($SITE->fullname) ? $SITE->fullname : parse_url($CFG->wwwroot, PHP_URL_HOST);
    $replacements = array(
            '{firstname}' => $user->firstname,
            '{lastname}' => $user->lastname,
            '{plan}' => $plan->fullname,
            '{amount}' => number_format(($payment->amount / 100), 2),
            '{currency}' => s($payment->currency),
            '{duration}' => $duration,
            '{discount}' => $discount,
            '{method}' => $method,
            '{reference}' => $reference,
            '{date}' => userdate($payment->created_at),
            '{invoice_url}' => (string)$invoiceurl,
        '{site}' => $sitename,
        );
        $htmlmessage = str_replace(array_keys($replacements), array_values($replacements), $template);
        // Send HTML as plain text fallbacks too
        $plain = strip_tags(str_replace(array('<br>','<br/>','<br />'), "\n", $htmlmessage));
        return email_to_user($user, $CFG->noreplyaddress, $subject, $plain, $htmlmessage);
    }

    // Fallback: simple text email
    $lines = array();
    $lines[] = 'Hello ' . $user->firstname . ',';
    $lines[] = '';
    $lines[] = 'Thank you for your payment. Here are your receipt details:';
    $lines[] = 'Plan: ' . $plan->fullname;
    $lines[] = 'Amount: ' . $amount;
    $lines[] = 'Duration: ' . $duration;
    $lines[] = 'Discount: ' . $discount;
    $lines[] = 'Payment Method: ' . $method;
    $lines[] = 'Reference: ' . $reference;
    $lines[] = 'Date: ' . userdate($payment->created_at);
    $lines[] = '';
    $lines[] = 'You can view or download your invoice here:';
    $lines[] = (string)$invoiceurl;
    $lines[] = '';
    $lines[] = 'Regards,';
    $lines[] = $sitename;

    $message = implode("\n", $lines);
    return email_to_user($user, $CFG->noreplyaddress, $subject, $message);
}
