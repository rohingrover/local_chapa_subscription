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
 * Language strings for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Chapa Subscription';
$string['chapa_subscription'] = 'Chapa Subscription';

// General
$string['subscription'] = 'Subscription';
$string['subscriptions'] = 'Subscriptions';
$string['plan'] = 'Plan';
$string['plans'] = 'Plans';
$string['payment'] = 'Payment';
$string['payments'] = 'Payments';
$string['amount'] = 'Amount';
$string['currency'] = 'Currency';
$string['status'] = 'Status';
$string['active'] = 'Active';
$string['pending'] = 'Pending';
$string['cancelled'] = 'Cancelled';
$string['expired'] = 'Expired';
$string['monthly'] = 'Monthly';
$string['months'] = 'Months';
$string['discount'] = 'Discount';
$string['total'] = 'Total';
$string['subscribe'] = 'Subscribe';
$string['upgrade'] = 'Upgrade';
$string['downgrade'] = 'Downgrade';
$string['cancel'] = 'Cancel';
$string['renew'] = 'Renew';
$string['invoice'] = 'Invoice';
$string['receipt'] = 'Receipt';

// Plans
$string['basic'] = 'Basic';
$string['standard'] = 'Standard';
$string['premium'] = 'Premium';
$string['free_preview'] = 'Free Preview';
$string['plan_basic'] = 'Basic Plan';
$string['plan_standard'] = 'Standard Plan';
$string['plan_premium'] = 'Premium Plan';
$string['plan_free_preview'] = 'Free Preview';

// Pricing
$string['price_per_month'] = 'Price per month';
$string['upfront_payment'] = 'Upfront Payment';
$string['monthly_payment'] = 'Monthly Payment';
$string['save_percent'] = 'Save {$a}%';
$string['you_save'] = 'You save';
$string['original_price'] = 'Original price';
$string['discounted_price'] = 'Discounted price';

// Subscription features
$string['auto_renewal'] = 'Auto Renewal';
$string['manual_renewal'] = 'Manual Renewal';
$string['renewal_reminder'] = 'Renewal Reminder';
$string['renewal_success'] = 'Renewal Success';
$string['renewal_failed'] = 'Renewal Failed';
$string['subscription_expired'] = 'Subscription Expired';

// Access control
$string['access_restricted'] = 'Access Restricted';
$string['upgrade_required'] = 'Upgrade Required';
$string['subscription_required'] = 'A valid subscription is required to access this content.';
$string['upgrade_now'] = 'Upgrade Now';
$string['subscribe_now'] = 'Subscribe Now';

// Admin settings
$string['settings'] = 'Settings';
$string['chapa_api_keys'] = 'Chapa API Keys';
$string['chapa_public_key'] = 'Chapa Public Key';
$string['chapa_secret_key'] = 'Chapa Secret Key';
$string['chapa_encryption_key'] = 'Chapa Encryption Key';
$string['sandbox_mode'] = 'Sandbox Mode';
$string['live_mode'] = 'Live Mode';
$string['test_mode'] = 'Test Mode';

$string['plan_prices'] = 'Plan Prices';
$string['basic_price'] = 'Basic Plan Price';
$string['standard_price'] = 'Standard Plan Price';
$string['premium_price'] = 'Premium Plan Price';

$string['discounts'] = 'Discounts';
$string['discount_3_months'] = '3 Months Discount (%)';
$string['discount_6_months'] = '6 Months Discount (%)';
$string['discount_12_months'] = '12 Months Discount (%)';

$string['cohort_mappings'] = 'Cohort Mappings';
$string['free_preview_cohort'] = 'Free Preview Cohort';
$string['basic_cohort'] = 'Basic Plan Cohort';
$string['standard_cohort'] = 'Standard Plan Cohort';
$string['premium_cohort'] = 'Premium Plan Cohort';

$string['email_templates'] = 'Email Templates';
$string['renewal_reminder_template'] = 'Renewal Reminder Template';
$string['renewal_success_template'] = 'Renewal Success Template';
$string['renewal_failed_template'] = 'Renewal Failed Template';
$string['subscription_expired_template'] = 'Subscription Expired Template';
$string['receipt_email_template'] = 'Receipt Email Template';
$string['receipt_email_template_help'] = 'Customize the HTML receipt email. Supported placeholders: {firstname}, {lastname}, {plan}, {amount}, {currency}, {duration}, {discount}, {method}, {reference}, {date}, {invoice_url}, {site}.';

$string['invoice_settings'] = 'Invoice Settings';
$string['enable_invoices'] = 'Enable Invoice Generation';
$string['invoice_company_name'] = 'Company Name';
$string['invoice_company_address'] = 'Company Address';
$string['invoice_company_phone'] = 'Company Phone';
$string['invoice_company_email'] = 'Company Email';

// User dashboard
$string['my_subscription'] = 'My Subscription';
$string['subscription_details'] = 'Subscription Details';
$string['current_plan'] = 'Current Plan';
$string['subscription_start'] = 'Subscription Start';
$string['subscription_end'] = 'Subscription End';
$string['next_payment'] = 'Next Payment';
$string['payment_history'] = 'Payment History';
$string['billing_info'] = 'Billing Information';

// Payment process
$string['select_plan'] = 'Select Plan';
$string['select_duration'] = 'Select Duration';
$string['payment_summary'] = 'Payment Summary';
$string['proceed_to_payment'] = 'Proceed to Payment';
$string['payment_processing'] = 'Processing Payment...';
$string['payment_success'] = 'Payment Successful';
$string['payment_failed'] = 'Payment Failed';
$string['payment_cancelled'] = 'Payment Cancelled';

// Webhook
$string['webhook_received'] = 'Webhook Received';
$string['webhook_processed'] = 'Webhook Processed';
$string['webhook_failed'] = 'Webhook Processing Failed';

// Errors
$string['error_invalid_plan'] = 'Invalid subscription plan';
$string['error_invalid_duration'] = 'Invalid subscription duration';
$string['error_payment_failed'] = 'Payment processing failed';
$string['error_subscription_not_found'] = 'Subscription not found';
$string['error_user_not_found'] = 'User not found';
$string['error_webhook_verification'] = 'Webhook verification failed';
$string['error_api_keys_missing'] = 'Chapa API keys are not configured';

// Success messages
$string['success_subscription_created'] = 'Subscription created successfully';
$string['success_payment_processed'] = 'Payment processed successfully';
$string['success_subscription_updated'] = 'Subscription updated successfully';
$string['success_subscription_cancelled'] = 'Subscription cancelled successfully';

// Navigation
$string['subscription_management'] = 'Subscription Management';
$string['manage_subscriptions'] = 'Manage Subscriptions';
$string['view_subscription'] = 'View Subscription';
$string['edit_subscription'] = 'Edit Subscription';

// Cron tasks
$string['task_send_renewal_reminders'] = 'Send renewal reminders';
$string['task_process_failed_payments'] = 'Process failed payments';
$string['task_expire_subscriptions'] = 'Expire old subscriptions';

// Privacy
$string['privacy:metadata:local_chapa_subscriptions'] = 'Information about user subscriptions';
$string['privacy:metadata:local_chapa_payments'] = 'Information about payment transactions';
$string['privacy:metadata:chapa_customer_id'] = 'Chapa customer identifier';
$string['privacy:metadata:chapa_subscription_id'] = 'Chapa subscription identifier';
$string['privacy:metadata:chapa_txn_id'] = 'Chapa transaction identifier';

// Additional strings
$string['required'] = 'Required';
$string['yes'] = 'Yes';
$string['no'] = 'No';
$string['success'] = 'Success';
$string['failed'] = 'Failed';
$string['date'] = 'Date';
$string['continue'] = 'Continue';
$string['check_status'] = 'Check Status';
$string['payment_processing_message'] = 'Your payment is being processed. Please check back in a few minutes.';
$string['no_active_subscription'] = 'No Active Subscription';
$string['no_active_subscription_message'] = 'You do not have an active subscription. Subscribe now to access premium content.';
$string['terms_accepted'] = 'I accept the terms and conditions';
$string['error_active_subscription_exists'] = 'You already have an active subscription';
$string['error_same_plan'] = 'You are already subscribed to this plan';
$string['error_invalid_price'] = 'Invalid price';
$string['error_invalid_discount'] = 'Invalid discount percentage';
$string['error_access_denied'] = 'Access denied';
$string['success_settings_saved'] = 'Settings saved successfully';

$string['back_to_home'] = 'Back to Home';
$string['my_subscriptions'] = 'My Subscriptions';

// Help strings
$string['sandbox_mode_help'] = 'Enable sandbox mode for testing payments';
$string['chapa_public_key_help'] = 'Your Chapa public key for payment processing';
$string['chapa_secret_key_help'] = 'Your Chapa secret key for payment processing';
$string['chapa_encryption_key_help'] = 'Your Chapa encryption key for secure transactions';
$string['basic_price_help'] = 'Monthly price for Basic plan in ETB (e.g., 249 for 249 ETB)';
$string['standard_price_help'] = 'Monthly price for Standard plan in ETB (e.g., 299 for 299 ETB)';
$string['premium_price_help'] = 'Monthly price for Premium plan in ETB (e.g., 349 for 349 ETB)';
$string['discount_3_months_help'] = 'Discount percentage for 3-month upfront payments';
$string['discount_6_months_help'] = 'Discount percentage for 6-month upfront payments';
$string['discount_12_months_help'] = 'Discount percentage for 12-month upfront payments';
$string['free_preview_cohort_help'] = 'Cohort for users with no active subscription';
$string['basic_cohort_help'] = 'Cohort for users with Basic plan subscription';
$string['standard_cohort_help'] = 'Cohort for users with Standard plan subscription';
$string['premium_cohort_help'] = 'Cohort for users with Premium plan subscription';
$string['enable_invoices_help'] = 'Enable automatic PDF invoice generation';
$string['invoice_company_name_help'] = 'Company name to appear on invoices';
$string['invoice_company_address_help'] = 'Company address to appear on invoices';
$string['invoice_company_phone_help'] = 'Company phone number to appear on invoices';
$string['invoice_company_email_help'] = 'Company email address to appear on invoices';
$string['renewal_reminder_template_help'] = 'Available placeholders: {firstname}, {lastname}, {plan}, {enddate}, {amount}, {currency}, {site}.';
$string['renewal_success_template_help'] = 'Available placeholders: {firstname}, {lastname}, {plan}, {enddate}, {amount}, {currency}, {site}.';
$string['renewal_failed_template_help'] = 'Available placeholders: {firstname}, {lastname}, {plan}, {site}.';
$string['subscription_expired_template_help'] = 'Available placeholders: {firstname}, {lastname}, {plan}, {enddate}, {site}.';
$string['none'] = 'None';

// Subscription form strings
$string['duration'] = 'Duration';
$string['auto_renewal'] = 'Auto Renewal';
$string['auto_renewal_help'] = 'Automatically renew subscription when it expires';
$string['monthly'] = 'Monthly';
$string['quarterly'] = '3 Months';
$string['semiannual'] = '6 Months';
$string['annual'] = '12 Months';
$string['discount'] = 'Discount';
$string['total_price'] = 'Total Price';
$string['monthly_price'] = 'Monthly Price';
$string['you_save'] = 'You Save';
$string['best_value'] = 'Best Value';
$string['most_popular'] = 'Most Popular';
$string['subscribe_now'] = 'Subscribe Now';
$string['upgrade_now'] = 'Upgrade Now';
$string['current_plan'] = 'Current Plan';
$string['choose_plan'] = 'Choose Your Plan';
$string['plan_features'] = 'Plan Features';
$string['plan_exclusions'] = 'Plan Exclusions';
$string['plan_not_found'] = 'Plan not found';
$string['duration'] = 'Duration';
$string['auto_renewal_help'] = 'Auto-renewal will automatically charge your payment method when your subscription expires.';
$string['payment_method'] = 'Payment Method';
$string['downgrade_scheduled'] = 'Downgrade scheduled for next billing period';
$string['upgrade_plan'] = 'Upgrade Plan';
$string['downgrade_plan'] = 'Downgrade Plan';
$string['upgrade_difference'] = 'Upgrade Difference';
$string['downgrade_available'] = 'Downgrade Available';
$string['downgrade_scheduled_for'] = 'Downgrade scheduled for';
$string['immediate_upgrade'] = 'Immediate Upgrade';
$string['scheduled_downgrade'] = 'Scheduled Downgrade';
