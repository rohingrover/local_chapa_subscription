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
 * Hooks for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Hook to inject subscription modal assets into course pages
 */
function local_chapa_subscription_before_footer() {
    global $PAGE, $COURSE;
    
    // Only inject on course pages
    if ($PAGE->pagetype === 'course-view' || strpos($PAGE->pagetype, 'mod-') === 0) {
        // Inject modal assets
        local_chapa_subscription_inject_modal_assets();
        
        // Add JavaScript to make cohort restriction messages clickable
        $PAGE->requires->js_amd_inline("
            require(['jquery'], function($) {
                $(document).ready(function() {
                    // Make cohort restriction messages clickable
                    $('.cohort-restriction-message').css('cursor', 'pointer');
                    
                    // Add click handler for cohort restriction messages
                    $(document).on('click', '.cohort-restriction-message', function(e) {
                        e.preventDefault();
                        
                        var \$this = $(this);
                        var isUpgrade = \$this.data('is-upgrade') === 'true';
                        var currentPlan = \$this.data('current-plan') || 'Free Preview';
                        var requiredPlan = \$this.data('required-plan') || 'Basic Plan';
                        
                        // Show subscription modal
                        require(['local_chapa_subscription/subscription_modal'], function(Modal) {
                            Modal.showModal({
                                isUpgrade: isUpgrade,
                                currentPlan: currentPlan,
                                requiredPlan: requiredPlan
                            });
                        });
                    });
                });
            });
        ");
    }
}
