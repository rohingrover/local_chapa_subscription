// Chapa Subscription Restriction Checker
// This script checks for restricted content and shows modal for students

(function() {
    'use strict';
    
    console.log('CHAPA DEBUG: Restriction checker script loaded');
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    function init() {
        console.log('CHAPA DEBUG: Initializing restriction checker');
        
        // Check if we're on a course page
        if (typeof M !== 'undefined' && M.cfg && M.cfg.courseid) {
            console.log('CHAPA DEBUG: On course page, course ID:', M.cfg.courseid);
        } else {
            console.log('CHAPA DEBUG: Not on course page or M.cfg not available');
            return;
        }
        
        // Check for restricted content indicators
        const restrictedElements = document.querySelectorAll('.dimmed, .fa-lock, [data-availability]');
        console.log('CHAPA DEBUG: Found restricted elements:', restrictedElements.length);
        
        // Check for cohort restriction messages in the entire document
        const allElements = document.querySelectorAll('*');
        let hasCohortRestriction = false;
        let cohortText = '';
        
        // Dynamic cohort names from database (passed from PHP)
        const cohortNames = window.chapaCohortNames || ['Free Preview', 'Basic Plan', 'Standard Plan', 'Premium Plan'];
        console.log('CHAPA DEBUG: Using cohort names:', cohortNames);
        
        for (let element of allElements) {
            const text = element.textContent || '';
            for (let cohortName of cohortNames) {
                if (text.includes(cohortName)) {
                    hasCohortRestriction = true;
                    cohortText = text;
                    console.log('CHAPA DEBUG: Found cohort restriction text for:', cohortName);
                    console.log('CHAPA DEBUG: Full text:', cohortText.substring(0, 100));
                    break;
                }
            }
            if (hasCohortRestriction) break;
        }
        
        if (hasCohortRestriction) {
            console.log('CHAPA DEBUG: Cohort restriction found, checking user permissions');
            
            // Check if user has editing capabilities (skip for teachers/admins)
            const hasEditingCapability = document.querySelector('[data-region="edit-controls"], .editing, .course-edit, .btn-edit');
            if (hasEditingCapability) {
                console.log('CHAPA DEBUG: User has editing capabilities, skipping modal');
                return;
            }
            
            console.log('CHAPA DEBUG: User appears to be student, loading modal');
            
            // Load the modal script
            const script = document.createElement('script');
            script.src = '/local/chapa_subscription/js/simple_modal.js?v=' + Date.now();
            script.onload = function() {
                console.log('CHAPA DEBUG: Modal script loaded successfully');
            };
            script.onerror = function() {
                console.log('CHAPA DEBUG: Error loading modal script');
            };
            document.head.appendChild(script);
        } else {
            console.log('CHAPA DEBUG: No cohort restrictions found');
        }
    }
})();
