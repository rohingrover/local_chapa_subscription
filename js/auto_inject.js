// Auto-inject modal for Chapa subscription
// This script will be loaded on every page and inject the modal when needed

(function() {
    'use strict';
    
    console.log('CHAPA DEBUG: Auto-inject script loaded');
    console.log('CHAPA DEBUG: Script loaded at:', new Date().toISOString());
    console.log('CHAPA DEBUG: Window location:', window.location.href);
    console.log('CHAPA DEBUG: Document ready state:', document.readyState);
    
    // Wait for DOM to be ready
    function init() {
        // Check if we're on a course page (multiple ways to detect)
        const isCoursePage = (typeof M !== 'undefined' && M.cfg && M.cfg.courseid) || 
                            window.location.pathname.includes('/course/') ||
                            document.querySelector('.course-content, .course-header, .course-info');
        
        if (isCoursePage) {
        console.log('CHAPA DEBUG: On course page');
        
        // Check if user has editing capabilities (skip for teachers/admins)
        const hasEditingCapability = document.querySelector('[data-region="edit-controls"], .editing, .course-edit, .btn-edit');
        if (hasEditingCapability) {
            console.log('CHAPA DEBUG: User has editing capabilities, skipping modal');
            return;
        }
        
        // Check for cohort restriction messages
        const allElements = document.querySelectorAll('*');
        let hasCohortRestriction = false;
        let cohortText = '';
        
        // Check if user is restricted (passed from PHP)
        const isRestricted = window.chapaIsRestricted || false;
        const currentPlan = window.chapaCurrentPlan || 'free';
        console.log('CHAPA DEBUG: User is restricted:', isRestricted);
        console.log('CHAPA DEBUG: User current plan:', currentPlan);
        
        if (isRestricted) {
            hasCohortRestriction = true;
            console.log('CHAPA DEBUG: User is restricted by availability_cohort');
        }
        
        if (hasCohortRestriction) {
            console.log('CHAPA DEBUG: User is restricted, loading modal');
            console.log('CHAPA DEBUG: User current plan:', currentPlan);
            
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
            console.log('CHAPA DEBUG: User is not restricted, no modal needed');
        }
        } else {
            console.log('CHAPA DEBUG: Not on course page or M.cfg not available');
        }
    }
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
