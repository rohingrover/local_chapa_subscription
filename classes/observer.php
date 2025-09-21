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
 * Event observer for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_chapa_subscription;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer class
 */
class observer {
    
    /**
     * Handle course view event
     * 
     * @param \core\event\course_viewed $event
     */
    public static function course_viewed(\core\event\course_viewed $event) {
        global $PAGE, $USER;
        
        // Debug: Test if this observer works
        error_log("CHAPA DEBUG: course_viewed observer triggered for user {$USER->id}");
        
        // Check if user has editing capabilities (skip for teachers/admins)
        if (is_siteadmin($USER->id)) {
            error_log("CHAPA DEBUG: User is site admin, skipping modal");
            return;
        }
        
        // Check if user has any privileged roles
        $course_context = \context_course::instance($event->courseid);
        $has_privileged_role = self::user_has_any_role($course_context, ['manager', 'editingteacher', 'teacher', 'coursecreator', 'teacherassistant'], $USER->id);
        if ($has_privileged_role) {
            error_log("CHAPA DEBUG: User has privileged role, skipping modal");
            return;
        }
        
        // Check if user has only student role
        $is_student_only = self::user_has_only_student_role($course_context, $USER->id);
        if (!$is_student_only) {
            error_log("CHAPA DEBUG: User is not student-only, skipping modal");
            return;
        }
        
        error_log("CHAPA DEBUG: User passed all checks, injecting modal");
        
        // Inject modal assets for students on course pages
        require_once(__DIR__ . '/../lib.php');
        local_chapa_subscription_inject_enhanced_modal_assets();
    }
    
    /**
     * Handle course module view event to inject modal assets
     * 
     * @param \core\event\course_module_viewed $event
     */
	public static function course_module_viewed(\core\event\course_module_viewed $event) {
		global $PAGE, $USER;
		
		// Debug: Log that we're in the observer
		error_log("CHAPA DEBUG: course_module_viewed observer triggered for user {$USER->id}");
		error_log("CHAPA DEBUG: Observer is working - this is a test log");
		
		// Get course id from event
		$courseid = $event->courseid ?? 0;
		error_log("CHAPA DEBUG: Course ID = $courseid");
		
		// Determine the course module id from the event
		$cmid = 0;
		
		// Debug: Log all available properties using proper event data access
		error_log("CHAPA DEBUG: Event data: " . print_r($event->get_data(), true));
		
		// Try different ways to get the CM ID
		$eventdata = $event->get_data();
		if (isset($eventdata['contextinstanceid'])) {
			$cmid = (int)$eventdata['contextinstanceid'];
			error_log("CHAPA DEBUG: Got CM ID from contextinstanceid: $cmid");
		}
		if (!$cmid && isset($eventdata['objectid'])) {
			$cmid = (int)$eventdata['objectid'];
			error_log("CHAPA DEBUG: Got CM ID from objectid: $cmid");
		}
		if (!$cmid && method_exists($event, 'get_context')) {
			$context = $event->get_context();
			if ($context && property_exists($context, 'instanceid')) {
				$cmid = (int)$context->instanceid;
				error_log("CHAPA DEBUG: Got CM ID from context instanceid: $cmid");
			}
		}
		
		// If still no CM ID, try to get it from the URL or other means
		if (!$cmid) {
			global $PAGE;
			if (isset($PAGE->cm) && $PAGE->cm) {
				$cmid = $PAGE->cm->id;
				error_log("CHAPA DEBUG: Got CM ID from PAGE->cm: $cmid");
			}
		}
		
		// Last resort: try to extract from URL
		if (!$cmid) {
			global $_SERVER;
			if (isset($_SERVER['REQUEST_URI'])) {
				$url = $_SERVER['REQUEST_URI'];
				error_log("CHAPA DEBUG: Request URI: $url");
				if (preg_match('/id=(\d+)/', $url, $matches)) {
					$cmid = (int)$matches[1];
					error_log("CHAPA DEBUG: Got CM ID from URL: $cmid");
				}
			}
		}
		error_log("CHAPA DEBUG: CM ID = $cmid");
		if (!$cmid) {
			error_log("CHAPA DEBUG: No CM ID found, returning");
			return;
		}
		
		// Load cm info and check availability for cohort restriction
		$modinfo = \get_fast_modinfo($courseid);
		if (!$modinfo || empty($modinfo->cms[$cmid])) {
			error_log("CHAPA DEBUG: No modinfo or CM not found, returning");
			return;
		}
		$cm = $modinfo->cms[$cmid];
		error_log("CHAPA DEBUG: CM loaded successfully");
		
		// Only show modal to users who have ONLY student role in this course
		$coursecontext = \context_course::instance($courseid);
		$systemcontext = \context_system::instance();
		
		// Check if user is site admin or has system capabilities - never show modal
		if (\is_siteadmin($USER->id) || \has_capability('moodle/site:config', $systemcontext, $USER->id)) {
			error_log("CHAPA DEBUG: User is site admin or has system capabilities, returning");
			return;
		}
		
		// Check course-level capabilities - never show modal
		if (\has_capability('moodle/course:manageactivities', $coursecontext, $USER->id) ||
			\has_capability('moodle/course:update', $coursecontext, $USER->id) ||
			\has_capability('moodle/course:viewhiddenactivities', $coursecontext, $USER->id) ||
			\has_capability('moodle/role:assign', $coursecontext, $USER->id) ||
			\has_capability('moodle/course:ignoreavailabilityrestrictions', $coursecontext, $USER->id)) {
			error_log("CHAPA DEBUG: User has course capabilities, returning");
			return;
		}
		
		// Check if user has any staff roles in system context - never show modal
		$staffroles = array('manager', 'coursecreator', 'editingteacher', 'teacher', 'teacherassistant');
		if (self::user_has_any_role($systemcontext, $staffroles, $USER->id)) {
			error_log("CHAPA DEBUG: User has staff role in system context, returning");
			return;
		}
		
		// Check if user has any staff roles in course context - never show modal
		if (self::user_has_any_role($coursecontext, $staffroles, $USER->id)) {
			error_log("CHAPA DEBUG: User has staff role in course context, returning");
			return;
		}
		
		// Now check if user has ONLY student role in this course
		// If they have student + any other role, don't show modal
		$has_only_student = self::user_has_only_student_role($coursecontext, $USER->id);
		error_log("CHAPA DEBUG: User has only student role: " . ($has_only_student ? 'YES' : 'NO'));
		if (!$has_only_student) {
			error_log("CHAPA DEBUG: User does not have only student role, returning");
			return;
		}

		// Only inject when this module has a cohort-based availability restriction
		$availabilityjson = $cm->availability ?? null;
		$sectionavailability = null;
		$section = $cm->get_modinfo()->get_section_info($cm->sectionnum, MUST_EXIST);
		if ($section && property_exists($section, 'availability')) {
			$sectionavailability = $section->availability;
		}
		
		error_log("CHAPA DEBUG: Module availability: " . ($availabilityjson ? 'YES' : 'NO'));
		error_log("CHAPA DEBUG: Section availability: " . ($sectionavailability ? 'YES' : 'NO'));
		
		$hascohort = false;
		$restriction_type = '';
		
		// Check if restriction is on the activity itself
		if ($availabilityjson && self::availability_has_cohort_condition($availabilityjson)) {
			$hascohort = true;
			$restriction_type = 'activity';
			error_log("CHAPA DEBUG: Module has cohort condition");
		}
		
		// Check if restriction is on the section/topic
		if (!$hascohort && $sectionavailability && self::availability_has_cohort_condition($sectionavailability)) {
			$hascohort = true;
			$restriction_type = 'section';
			error_log("CHAPA DEBUG: Section has cohort condition");
		}
		
		if (!$hascohort) {
			error_log("CHAPA DEBUG: No cohort conditions found, returning");
			return;
		}
		
		error_log("CHAPA DEBUG: Restriction type: $restriction_type");

		// If user can already access the activity, do not inject
		$info = new \core_availability\info_module($cm);
		$information = '';
		$is_available = $info->is_available($information, false, $USER->id);
		error_log("CHAPA DEBUG: User can access activity: " . ($is_available ? 'YES' : 'NO'));
		if ($is_available) {
			error_log("CHAPA DEBUG: User can access activity, returning");
			return;
		}
		
		// Additional check: Only inject if the restriction actually blocks the user
		// Check if the restriction information contains cohort-related text
		if (empty($information) || !preg_match('/belong to|cohort|plan/i', $information)) {
			error_log("CHAPA DEBUG: No cohort restriction information found, returning");
			return;
		}

		error_log("CHAPA DEBUG: Injecting modal JS with restriction type: $restriction_type");
		
		// Pass restriction type to JavaScript
		$PAGE->requires->js_init_code("
			window.chapaRestrictionType = '$restriction_type';
		");
		
		$PAGE->requires->js('/local/chapa_subscription/js/simple_modal.js?v=' . time());
	}
    
    /**
     * Handle any page view to inject modal assets for comprehensive coverage
     * 
     * @param \core\event\base $event
     */
	public static function any_page_viewed(\core\event\base $event) {
		global $USER;
		
		// Debug: Test if this observer works
		error_log("CHAPA DEBUG: any_page_viewed observer triggered for user {$USER->id}");
		
		// Disable global injection to avoid showing on unrelated pages
		return;
	}

	/**
	 * Check recursively whether the availability JSON includes a cohort condition.
	 *
	 * @param string $availabilityjson
	 * @return bool
	 */
	private static function availability_has_cohort_condition($availabilityjson) {
		$data = json_decode($availabilityjson, true);
		if (!$data) {
			return false;
		}
		return self::node_has_cohort($data);
	}

	/**
	 * Recursive helper to find a cohort condition within an availability tree node.
	 *
	 * @param array $node
	 * @return bool
	 */
	private static function node_has_cohort(array $node) {
		// Leaf condition node
		if (isset($node['type']) && $node['type'] === 'cohort') {
			return true;
		}
		// Group node with children in key 'c'
		if (isset($node['c']) && is_array($node['c'])) {
			foreach ($node['c'] as $child) {
				if (is_array($child) && self::node_has_cohort($child)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Check if user has any of the given roles in the context (including parents).
	 *
	 * @param \context $context
	 * @param array $shortnames
	 * @param int $userid
	 * @return bool
	 */
	private static function user_has_any_role($context, array $shortnames, $userid) {
		$roles = \get_user_roles($context, $userid, true);
		foreach ($roles as $ra) {
			if (!empty($ra->shortname) && in_array($ra->shortname, $shortnames, true)) {
				return true;
			}
		}
		
		// Also check if user has any of these roles in parent contexts
		$parentcontext = $context->get_parent_context();
		if ($parentcontext && $parentcontext->id != $context->id) {
			return self::user_has_any_role($parentcontext, $shortnames, $userid);
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
	private static function user_has_only_student_role($context, $userid) {
		$roles = \get_user_roles($context, $userid, true);
		
		error_log("CHAPA DEBUG: User roles in context {$context->id}: " . count($roles));
		foreach ($roles as $role) {
			error_log("CHAPA DEBUG: Role: " . ($role->shortname ?? 'no-shortname'));
		}
		
		// If no roles at all, not a student
		if (empty($roles)) {
			error_log("CHAPA DEBUG: No roles found");
			return false;
		}
		
		// Check if user has exactly one role and it's student
		if (count($roles) == 1) {
			$role = reset($roles);
			$is_student = !empty($role->shortname) && $role->shortname === 'student';
			error_log("CHAPA DEBUG: Single role, is student: " . ($is_student ? 'YES' : 'NO'));
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
		
		error_log("CHAPA DEBUG: Has student: " . ($has_student ? 'YES' : 'NO') . ", Has privileged: " . ($has_privileged ? 'YES' : 'NO'));
		
		// Only show modal if user has student role but no privileged roles
		$result = $has_student && !$has_privileged;
		error_log("CHAPA DEBUG: Final result: " . ($result ? 'YES' : 'NO'));
		return $result;
	}
    
    /**
     * Handle user creation event to assign to Free Preview Cohort
     * 
     * @param \core\event\user_created $event
     */
    public static function user_created(\core\event\user_created $event) {
        global $DB;
        
        $userid = $event->objectid;
        
        // Get Free Preview Cohort ID from settings
        $free_preview_cohort = $DB->get_field('local_chapa_settings', 'value', array('name' => 'free_preview_cohort'));
        
        if ($free_preview_cohort) {
            // Check if user is already in the cohort
            $existing_member = $DB->get_record('cohort_members', array(
                'cohortid' => $free_preview_cohort,
                'userid' => $userid
            ));
            
            if (!$existing_member) {
                // Add user to Free Preview Cohort
                $cohort_member = new \stdClass();
                $cohort_member->cohortid = $free_preview_cohort;
                $cohort_member->userid = $userid;
                $cohort_member->timeadded = time();
                $DB->insert_record('cohort_members', $cohort_member);
                
                // Log the assignment
                error_log("User $userid automatically assigned to Free Preview Cohort $free_preview_cohort");
            }
        }
    }
    
    /**
     * Handle user login event to ensure Free Preview Cohort assignment
     * 
     * @param \core\event\user_loggedin $event
     */
    public static function user_loggedin(\core\event\user_loggedin $event) {
        global $DB, $CFG;
        $userid = $event->objectid;

        // First login redirect to subscribe page (use lastlogin == 0)
        $user = $DB->get_record('user', array('id' => $userid), 'id,lastlogin');
        if ($user && empty($user->lastlogin)) {
            $currenturl = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($currenturl, '/local/chapa_subscription/subscribe.php') === false) {
                redirect(new \moodle_url('/local/chapa_subscription/subscribe.php'));
                return;
            }
        }

        // Ensure Free Preview cohort if no active subscription
        $free_preview_cohort = $DB->get_field('local_chapa_settings', 'value', array('name' => 'free_preview_cohort'));
        if ($free_preview_cohort) {
            $active_subscriptions = $DB->get_records('local_chapa_subscriptions', array('userid' => $userid, 'status' => 'active'));
            if (empty($active_subscriptions)) {
                $existing_member = $DB->get_record('cohort_members', array('cohortid' => $free_preview_cohort, 'userid' => $userid));
                if (!$existing_member) {
                    $cohort_member = new \stdClass();
                    $cohort_member->cohortid = $free_preview_cohort;
                    $cohort_member->userid = $userid;
                    $cohort_member->timeadded = time();
                    $DB->insert_record('cohort_members', $cohort_member);
                }
            }
        }
    }
}
