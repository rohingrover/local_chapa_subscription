/**
 * Subscription modal functionality for course access restrictions
 * 
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/modal_factory', 'core/modal_events'], function($, ModalFactory, ModalEvents) {
    
    var SubscriptionModal = {
        
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
                courseId: null
            };
            
            var config = $.extend({}, defaults, options);
            
            // Create modal content
            var modalContent = self.createModalContent(config);
            
            // Create modal
            ModalFactory.create({
                type: ModalFactory.types.SAVE_CANCEL,
                title: config.title,
                body: modalContent,
                large: true
            }).then(function(modal) {
                // Handle modal events
                modal.getRoot().on(ModalEvents.save, function() {
                    // Redirect to subscription page
                    window.location.href = M.cfg.wwwroot + '/local/chapa_subscription/subscribe.php';
                });
                
                modal.getRoot().on(ModalEvents.cancel, function() {
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
            
            if (config.isUpgrade) {
                content += '<div class="alert alert-info">';
                content += '<h5><i class="fa fa-info-circle"></i> Upgrade Required</h5>';
                content += '<p>You need to upgrade your subscription to access this content.</p>';
                content += '</div>';
                
                content += '<div class="current-plan-info">';
                content += '<p><strong>Current Plan:</strong> ' + (config.currentPlan || 'Free Preview') + '</p>';
                content += '<p><strong>Required Plan:</strong> ' + (config.requiredPlan || 'Basic Plan') + '</p>';
                content += '</div>';
            } else {
                content += '<div class="alert alert-warning">';
                content += '<h5><i class="fa fa-lock"></i> Subscription Required</h5>';
                content += '<p>This content requires a subscription to access.</p>';
                content += '</div>';
            }
            
            content += '<div class="subscription-benefits">';
            content += '<h6>Subscription Benefits:</h6>';
            content += '<ul>';
            content += '<li>Access to all course content</li>';
            content += '<li>Downloadable resources</li>';
            content += '<li>Certificate of completion</li>';
            content += '<li>Priority support</li>';
            content += '</ul>';
            content += '</div>';
            
            content += '<div class="subscription-plans-preview">';
            content += '<h6>Available Plans:</h6>';
            content += '<div class="row">';
            content += '<div class="col-md-4">';
            content += '<div class="plan-card">';
            content += '<h6>Basic Plan</h6>';
            content += '<p class="price">' + (window.chapaPlanPrices?.basic || 249) + ' ETB/month</p>';
            content += '<ul class="plan-features">';
            content += '<li>Basic courses</li>';
            content += '<li>Email support</li>';
            content += '</ul>';
            content += '</div>';
            content += '</div>';
            content += '<div class="col-md-4">';
            content += '<div class="plan-card">';
            content += '<h6>Standard Plan</h6>';
            content += '<p class="price">' + (window.chapaPlanPrices?.standard || 299) + ' ETB/month</p>';
            content += '<ul class="plan-features">';
            content += '<li>Standard courses</li>';
            content += '<li>Priority support</li>';
            content += '</ul>';
            content += '</div>';
            content += '</div>';
            content += '<div class="col-md-4">';
            content += '<div class="plan-card">';
            content += '<h6>Premium Plan</h6>';
            content += '<p class="price">' + (window.chapaPlanPrices?.premium || 349) + ' ETB/month</p>';
            content += '<ul class="plan-features">';
            content += '<li>All courses</li>';
            content += '<li>Premium features</li>';
            content += '<li>24/7 support</li>';
            content += '</ul>';
            content += '</div>';
            content += '</div>';
            content += '</div>';
            content += '</div>';
            
            content += '</div>';
            
            return content;
        },
        
        /**
         * Initialize subscription modal functionality
         */
        init: function() {
            var self = this;
            
            // Add click handlers for restricted content
            $(document).on('click', '.restricted-content', function(e) {
                e.preventDefault();
                
                var $this = $(this);
                var isUpgrade = $this.data('is-upgrade') || false;
                var currentPlan = $this.data('current-plan') || null;
                var requiredPlan = $this.data('required-plan') || null;
                var courseId = $this.data('course-id') || null;
                
                self.showModal({
                    isUpgrade: isUpgrade,
                    currentPlan: currentPlan,
                    requiredPlan: requiredPlan,
                    courseId: courseId
                });
            });
            
            // Add click handlers for cohort restriction messages
            $(document).on('click', '.cohort-restriction-message', function(e) {
                e.preventDefault();
                
                var $this = $(this);
                var isUpgrade = $this.data('is-upgrade') || false;
                var currentPlan = $this.data('current-plan') || null;
                var requiredPlan = $this.data('required-plan') || null;
                
                self.showModal({
                    isUpgrade: isUpgrade,
                    currentPlan: currentPlan,
                    requiredPlan: requiredPlan
                });
            });
        }
    };
    
    return SubscriptionModal;
});
