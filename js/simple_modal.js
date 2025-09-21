/**
 * Simple subscription modal without AMD dependencies
 * Version: 2025-09-20 - Enhanced activity click detection
 */

// Function to get appropriate restriction message based on type
function getRestrictionMessage(options) {
    var restrictionType = window.chapaRestrictionType || 'activity';
    var contentName = options.contentName || 'this content';
    var requiredPlan = options.requiredPlan || '';
    var currentPlan = options.currentPlan || '';

    // Prefer plan-specific messaging if we detected a required plan
    if (requiredPlan) {
        if (restrictionType === 'section') {
            return `To access the <strong>${contentName}</strong> section and all its content, you need the <strong>${requiredPlan}</strong>.`;
        } else {
            return `To access <strong>${contentName}</strong>, you need the <strong>${requiredPlan}</strong>.`;
        }
    }

    // Fallback generic message
    if (restrictionType === 'section') {
        return `To access the <strong>${contentName}</strong> section and all its content, you need an active subscription.`;
    } else {
        return `To access <strong>${contentName}</strong>, you need an active subscription.`;
    }
}

// Simple modal implementation
function showSubscriptionModal(options) {
    // console.debug('showSubscriptionModal options:', options);
    
    // Create simple modal HTML
    var modalHtml = `
        <div id="subscription-modal" class="modal fade" tabindex="-1" role="dialog" style="display: none;">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upgrade Required</h5>
                        <button type="button" class="close">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="mb-4">
                            <i class="fa fa-lock fa-3x text-warning mb-3"></i>
                            <h4>You need to upgrade your plan</h4>
                            <p class="text-muted">${getRestrictionMessage(options)}</p>
                        </div>
                        <div class="d-grid gap-2">
                            <a href="/local/chapa_subscription/subscribe.php" class="btn btn-primary btn-lg">Upgrade Now</a>
                            <a href="/local/chapa_subscription/user/subscriptions.php" class="btn btn-outline-secondary">My Subscriptions</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    $('#subscription-modal').remove();
    // console.debug('modal: removed existing');
    
    // Add modal to body
    $('body').append(modalHtml);
    // console.debug('modal: added to body');
    
    // Add CSS for modal if not already present
    if (!$('#subscription-modal-css').length) {
        var modalCSS = `
            <style id="subscription-modal-css">
                #subscription-modal {
                    display: none;
                    position: fixed;
                    z-index: 1050;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0,0,0,0.5);
                }
                #subscription-modal.show {
                    display: block !important;
                }
                .modal-dialog {
                    position: relative;
                    width: auto;
                    max-width: 500px;
                    margin: 1.75rem auto;
                }
                .modal-content {
                    position: relative;
                    display: flex;
                    flex-direction: column;
                    width: 100%;
                    background-color: #fff;
                    border: 1px solid rgba(0,0,0,.2);
                    border-radius: 0.3rem;
                    box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,.5);
                }
                .modal-header {
                    display: flex;
                    align-items: flex-start;
                    justify-content: space-between;
                    padding: 1rem;
                    border-bottom: 1px solid #dee2e6;
                }
                .modal-body {
                    position: relative;
                    flex: 1 1 auto;
                    padding: 1rem;
                }
                .modal-footer {
                    display: flex;
                    align-items: center;
                    justify-content: flex-end;
                    padding: 1rem;
                    border-top: 1px solid #dee2e6;
                }
                .btn {
                    display: inline-block;
                    font-weight: 400;
                    line-height: 1.5;
                    color: #212529;
                    text-align: center;
                    text-decoration: none;
                    vertical-align: middle;
                    cursor: pointer;
                    user-select: none;
                    background-color: transparent;
                    border: 1px solid transparent;
                    padding: 0.375rem 0.75rem;
                    font-size: 1rem;
                    border-radius: 0.25rem;
                    transition: color .15s ease-in-out,background-color .15s ease-in-out,border-color .15s ease-in-out,box-shadow .15s ease-in-out;
                }
                .btn-primary {
                    color: #fff !important;
                    background-color: #0d6efd !important;
                    border-color: #0d6efd !important;
                }
                .btn-primary:hover {
                    background-color: #0b5ed7 !important;
                    border-color: #0a58ca !important;
                }
                .btn-outline-secondary {
                    color: #6c757d !important;
                    border-color: #6c757d !important;
                    background-color: transparent !important;
                }
                .btn-outline-secondary:hover {
                    background-color: #6c757d !important;
                    color: #fff !important;
                }
                .close {
                    float: right;
                    font-size: 1.5rem;
                    font-weight: 700;
                    line-height: 1;
                    color: #000;
                    text-shadow: 0 1px 0 #fff;
                    opacity: .5;
                    background: transparent;
                    border: 0;
                    cursor: pointer;
                }
                .close:hover {
                    opacity: .75;
                }
            </style>
        `;
        $('head').append(modalCSS);
    }
    
    // Check if modal element exists
    var modalElement = $('#subscription-modal');
    var modalDomElement = modalElement[0]; // Get the actual DOM element
    // console.debug('modal found', modalElement.length > 0);
    
    // Show modal - simple approach
    if (modalDomElement) {
        modalDomElement.style.display = 'block';
        modalDomElement.classList.add('show');
        document.body.classList.add('modal-open');
        // console.debug('modal displayed');
        
        // Add event handlers after modal is displayed
        setTimeout(function() {
            // Close button functionality
            $('#subscription-modal .close').off('click').on('click', function(e) {
                e.preventDefault();
                closeModal();
            });
            
            // Close modal when clicking outside
            $('#subscription-modal').off('click').on('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
            
            // Test button clicks
            $('#subscription-modal .btn').off('click').on('click', function(e) {});
            
            // console.debug('modal handlers attached');
        }, 100);
    } else {
        console.error('Modal DOM element not found');
    }
}

// Global function to close modal
function closeModal() {
    console.log('closeModal called');
    var modalElement = $('#subscription-modal');
    var modalDomElement = modalElement[0];
    
    if (modalDomElement) {
        modalDomElement.style.display = 'none';
        modalDomElement.classList.remove('show');
        document.body.classList.remove('modal-open');
        console.log('Modal closed successfully');
    } else {
        console.error('Modal element not found for closing');
    }
}

// Make closeModal available globally
window.closeModal = closeModal;

// Initialize click handlers
$(document).ready(function() {
    console.log('Simple modal script loaded');
    console.log('jQuery version:', $.fn.jquery);
    console.log('Document ready, setting up click handlers');
    
    // Capture-phase fallback: intercept clicks on restricted activities before other handlers
    // This ensures we still show the modal even if other scripts stop propagation
    document.addEventListener('click', function(e) {
        try {
            var modalEl = document.getElementById('subscription-modal');
            if (modalEl && modalEl.contains(e.target)) {
                return;
            }
            var wrapper = e.target.closest('.activity.dimmed, .activity-item.dimmed, .activity.subtile.dimmed, .activity.unclickable.dimmed, .activity.purpose_content.dimmed, .sectionname.dimmed');
            if (!wrapper) {
                return;
            }
            console.log('CHAPA DEBUG: Capture-phase detected restricted activity click');
            
            // Prevent default and stop other handlers
            e.preventDefault();
            e.stopPropagation();
            if (typeof e.stopImmediatePropagation === 'function') {
                e.stopImmediatePropagation();
            }
            
            var $wrapper = $(wrapper);
            
            // Determine content name
            var contentName = $wrapper.attr('data-title') || '';
            if (!contentName) {
                var titleEl = $wrapper.find('.activityname h5, h4.sectionname, .sectionname, h4, h5').first();
                if (titleEl.length > 0) {
                    contentName = titleEl.text().trim();
                }
            }
            if (!contentName) {
                contentName = 'this content';
            }
            
            // Gather restriction info
            var directText = $wrapper.text() || '';
            var nestedTitle = $wrapper.find('.availabilityinfo, .badge.badge-info').attr('data-original-title') || '';
            var allText = (directText + ' ' + nestedTitle).trim();
            
            // Detect required plan
            var requiredPlan = 'Standard Plan';
            if (allText.includes('Premium Plan')) {
                requiredPlan = 'Premium Plan';
            } else if (allText.includes('Standard Plan')) {
                requiredPlan = 'Standard Plan';
            } else if (allText.includes('Basic Plan')) {
                requiredPlan = 'Basic Plan';
            }
            
            var currentPlan = window.chapaCurrentPlan || 'Basic Plan';
            
            console.log('CHAPA DEBUG: [capture] Showing modal for:', {
                contentName: contentName,
                requiredPlan: requiredPlan,
                currentPlan: currentPlan
            });
            
            showSubscriptionModal({
                contentName: contentName,
                requiredPlan: requiredPlan,
                currentPlan: currentPlan,
                restrictionType: 'activity'
            });
        } catch (err) {
            console.log('CHAPA DEBUG: Capture-phase handler error', err);
        }
    }, true);
    
    // Debug: Log all clicks (but not modal elements)
    $(document).on('click', '*', function(e) {
        // Don't log clicks on modal elements
        if ($(this).closest('#subscription-modal').length > 0) {
            return;
        }
        
        var $this = $(this);
        var classes = $this.attr('class') || '';
        var text = $this.text().substring(0, 100); // First 100 chars
        
        // Log clicks on activities, dimmed elements, or restriction-related elements
        if (classes.includes('dimmed') || classes.includes('activity-item') || classes.includes('activity') || text.includes('Not available') || text.includes('Restricted')) {
            console.log('Click detected on:', {
                element: this.tagName,
                classes: classes,
                text: text,
                hasLock: $this.find('i.fa-lock').length > 0
            });
        }
    });
    
    // Handle clicks on restricted content - be more comprehensive
    $(document).on('click', '.activity.dimmed, .activity-item.dimmed, .sectionname.dimmed, .cm-inner.dimmed, .activity.subtile.dimmed, .activity.unclickable.dimmed, .activity.purpose_content.dimmed, .dimmed', function(e) {
        // Don't trigger on modal elements
        if ($(this).closest('#subscription-modal').length > 0) {
            return;
        }
        
        var $this = $(this);
        var text = $this.text();
        
        // For sections, be more specific - only trigger if the section itself is restricted
        if ($this.hasClass('sectionname') || $this.closest('.section').length > 0) {
            // Only show modal if the section itself has a lock icon or restriction text
            var sectionHasRestriction = $this.find('i.fa-lock').length > 0 || 
                                       $this.text().includes('Not available unless') || 
                                       $this.text().includes('You belong to');
            
            if (!sectionHasRestriction) {
                console.log('CHAPA DEBUG: Section is not restricted, no modal needed');
                return;
            }
            
            // For section clicks, also check if the user can access the section based on its direct restrictions
            var sectionText = $this.text().trim();
            var currentPlan = window.chapaCurrentPlan || 'free';
            if (sectionText.includes('Basic Plan') && currentPlan === 'Basic Plan') {
                console.log('CHAPA DEBUG: User has Basic Plan, can access Basic Plan section');
                return;
            }
        }
        
        // For activities, check if they have restrictions
        if ($this.hasClass('activity') || $this.closest('.activity').length > 0) {
            console.log('CHAPA DEBUG: Activity clicked, checking for restrictions');
            
            // Check if this activity has restriction info in child elements
            var hasChildRestrictions = $this.find('.availabilityinfo, .badge.badge-info').length > 0;
            var hasRestrictionAttributes = $this.attr('data-original-title') && 
                                          ($this.attr('data-original-title').includes('Standard Plan') || 
                                           $this.attr('data-original-title').includes('Premium Plan') ||
                                           $this.attr('data-original-title').includes('Basic Plan'));
            
            // Also check if the activity is dimmed (which usually indicates restrictions)
            var isDimmed = $this.hasClass('dimmed');
            
            // Check for restriction text in the activity content
            var hasRestrictionText = $this.text().includes('Not available unless') || 
                                   $this.text().includes('You belong to') || 
                                   $this.text().includes('Restricted');
            
            // Check for restriction info in nested elements more thoroughly
            var hasNestedRestrictions = false;
            $this.find('.availabilityinfo, .badge.badge-info').each(function() {
                var nestedTitle = $(this).attr('data-original-title') || '';
                var nestedText = $(this).text() || '';
                if (nestedTitle.includes('Standard Plan') || nestedTitle.includes('Premium Plan') || nestedTitle.includes('Basic Plan') ||
                    nestedText.includes('Not available unless') || nestedText.includes('You belong to')) {
                    hasNestedRestrictions = true;
                    console.log('CHAPA DEBUG: Found nested restriction info:', {
                        title: nestedTitle,
                        text: nestedText
                    });
                }
            });
            
            console.log('CHAPA DEBUG: Activity restriction check:', {
                hasChildRestrictions: hasChildRestrictions,
                hasRestrictionAttributes: hasRestrictionAttributes,
                isDimmed: isDimmed,
                hasRestrictionText: hasRestrictionText,
                hasNestedRestrictions: hasNestedRestrictions
            });
            
            if (!hasChildRestrictions && !hasRestrictionAttributes && !isDimmed && !hasRestrictionText && !hasNestedRestrictions) {
                console.log('CHAPA DEBUG: Activity has no restrictions, no modal needed');
                return;
            }
            
            console.log('CHAPA DEBUG: Activity has restrictions, proceeding with modal logic');
            
            // For activities with restrictions, proceed directly to modal logic
            e.preventDefault();
            console.log('Restricted activity clicked');
            
            var contentName = 'this content';
            
            console.log('Element text:', text);
            console.log('Element classes:', $this.attr('class'));
            
            // Try to find a title element for better content name
            var titleElement = $this.find('h4, h5, .sectionname, .activityname').first();
            if (titleElement.length > 0) {
                contentName = titleElement.text().trim();
                console.log('Found title element:', contentName);
            }
            
            // Get all text content for plan detection
            var directText = $this.text().trim();
            var tooltipText = $this.attr('title') || '';
            var dataOriginalTitle = $this.attr('data-original-title') || '';
            var allText = directText + ' ' + tooltipText + ' ' + dataOriginalTitle;
            
            // For activity elements, also check child elements for restriction info
            var childRestrictionInfo = $this.find('.availabilityinfo, .badge.badge-info').attr('data-original-title') || '';
            if (childRestrictionInfo) {
                allText += ' ' + childRestrictionInfo;
                console.log('Child restriction info:', childRestrictionInfo);
            }
            
            // Also check for restriction info in the activity element itself
            var activityRestrictionInfo = $this.attr('data-original-title') || '';
            if (activityRestrictionInfo) {
                allText += ' ' + activityRestrictionInfo;
                console.log('Activity restriction info:', activityRestrictionInfo);
            }
            
            // For activities, also check the entire activity content for restriction text
            var activityContent = $this.text();
            if (activityContent && activityContent.includes('Not available unless')) {
                allText += ' ' + activityContent;
                console.log('Activity content with restrictions:', activityContent);
            }
            
            // Check all nested restriction elements more thoroughly
            $this.find('.availabilityinfo, .badge.badge-info').each(function() {
                var nestedTitle = $(this).attr('data-original-title') || '';
                var nestedText = $(this).text() || '';
                if (nestedTitle) {
                    allText += ' ' + nestedTitle;
                    console.log('Nested restriction title:', nestedTitle);
                }
                if (nestedText && nestedText.includes('Not available unless')) {
                    allText += ' ' + nestedText;
                    console.log('Nested restriction text:', nestedText);
                }
            });
            
            console.log('Direct text for plan detection:', directText);
            console.log('Tooltip text:', tooltipText);
            console.log('Data original title:', dataOriginalTitle);
            console.log('All text combined:', allText);
            
            // Detect required plan from the text
            var requiredPlan = 'Standard Plan'; // Default
            if (allText.includes('Basic Plan')) {
                requiredPlan = 'Basic Plan';
            } else if (allText.includes('Standard Plan')) {
                requiredPlan = 'Standard Plan';
            } else if (allText.includes('Premium Plan')) {
                requiredPlan = 'Premium Plan';
            }
            
            console.log('Detected required plan from direct text:', requiredPlan);
            console.log('Content name:', contentName);
            console.log('Required plan:', requiredPlan);
            console.log('User current plan:', currentPlan);
            console.log('Direct text:', directText);
            
            // Show modal with activity-specific options
            showSubscriptionModal({
                contentName: contentName,
                requiredPlan: requiredPlan,
                currentPlan: currentPlan,
                restrictionType: 'activity'
            });
            
            return; // Exit early for activities
        }
        
        // For non-activity elements, check for restriction indicators
        var hasRestrictionText = text.includes('Not available unless') || 
                                text.includes('You belong to') || 
                                text.includes('Restricted') ||
                                ($this.hasClass('dimmed') && (text.includes('Basic Plan') || text.includes('Standard Plan') || text.includes('Premium Plan')));
        
        // Also check if there's a restriction badge or info nearby
        var hasRestrictionBadge = $this.find('.badge.badge-info, .availabilityinfo').length > 0;
        
        // Check for plan information in nearby availability info elements
        var availabilityInfo = $this.find('.availabilityinfo, .badge.badge-info').text();
        if (availabilityInfo) {
            allText += ' ' + availabilityInfo;
            console.log('Availability info text:', availabilityInfo);
        }
        
        // For activity elements, also check if the element itself has restriction attributes
        var hasRestrictionAttributes = $this.attr('data-original-title') && 
                                      ($this.attr('data-original-title').includes('Standard Plan') || 
                                       $this.attr('data-original-title').includes('Premium Plan') ||
                                       $this.attr('data-original-title').includes('Basic Plan'));
        
        // Check for restriction info in child elements
        var hasChildRestrictionAttributes = $this.find('.availabilityinfo, .badge.badge-info').attr('data-original-title') && 
                                          ($this.find('.availabilityinfo, .badge.badge-info').attr('data-original-title').includes('Standard Plan') || 
                                           $this.find('.availabilityinfo, .badge.badge-info').attr('data-original-title').includes('Premium Plan') ||
                                           $this.find('.availabilityinfo, .badge.badge-info').attr('data-original-title').includes('Basic Plan'));
        
        // If this is a dimmed activity with restriction info, treat it as restricted
        if ($this.hasClass('dimmed') && (hasRestrictionBadge || hasRestrictionAttributes || hasChildRestrictionAttributes)) {
            hasRestrictionText = true;
            console.log('CHAPA DEBUG: Dimmed activity with restrictions detected');
        }
        
        if (!hasRestrictionText && !hasRestrictionBadge) {
            console.log('CHAPA DEBUG: Content is not restricted, no modal needed');
            return;
        }
        
        console.log('Specific restricted content clicked');
        e.preventDefault();
        console.log('Restricted content clicked');
        
        var contentName = 'this content';
        
        console.log('Element text:', text);
        console.log('Element classes:', $this.attr('class'));
        
        // Extract content name
        var titleElement = $this.find('h1, h2, h3, h4, h5, h6, .sectionname, .activityname').first();
        if (titleElement.length > 0) {
            contentName = titleElement.text().replace(/\s*<i[^>]*>.*?<\/i>\s*/g, '').trim();
            console.log('Found title element:', titleElement.text());
        }
        
        // Extract required plan - be more specific about what we're checking
        var requiredPlan = 'Basic Plan';
        
        // Only check for plan requirements in the actual clicked element, not nested content
        var directText = $this.text().trim();
        var tooltipText = $this.attr('title') || '';
        var dataOriginalTitle = $this.attr('data-original-title') || '';
        var allText = directText + ' ' + tooltipText + ' ' + dataOriginalTitle;
        
        // For activity elements, also check child elements for restriction info
        var childRestrictionInfo = $this.find('.availabilityinfo, .badge.badge-info').attr('data-original-title') || '';
        if (childRestrictionInfo) {
            allText += ' ' + childRestrictionInfo;
            console.log('Child restriction info:', childRestrictionInfo);
        }
        
        // Also check for restriction info in the activity element itself
        var activityRestrictionInfo = $this.attr('data-original-title') || '';
        if (activityRestrictionInfo) {
            allText += ' ' + activityRestrictionInfo;
            console.log('Activity restriction info:', activityRestrictionInfo);
        }
        
        // For activities, also check the entire activity content for restriction text
        var activityContent = $this.text();
        if (activityContent && activityContent.includes('Not available unless')) {
            allText += ' ' + activityContent;
            console.log('Activity content with restrictions:', activityContent);
        }
        
        // Check all nested restriction elements more thoroughly
        $this.find('.availabilityinfo, .badge.badge-info').each(function() {
            var nestedTitle = $(this).attr('data-original-title') || '';
            var nestedText = $(this).text() || '';
            if (nestedTitle) {
                allText += ' ' + nestedTitle;
                console.log('Nested restriction title:', nestedTitle);
            }
            if (nestedText && nestedText.includes('Not available unless')) {
                allText += ' ' + nestedText;
                console.log('Nested restriction text:', nestedText);
            }
        });
        
        console.log('Direct text for plan detection:', directText);
        console.log('Tooltip text:', tooltipText);
        console.log('Data original title:', dataOriginalTitle);
        console.log('All text combined:', allText);
        
        if (allText.includes('Standard Plan') || allText.includes('Standard Plan Cohort')) {
            requiredPlan = 'Standard Plan';
        } else if (allText.includes('Premium Plan') || allText.includes('Premium Plan Cohort')) {
            requiredPlan = 'Premium Plan';
        } else if (allText.includes('Basic Plan') || allText.includes('Basic Plan Cohort')) {
            requiredPlan = 'Basic Plan';
        }
        
        console.log('Detected required plan from direct text:', requiredPlan);
        
        console.log('Content name:', contentName);
        console.log('Required plan:', requiredPlan);
        console.log('User current plan:', window.chapaCurrentPlan || 'free');
        console.log('Direct text:', directText);
        
        // Check if user's current plan is sufficient for the required plan
        var currentPlan = window.chapaCurrentPlan || 'free';
        var planHierarchy = {
            'free': 0,
            'Free Preview': 0,
            'Basic Plan': 1,
            'Standard Plan': 2,
            'Premium Plan': 3
        };
        
        var currentPlanLevel = planHierarchy[currentPlan] || 0;
        var requiredPlanLevel = planHierarchy[requiredPlan] || 1;
        
        console.log('Current plan level:', currentPlanLevel);
        console.log('Required plan level:', requiredPlanLevel);
        console.log('Plan comparison:', currentPlanLevel + ' >= ' + requiredPlanLevel + ' = ' + (currentPlanLevel >= requiredPlanLevel));
        
        // If user's plan level is sufficient, don't show modal
        if (currentPlanLevel >= requiredPlanLevel) {
            console.log('CHAPA DEBUG: User has sufficient plan level, no modal needed');
            return;
        }
        
        console.log('CHAPA DEBUG: User needs to upgrade, showing modal');
        
        showSubscriptionModal({
            contentName: contentName,
            requiredPlan: requiredPlan,
            currentPlan: currentPlan,
            restrictionType: window.chapaRestrictionType || 'activity'
        });
    });
    
    // Handle elements with restriction text (but not modal elements)
    $(document).on('click', '.availabilityinfo, .badge.badge-info, .dimmed', function(e) {
        // Ignore clicks inside the modal
        if ($(this).closest('#subscription-modal').length > 0) {
            return;
        }

        // Ignore clicks on anchors or buttons to allow navigation
        if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON') {
            return;
        }

        var $this = $(this);
        var text = $this.text();
        
        // Only trigger on elements that actually have restriction text
        if (text.includes('Not available unless:') || text.includes('belong to') || text.includes('Restricted')) {
            e.preventDefault();
            console.log('Restriction message clicked');
            
            var contentName = 'this content';
            var titleElement = $this.find('h1, h2, h3, h4, h5, h6, .sectionname, .activityname').first();
            if (titleElement.length > 0) {
                contentName = titleElement.text().replace(/\s*<i[^>]*>.*?<\/i>\s*/g, '').trim();
            }
            
            var requiredPlan = 'Basic Plan';
            console.log('Restriction text:', text);
            
            // Check for plan requirements in the restriction text
            // Also check tooltip and title attributes for plan information
            var tooltipText = $this.attr('title') || '';
            var dataOriginalTitle = $this.attr('data-original-title') || '';
            var allText = text + ' ' + tooltipText + ' ' + dataOriginalTitle;
            
            console.log('Tooltip text:', tooltipText);
            console.log('Data original title:', dataOriginalTitle);
            console.log('All text combined:', allText);
            
            if (allText.includes('Standard Plan') || allText.includes('Standard Plan Cohort')) {
                requiredPlan = 'Standard Plan';
            } else if (allText.includes('Premium Plan') || allText.includes('Premium Plan Cohort')) {
                requiredPlan = 'Premium Plan';
            } else if (allText.includes('Basic Plan') || allText.includes('Basic Plan Cohort')) {
                requiredPlan = 'Basic Plan';
            }
            
            console.log('Detected required plan:', requiredPlan);
            
            // Check if user's current plan is sufficient for the required plan
            var currentPlan = window.chapaCurrentPlan || 'free';
            var planHierarchy = {
                'free': 0,
                'Free Preview': 0,
                'Basic Plan': 1,
                'Standard Plan': 2,
                'Premium Plan': 3
            };
            
            var currentPlanLevel = planHierarchy[currentPlan] || 0;
            var requiredPlanLevel = planHierarchy[requiredPlan] || 1;
            
            console.log('Current plan level:', currentPlanLevel);
            console.log('Required plan level:', requiredPlanLevel);
            console.log('Plan comparison:', currentPlanLevel + ' >= ' + requiredPlanLevel + ' = ' + (currentPlanLevel >= requiredPlanLevel));
            
            // If user's plan level is sufficient, don't show modal
            if (currentPlanLevel >= requiredPlanLevel) {
                console.log('CHAPA DEBUG: User has sufficient plan level, no modal needed');
                return;
            }
            
            console.log('CHAPA DEBUG: User needs to upgrade, showing modal');
            
            showSubscriptionModal({
                contentName: contentName,
                requiredPlan: requiredPlan,
                currentPlan: currentPlan,
                restrictionType: window.chapaRestrictionType || 'activity'
            });
        }
    });
});
