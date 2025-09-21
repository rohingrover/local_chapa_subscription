# Chapa Subscription Plugin for Moodle

## Overview

The Chapa Subscription plugin is a comprehensive subscription management system for Moodle that integrates with Chapa payment gateway to provide tiered access to course content based on subscription plans. The plugin supports automatic cohort management, content restriction, payment processing, and subscription lifecycle management.

## Table of Contents

1. [Features](#features)
2. [Installation & Setup](#installation--setup)
3. [User Journey](#user-journey)
4. [Admin Configuration](#admin-configuration)
5. [Content Restriction System](#content-restriction-system)
6. [Payment Processing](#payment-processing)
7. [Subscription Management](#subscription-management)
8. [Email Templates](#email-templates)
9. [Reports & Analytics](#reports--analytics)
10. [Technical Architecture](#technical-architecture)
11. [Troubleshooting](#troubleshooting)

## Features

### Core Features
- **Multi-tier Subscription Plans**: Basic, Standard, and Premium plans with different access levels
- **Automatic Cohort Management**: Users are automatically assigned to appropriate cohorts based on their subscription
- **Content Restriction**: Restrict access to activities, resources, and sections based on subscription plans
- **Payment Integration**: Seamless integration with Chapa payment gateway
- **Subscription Lifecycle**: Complete management of subscription creation, renewal, upgrades, and cancellations
- **Email Notifications**: Automated email notifications for various subscription events
- **Invoice Generation**: Automatic invoice generation and email delivery
- **Admin Dashboard**: Comprehensive admin interface for managing subscriptions and users

### Advanced Features
- **Downgrade Scheduling**: Users can schedule plan downgrades for the next billing cycle
- **Auto-renewal Management**: Users can enable/disable auto-renewal
- **Discount System**: Support for bulk payment discounts (3, 6, 12 months)
- **Content Preview**: Free preview access for non-subscribers
- **Content Hierarchy**: Support for restricting entire sections or individual activities
- **Renewal Reminders**: Automated renewal reminder emails
- **Refund Management**: Admin capability to process refunds

## Installation & Setup

### Prerequisites
- Moodle 4.5+
- PHP 8.2+
- Chapa API credentials
- SSL certificate (required for payment processing)

### Installation Steps

1. **Download and Install**
   ```bash
   # Place the plugin in the local directory
   /path/to/moodle/local/chapa_subscription/
   ```

2. **Run Installation**
   ```bash
   # Access Moodle admin and run the installation
   # Or via CLI:
   php admin/cli/upgrade.php
   ```

3. **Configure Database**
   The plugin will automatically create the following tables:
   - `local_chapa_subscriptions`
   - `local_chapa_payments`
   - `local_chapa_settings`
   - `local_chapa_plans`
   - `local_chapa_reminders`
   - `local_chapa_downgrade_requests`
   - `local_chapa_cancellations`
   - `local_chapa_subscription_logs`


## User Journey

### 1. User Registration & First Login

When a new user registers:

1. **Automatic Cohort Assignment**: User is automatically added to the "Free Preview" cohort
2. **First Login Redirect**: On first login, user is redirected to the subscription page
3. **Subscription Options**: User can either:
   - Subscribe to a paid plan immediately
   - Skip and continue with free preview access
   - Browse available plans and features

### 2. Free Preview Access

Users with free preview access can:
- Self-enroll in any course (if self-enrollment is enabled)
- Access content marked for "Free Preview" cohort
- View subscription plans and pricing
- Subscribe to paid plans at any time

### 3. Paid Subscription Flow

1. **Plan Selection**: User selects a subscription plan (Basic, Standard, or Premium)
2. **Payment Duration**: User chooses payment frequency (Monthly, 3 Months, 6 Months, 12 Months)
3. **Payment Processing**: Integration with Chapa payment gateway
4. **Cohort Assignment**: Automatic assignment to appropriate cohort based on plan
5. **Access Grant**: Immediate access to plan-specific content

### 4. Content Access Based on Plans

#### Basic Plan
- Full access to video lessons
- Short notes
- Basic support
- **Restricted**: AI assistant, Question Bank, Review/Entrance exam videos

#### Standard Plan
- Everything in Basic Plan
- Access to AI assistant
- Review Question Videos
- Entrance Exam Question Videos
- Question Bank
- **Restricted**: Special Telegram channel, Tailored question responses

#### Premium Plan
- Everything in Standard Plan
- Access to special Telegram channel
- Ability to forward questions and receive tailored responses
- Priority support

## Admin Configuration

### Admin Settings Page (`/local/chapa_subscription/admin/settings.php`)

#### 1. Chapa API Configuration
- **Public Key**: Your Chapa public key
- **Secret Key**: Your Chapa secret key
- **Webhook URL**: Automatically generated webhook URL for payment notifications
- **Test Mode**: Enable/disable test mode for development

#### 2. Subscription Plans Configuration
- **Plan Names**: Customize plan names (Basic, Standard, Premium)
- **Monthly Prices**: Set monthly pricing for each plan (in ETB)
- **Plan Descriptions**: Detailed descriptions for each plan
- **Features**: List of features included in each plan
- **Exclusions**: List of features not included in each plan

#### 3. Cohort Mapping
- **Free Preview Cohort**: Cohort for users without active subscriptions
- **Basic Plan Cohort**: Cohort for Basic plan subscribers
- **Standard Plan Cohort**: Cohort for Standard plan subscribers
- **Premium Plan Cohort**: Cohort for Premium plan subscribers

#### 4. Discount Configuration
- **3 Months Discount**: Percentage discount for quarterly payments
- **6 Months Discount**: Percentage discount for semi-annual payments
- **12 Months Discount**: Percentage discount for annual payments

#### 5. Email Templates
- **Welcome Email**: Template for new subscribers
- **Renewal Reminder**: Template for renewal reminders
- **Renewal Success**: Template for successful renewals
- **Renewal Failed**: Template for failed renewals
- **Subscription Expired**: Template for expired subscriptions
- **Receipt Email**: Template for payment receipts

#### 6. Company Information
- **Company Name**: Your company name for invoices
- **Company Address**: Company address for invoices
- **Company Phone**: Contact phone number
- **Company Email**: Contact email address
- **Company Logo**: Company logo URL for invoices

### Admin Reports Page (`/local/chapa_subscription/admin/reports.php`)

#### Subscription Reports
- **Total Subscriptions**: Count of all subscriptions
- **Active Subscriptions**: Currently active subscriptions
- **Revenue Analytics**: Revenue breakdown by plan and time period
- **User Analytics**: User subscription patterns and trends

#### User Management
- **User Search**: Search users by name, email, or subscription status
- **Subscription Details**: View detailed subscription information
- **Payment History**: Complete payment history for each user
- **Subscription Actions**: Cancel, activate, or modify user subscriptions

### Admin Manage Subscription Page (`/local/chapa_subscription/admin/manage_subscription.php`)

#### Subscription Management
- **View Subscription**: Detailed subscription information
- **Cancel Subscription**: Admin can cancel any subscription
- **Change Plan**: Upgrade or downgrade user plans
- **Payment History**: View all payments for the subscription
- **Refund Processing**: Process refunds for payments

## Content Restriction System

### How Content Restriction Works

The plugin uses Moodle's cohort-based restriction system to control access to content:

1. **Cohort Assignment**: Users are automatically assigned to cohorts based on their subscription plan
2. **Content Restriction**: Course content is restricted using Moodle's "Restrict access" feature
3. **Hierarchical Access**: Higher-tier plans have access to all lower-tier content

### Setting Up Content Restrictions

#### 1. Course-Level Restrictions
- Navigate to course settings
- Go to "Restrict access" section
- Add restriction: "User belongs to cohort"
- Select the appropriate cohort (e.g., "Basic Plan Cohort")

#### 2. Section-Level Restrictions
- Edit course sections
- Add "Restrict access" to the section
- Set cohort-based restrictions

#### 3. Activity/Resource Restrictions
- Edit individual activities or resources
- Add "Restrict access" settings
- Configure cohort-based access

### Recommended Content Structure

#### Free Preview Content
- Sample videos from each course
- Basic course information
- Limited access to demonstrate value

#### Basic Plan Content
- Full video lessons
- Short notes
- Basic support materials

#### Standard Plan Content
- Everything in Basic Plan
- AI assistant access
- Question banks
- Review videos

#### Premium Plan Content
- Everything in Standard Plan
- Special Telegram channel access
- Tailored question responses
- Priority support

## Payment Processing

### Payment Flow

1. **Plan Selection**: User selects plan and payment duration
2. **Price Calculation**: System calculates total price including discounts
3. **Chapa Integration**: Payment request sent to Chapa
4. **Payment Processing**: User completes payment on Chapa's secure platform
5. **Webhook Notification**: Chapa sends payment confirmation
6. **Subscription Activation**: System activates subscription and assigns cohorts
7. **Email Notification**: User receives confirmation email

### Payment Methods Supported
- Credit/Debit Cards
- Mobile Money (Ethiopia)
- Bank Transfers
- Other Chapa-supported payment methods

### Discount System
- **3 Months**: 10% discount
- **6 Months**: 25% discount
- **12 Months**: 40% discount

## Subscription Management

### User Subscription Management (`/local/chapa_subscription/user/manage_subscription.php`)

#### Available Actions
- **View Current Plan**: See active subscription details
- **Upgrade Plan**: Upgrade to higher-tier plans immediately
- **Schedule Downgrade**: Schedule downgrade for next billing cycle
- **Cancel Subscription**: Cancel auto-renewal
- **View Invoices**: Download past invoices
- **Payment History**: View complete payment history

#### Subscription Statuses
- **Active**: Currently active subscription
- **Active (Pending cancellation)**: Active but won't renew
- **Expired**: Subscription has expired
- **Cancelled**: Manually cancelled by user or admin

### Auto-Renewal System

#### How Auto-Renewal Works
1. **Renewal Reminders**: Users receive email reminders before renewal
2. **Automatic Charging**: Chapa automatically processes payment
3. **Cohort Management**: Users remain in appropriate cohorts
4. **Email Notifications**: Users receive renewal confirmation

#### Managing Auto-Renewal
- Users can enable/disable auto-renewal
- Admins can override auto-renewal settings
- Failed payments trigger retry mechanisms

## Email Templates

### Template System

The plugin includes comprehensive email templates for all subscription events:

#### 1. Welcome Email
- Sent when user subscribes to a plan
- Includes subscription details and next steps
- Customizable content and styling

#### 2. Renewal Reminders
- Sent before subscription renewal
- Configurable timing (e.g., 7 days, 3 days, 1 day before)
- Includes renewal instructions

#### 3. Payment Receipts
- Sent after successful payments
- Includes invoice details and payment information
- Professional HTML formatting

#### 4. Subscription Expired
- Sent when subscription expires
- Includes re-subscription options
- Grace period information

### Email Customization

All email templates can be customized through the admin settings:
- HTML content editing
- Variable substitution (user name, plan details, etc.)
- Company branding
- Email styling

## Reports & Analytics

### Admin Reports Dashboard

#### Revenue Reports
- **Total Revenue**: Overall revenue from subscriptions
- **Revenue by Plan**: Breakdown by subscription plan
- **Revenue by Time Period**: Monthly, quarterly, annual revenue
- **Payment Success Rate**: Percentage of successful payments

#### User Analytics
- **Subscription Trends**: New subscriptions over time
- **Plan Popularity**: Most popular subscription plans
- **User Retention**: Subscription renewal rates
- **Churn Analysis**: User cancellation patterns

#### Financial Reports
- **Payment History**: Complete payment records
- **Refund Reports**: Refund processing and amounts
- **Tax Reports**: Revenue for tax purposes
- **Export Capabilities**: CSV/Excel export options

### User Reports
- **Personal Payment History**: Individual user payment records
- **Invoice Downloads**: PDF invoice generation
- **Subscription Timeline**: Complete subscription history

## Technical Architecture

### Database Schema

#### Core Tables
- `local_chapa_subscriptions`: Subscription records
- `local_chapa_payments`: Payment transactions
- `local_chapa_settings`: Plugin configuration
- `local_chapa_plans`: Plan definitions
- `local_chapa_plan_changes`: Scheduled plan changes
- `local_chapa_refunds`: Refund records

#### Key Relationships
- Users → Subscriptions (One-to-Many)
- Subscriptions → Payments (One-to-Many)
- Plans → Cohorts (One-to-One)

### Event System

#### Moodle Events
- `user_created`: Automatic cohort assignment
- `user_loggedin`: First login redirect
- `course_viewed`: Content access checking
- `course_module_viewed`: Activity access validation

#### Custom Events
- `subscription_created`: New subscription events
- `payment_processed`: Payment completion
- `subscription_expired`: Expiration events

### Cron Jobs

#### Automated Tasks
- **Subscription Expiration**: Daily check for expired subscriptions
- **Renewal Reminders**: Automated reminder emails
- **Cohort Management**: Ensure proper cohort assignments
- **Payment Processing**: Handle recurring payments

### Security Features

#### Payment Security
- SSL encryption for all payment processing
- Webhook signature verification
- Secure API key management
- PCI compliance through Chapa

#### Access Control
- Role-based permissions
- Capability checks for admin functions
- Secure user data handling
- Audit logging for admin actions

## Troubleshooting

### Common Issues

#### 1. Payment Processing Issues
**Problem**: Payments not processing
**Solutions**:
- Verify Chapa API credentials
- Check webhook URL configuration
- Ensure SSL certificate is valid
- Check server logs for errors

#### 2. Cohort Assignment Issues
**Problem**: Users not assigned to correct cohorts
**Solutions**:
- Verify cohort IDs in admin settings
- Check observer event registration
- Run manual cohort assignment script
- Check database for cohort_members table

#### 3. Content Access Issues
**Problem**: Users can't access restricted content
**Solutions**:
- Verify cohort restrictions are set correctly
- Check user's cohort membership
- Ensure cohorts are properly configured
- Test with different user accounts

#### 4. Email Delivery Issues
**Problem**: Emails not being sent
**Solutions**:
- Check Moodle email configuration
- Verify SMTP settings
- Check email templates for syntax errors
- Test with different email addresses

### Debugging Tools

#### 1. Admin Debug Mode
- Enable debug mode in admin settings
- View detailed error logs
- Check webhook processing logs
- Monitor payment processing

#### 2. Manual Scripts
- Cohort assignment script: `/cli/assign_free_cohort.php`
- Database verification scripts
- Payment reconciliation tools

#### 3. Log Files
- Moodle error logs
- Web server logs
- Chapa webhook logs
- Plugin-specific debug logs

### Performance Optimization

#### 1. Database Optimization
- Regular database maintenance
- Index optimization
- Query performance monitoring
- Cleanup of old records

#### 2. Caching
- Moodle cache configuration
- Plugin-specific caching
- CDN for static assets
- Database query caching

#### 3. Server Configuration
- PHP memory limits
- Database connection pooling
- Web server optimization
- SSL certificate management

## Support and Maintenance

### Regular Maintenance Tasks

#### Daily
- Monitor payment processing
- Check for failed payments
- Review error logs
- Verify webhook functionality

#### Weekly
- Review subscription reports
- Check cohort assignments
- Monitor email delivery
- Update user analytics

#### Monthly
- Database cleanup
- Performance optimization
- Security updates
- Backup verification

### Getting Help

#### Documentation
- This comprehensive guide
- Moodle documentation
- Chapa API documentation
- Plugin-specific documentation

#### Support Channels
- Plugin developer support
- Moodle community forums
- Chapa support team
- Technical documentation

#### Updates and Upgrades
- Regular plugin updates
- Moodle version compatibility
- Security patches
- Feature enhancements

---

## Conclusion

The Chapa Subscription plugin provides a complete subscription management solution for Moodle, offering seamless integration with Chapa payment gateway, comprehensive content restriction capabilities, and robust admin management tools. With proper configuration and maintenance, it can significantly enhance your Moodle site's monetization capabilities while providing users with a smooth subscription experience.

For additional support or feature requests, please refer to the plugin documentation or contact the development team.
