/**
 * Enhanced subscription modal functionality for comprehensive access control
 * 
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/modal_factory', 'core/modal_events', 'core/ajax'], function($, ModalFactory, ModalEvents, Ajax) {
    
    var EnhancedSubscriptionModal = {
        
        /**
         * Show subscription modal for restricted content
         * @param {Object} options - Modal options
         */
        showModal: function(options) {
            var self = this;
            var defaults = {
                title: 'Subscription Required',
                message: 'This content requires a subscription to access.',
                isUpgrade: false,
                currentPlan: null,
                requiredPlan: null,
                courseId: null,
                contentType: 'topic', // topic, section, activity, resource
                contentName: 'this content',
                contentId: null
            };
            
            var config = $.extend({}, defaults, options);
            
            // Create modal content
            var modalContent = self.createModalContent(config);
            
            // Create modal
            ModalFactory.create({
                title: config.title,
                body: modalContent,
                large: true,
                show: true
            }).then(function(modal) {
                // Handle subscribe button clicks
                modal.getRoot().on('click', '.subscribe-btn', function(e) {
                    e.preventDefault();
                    var plan = $(this).data('plan');
                    if (plan) {
                        window.location.href = M.cfg.wwwroot + '/local/chapa_subscription/subscribe.php?plan=' + plan;
                    }
                });
                
                // Handle cancel button
                modal.getRoot().on('click', '.cancel-btn', function(e) {
                    e.preventDefault();
                    modal.destroy();
                });
                
                // Show modal
                modal.show();
            });
        },
        
        /**
         * Create modal content HTML
         * @param {Object} config - Configuration options
         * @returns {string} HTML content
         */
        createModalContent: function(config) {
            var content = '<div class="subscription-modal-content">';
            
            // Header based on content type
            var contentTypeText = this.getContentTypeText(config.contentType);
            var contentName = config.contentName || contentTypeText;
            
            if (config.isUpgrade) {
                content += '<div class="alert alert-info">';
                content += '<h5><i class="fa fa-arrow-up"></i> Upgrade Required</h5>';
                content += '<p>To access <strong>' + contentName + '</strong>, you need to upgrade your subscription plan.</p>';
                content += '</div>';
                
                content += '<div class="current-plan-info bg-light p-3 rounded mb-3">';
                content += '<div class="row">';
                content += '<div class="col-md-6">';
                content += '<p><strong>Current Plan:</strong> <span class="badge badge-secondary">' + (config.currentPlan || 'Free Preview') + '</span></p>';
                content += '</div>';
                content += '<div class="col-md-6">';
                content += '<p><strong>Required Plan:</strong> <span class="badge badge-primary">' + (config.requiredPlan || 'Basic Plan') + '</span></p>';
                content += '</div>';
                content += '</div>';
                content += '</div>';
            } else {
                content += '<div class="alert alert-warning">';
                content += '<h5><i class="fa fa-lock"></i> Subscription Required</h5>';
                content += '<p>To access <strong>' + contentName + '</strong>, you need an active subscription.</p>';
                content += '</div>';
            }
            
            // Show available plans with smart filtering
            content += '<div class="subscription-plans-preview">';
            content += '<h6>Available Plans:</h6>';
            content += '<div class="row">';
            
            // Basic Plan
            if (!this.isPlanHigher('basic', config.currentPlan)) {
                content += '<div class="col-md-4">';
                content += '<div class="plan-card border rounded p-3 mb-3">';
                content += '<h6>Basic Plan</h6>';
                content += '<p class="price text-primary font-weight-bold">' + (config.planPrices?.basic || 249) + ' ETB/month</p>';
                content += '<ul class="list-unstyled">';
                content += '<li><i class="fa fa-check text-success"></i> Full access to video lessons</li>';
                content += '<li><i class="fa fa-check text-success"></i> Short notes</li>';
                content += '<li><i class="fa fa-check text-success"></i> Basic support</li>';
                content += '</ul>';
                if (config.isUpgrade && config.currentPlan === 'basic') {
                    content += '<button class="btn btn-outline-secondary btn-sm w-100" disabled>Current Plan</button>';
                } else {
                    content += '<button class="btn btn-primary btn-sm w-100 subscribe-btn" data-plan="basic">Subscribe Now</button>';
                }
                content += '</div>';
                content += '</div>';
            }
            
            // Standard Plan
            if (!this.isPlanHigher('standard', config.currentPlan)) {
                content += '<div class="col-md-4">';
                content += '<div class="plan-card border rounded p-3 mb-3 popular">';
                content += '<div class="badge badge-warning mb-2">Most Popular</div>';
                content += '<h6>Standard Plan</h6>';
                content += '<p class="price text-primary font-weight-bold">' + (config.planPrices?.standard || 299) + ' ETB/month</p>';
                content += '<ul class="list-unstyled">';
                content += '<li><i class="fa fa-check text-success"></i> All Basic features</li>';
                content += '<li><i class="fa fa-check text-success"></i> AI assistant</li>';
                content += '<li><i class="fa fa-check text-success"></i> Question Bank</li>';
                content += '<li><i class="fa fa-check text-success"></i> Review videos</li>';
                content += '</ul>';
                if (config.isUpgrade && config.currentPlan === 'standard') {
                    content += '<button class="btn btn-outline-secondary btn-sm w-100" disabled>Current Plan</button>';
                } else {
                    content += '<button class="btn btn-primary btn-sm w-100 subscribe-btn" data-plan="standard">Subscribe Now</button>';
                }
                content += '</div>';
                content += '</div>';
            }
            
            // Premium Plan
            if (!this.isPlanHigher('premium', config.currentPlan)) {
                content += '<div class="col-md-4">';
                content += '<div class="plan-card border rounded p-3 mb-3">';
                content += '<h6>Premium Plan</h6>';
                content += '<p class="price text-primary font-weight-bold">' + (config.planPrices?.premium || 349) + ' ETB/month</p>';
                content += '<ul class="list-unstyled">';
                content += '<li><i class="fa fa-check text-success"></i> All Standard features</li>';
                content += '<li><i class="fa fa-check text-success"></i> Special Telegram channel</li>';
                content += '<li><i class="fa fa-check text-success"></i> Tailored responses</li>';
                content += '<li><i class="fa fa-check text-success"></i> Priority support</li>';
                content += '</ul>';
                if (config.isUpgrade && config.currentPlan === 'premium') {
                    content += '<button class="btn btn-outline-secondary btn-sm w-100" disabled>Current Plan</button>';
                } else {
                    content += '<button class="btn btn-primary btn-sm w-100 subscribe-btn" data-plan="premium">Subscribe Now</button>';
                }
                content += '</div>';
                content += '</div>';
            }
            
            content += '</div>';
            content += '</div>';
            
            // Action buttons
            content += '<div class="modal-actions mt-4">';
            content += '<div class="row">';
            content += '<div class="col-md-6">';
            content += '<button type="button" class="btn btn-secondary w-100 cancel-btn">Cancel</button>';
            content += '</div>';
            content += '<div class="col-md-6">';
            content += '<a href="/local/chapa_subscription/user/subscriptions.php" class="btn btn-outline-primary w-100">My Subscriptions</a>';
            content += '</div>';
            content += '</div>';
            content += '</div>';
            
            content += '</div>';
            
            return content;
        },
        
        /**
         * Get content type text
         * @param {string} contentType - Type of content
         * @returns {string} Human readable text
         */
        getContentTypeText: function(contentType) {
            var types = {
                'topic': 'this topic',
                'section': 'this section',
                'activity': 'this activity',
                'resource': 'this resource',
                'lesson': 'this lesson',
                'quiz': 'this quiz',
                'assignment': 'this assignment'
            };
            return types[contentType] || 'this content';
        },
        
        /**
         * Check if a plan is higher than current plan
         * @param {string} plan - Plan to check
         * @param {string} currentPlan - Current user plan
         * @returns {boolean} True if plan is higher
         */
        isPlanHigher: function(plan, currentPlan) {
            var planLevels = {
                'free': 0,
                'basic': 1,
                'standard': 2,
                'premium': 3
            };
            
            var planLevel = planLevels[plan] || 0;
            var currentLevel = planLevels[currentPlan] || 0;
            
            return planLevel > currentLevel;
        },
        
        /**
         * Initialize enhanced subscription modal functionality
         */
        init: function(config) {
            var self = this;
            EnhancedSubscriptionModal._config = config || {};
            
            // Add click handlers for all types of restricted content
            $(document).on('click', '.restricted-content, .cohort-restriction-message, [data-cohort-restricted]', function(e) {
                e.preventDefault();
                
                var $this = $(this);
                var isUpgrade = $this.data('is-upgrade') || false;
                var currentPlan = $this.data('current-plan') || null;
                var requiredPlan = $this.data('required-plan') || null;
                var courseId = $this.data('course-id') || null;
                var contentType = $this.data('content-type') || 'topic';
                var contentName = $this.data('content-name') || self.getContentTypeText(contentType);
                var contentId = $this.data('content-id') || null;
                
                self.showModal({
                    isUpgrade: isUpgrade,
                    currentPlan: currentPlan,
                    requiredPlan: requiredPlan,
                    courseId: courseId,
                    contentType: contentType,
                    contentName: contentName,
                    contentId: contentId
                });
            });
            
            // Handle cohort restriction messages and locked content
            $(document).on('click', '.cohort-restriction-message, .activity-item.dimmed, .sectionname.dimmed, .cm-inner.activity-item', function(e) {
                e.preventDefault();
                
                var $this = $(this);
                var message = $this.text() || $this.find('.cohort-restriction-message').text();
                var requiredCohort = self.extractCohortFromMessage(message);
                
                // Get content name from the element
                var contentName = 'this content';
                var contentTitle = $this.find('h1, h2, h3, h4, h5, h6, .sectionname, .activityname').first().text().trim();
                if (contentTitle) {
                    contentName = contentTitle.replace(/\s*<i[^>]*>.*?<\/i>\s*/g, '').trim(); // Remove lock icon
                }
                
                self.showModal({
                    isUpgrade: true,
                    currentPlan: 'free',
                    requiredPlan: requiredCohort,
                    contentType: 'content',
                    contentName: contentName
                });
            });
            
            // Handle specific locked activities and sections
            $(document).on('click', '.activity-item.dimmed, .sectionname.dimmed', function(e) {
                e.preventDefault();
                
                var $this = $(this);
                var contentName = $this.text().trim();
                var requiredCohort = 'Basic Plan'; // Default to Basic Plan
                
                // Check if there's a cohort restriction message nearby
                var restrictionMessage = $this.closest('.cm-inner').find('*').filter(function() {
                    return $(this).text().includes('Not available unless:') || $(this).text().includes('belong to');
                }).first();
                
                if (restrictionMessage.length > 0) {
                    requiredCohort = self.extractCohortFromMessage(restrictionMessage.text());
                }
                
                self.showModal({
                    isUpgrade: true,
                    currentPlan: 'free',
                    requiredPlan: requiredCohort,
                    contentType: 'content',
                    contentName: contentName
                });
            });
            
            // Handle any element containing restriction messages
            $(document).on('click', '*', function(e) {
                var $this = $(this);
                var text = $this.text();
                
                // Check if this element or its children contain restriction messages
                if (text.includes('Not available unless:') || text.includes('belong to') || 
                    ($this.hasClass('dimmed') && $this.find('i.fa-lock').length > 0)) {
                    
                    e.preventDefault();
                    
                    var contentName = 'this content';
                    var titleElement = $this.find('h1, h2, h3, h4, h5, h6, .sectionname, .activityname').first();
                    if (titleElement.length > 0) {
                        contentName = titleElement.text().replace(/\s*<i[^>]*>.*?<\/i>\s*/g, '').trim();
                    } else if ($this.is('h1, h2, h3, h4, h5, h6, .sectionname, .activityname')) {
                        contentName = $this.text().replace(/\s*<i[^>]*>.*?<\/i>\s*/g, '').trim();
                    }
                    
                    var requiredCohort = self.extractCohortFromMessage(text);
                    
                    self.showModal({
                        isUpgrade: true,
                        currentPlan: 'free',
                        requiredPlan: requiredCohort,
                        contentType: 'content',
                        contentName: contentName
                    });
                }
            });
        },
        
        /**
         * Extract cohort name from restriction message
         * @param {string} message - Restriction message
         * @returns {string} Cohort name
         */
        extractCohortFromMessage: function(message) {
            if (!message) return 'Basic Plan';
            
            // Look for specific plan mentions
            if (message.includes('Basic Plan Cohort') || message.includes('Basic Plan')) return 'Basic Plan';
            if (message.includes('Standard Plan Cohort') || message.includes('Standard Plan')) return 'Standard Plan';
            if (message.includes('Premium Plan Cohort') || message.includes('Premium Plan')) return 'Premium Plan';
            
            // Look for plan keywords
            if (message.toLowerCase().includes('basic')) return 'Basic Plan';
            if (message.toLowerCase().includes('standard')) return 'Standard Plan';
            if (message.toLowerCase().includes('premium')) return 'Premium Plan';
            
            return 'Basic Plan';
        }
    };
    
    return EnhancedSubscriptionModal;
});
