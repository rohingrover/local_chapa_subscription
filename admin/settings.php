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
 * Admin settings page for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/formslib.php');

// Check permissions
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/local/chapa_subscription/admin/settings.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('settings', 'local_chapa_subscription'));
$PAGE->set_heading(get_string('settings', 'local_chapa_subscription'));

// Navigation
$PAGE->navbar->add(get_string('pluginname', 'local_chapa_subscription'));
$PAGE->navbar->add(get_string('settings', 'local_chapa_subscription'));

// Form definition
class chapa_subscription_settings_form extends moodleform {

    public function definition() {
        global $CFG;
        
        $mform = $this->_form;
        
        // Chapa API Keys section
        $mform->addElement('header', 'chapa_api_keys', get_string('chapa_api_keys', 'local_chapa_subscription'));
        
        $mform->addElement('selectyesno', 'sandbox_mode', get_string('sandbox_mode', 'local_chapa_subscription'));
        $mform->setDefault('sandbox_mode', 1);
        $mform->addHelpButton('sandbox_mode', 'sandbox_mode', 'local_chapa_subscription');
        
        $mform->addElement('text', 'chapa_public_key', get_string('chapa_public_key', 'local_chapa_subscription'), array('size' => 50));
        $mform->setType('chapa_public_key', PARAM_TEXT);
        $mform->addHelpButton('chapa_public_key', 'chapa_public_key', 'local_chapa_subscription');
        
        $mform->addElement('text', 'chapa_secret_key', get_string('chapa_secret_key', 'local_chapa_subscription'), array('size' => 50));
        $mform->setType('chapa_secret_key', PARAM_TEXT);
        $mform->addHelpButton('chapa_secret_key', 'chapa_secret_key', 'local_chapa_subscription');
        
        $mform->addElement('text', 'chapa_encryption_key', get_string('chapa_encryption_key', 'local_chapa_subscription'), array('size' => 50));
        $mform->setType('chapa_encryption_key', PARAM_TEXT);
        $mform->addHelpButton('chapa_encryption_key', 'chapa_encryption_key', 'local_chapa_subscription');
        
        // Plan Prices section
        $mform->addElement('header', 'plan_prices', get_string('plan_prices', 'local_chapa_subscription'));
        
        $mform->addElement('text', 'basic_price', get_string('basic_price', 'local_chapa_subscription'), array('size' => 10));
        $mform->setType('basic_price', PARAM_INT);
        $mform->setDefault('basic_price', 249); // 249 ETB
        $mform->addHelpButton('basic_price', 'basic_price', 'local_chapa_subscription');
        
        $mform->addElement('text', 'standard_price', get_string('standard_price', 'local_chapa_subscription'), array('size' => 10));
        $mform->setType('standard_price', PARAM_INT);
        $mform->setDefault('standard_price', 299); // 299 ETB
        $mform->addHelpButton('standard_price', 'standard_price', 'local_chapa_subscription');
        
        $mform->addElement('text', 'premium_price', get_string('premium_price', 'local_chapa_subscription'), array('size' => 10));
        $mform->setType('premium_price', PARAM_INT);
        $mform->setDefault('premium_price', 349); // 349 ETB
        $mform->addHelpButton('premium_price', 'premium_price', 'local_chapa_subscription');
        
        // Discounts section
        $mform->addElement('header', 'discounts', get_string('discounts', 'local_chapa_subscription'));
        
        $mform->addElement('text', 'discount_3_months', get_string('discount_3_months', 'local_chapa_subscription'), array('size' => 5));
        $mform->setType('discount_3_months', PARAM_INT);
        $mform->setDefault('discount_3_months', 10);
        $mform->addHelpButton('discount_3_months', 'discount_3_months', 'local_chapa_subscription');
        
        $mform->addElement('text', 'discount_6_months', get_string('discount_6_months', 'local_chapa_subscription'), array('size' => 5));
        $mform->setType('discount_6_months', PARAM_INT);
        $mform->setDefault('discount_6_months', 25);
        $mform->addHelpButton('discount_6_months', 'discount_6_months', 'local_chapa_subscription');
        
        $mform->addElement('text', 'discount_12_months', get_string('discount_12_months', 'local_chapa_subscription'), array('size' => 5));
        $mform->setType('discount_12_months', PARAM_INT);
        $mform->setDefault('discount_12_months', 40);
        $mform->addHelpButton('discount_12_months', 'discount_12_months', 'local_chapa_subscription');
        
        // Cohort Mappings section
        $mform->addElement('header', 'cohort_mappings', get_string('cohort_mappings', 'local_chapa_subscription'));
        
        // Use passed cohorts via customdata
        $customdata = $this->_customdata ?? array();
        $passedcohorts = $customdata['cohorts'] ?? array();
        $cohortoptions = array(0 => get_string('none'));
        foreach ($passedcohorts as $cohort) {
            $cohortoptions[$cohort->id] = $cohort->name;
        }
        
        $mform->addElement('select', 'free_preview_cohort', get_string('free_preview_cohort', 'local_chapa_subscription'), $cohortoptions);
        $mform->addHelpButton('free_preview_cohort', 'free_preview_cohort', 'local_chapa_subscription');
        
        $mform->addElement('select', 'basic_cohort', get_string('basic_cohort', 'local_chapa_subscription'), $cohortoptions);
        $mform->addHelpButton('basic_cohort', 'basic_cohort', 'local_chapa_subscription');
        
        $mform->addElement('select', 'standard_cohort', get_string('standard_cohort', 'local_chapa_subscription'), $cohortoptions);
        $mform->addHelpButton('standard_cohort', 'standard_cohort', 'local_chapa_subscription');
        
        $mform->addElement('select', 'premium_cohort', get_string('premium_cohort', 'local_chapa_subscription'), $cohortoptions);
        $mform->addHelpButton('premium_cohort', 'premium_cohort', 'local_chapa_subscription');
        
        // Invoice Settings section
        $mform->addElement('header', 'invoice_settings', get_string('invoice_settings', 'local_chapa_subscription'));
        
        $mform->addElement('selectyesno', 'enable_invoices', get_string('enable_invoices', 'local_chapa_subscription'));
        $mform->setDefault('enable_invoices', 1);
        $mform->addHelpButton('enable_invoices', 'enable_invoices', 'local_chapa_subscription');
        
        $mform->addElement('text', 'invoice_company_name', get_string('invoice_company_name', 'local_chapa_subscription'), array('size' => 50));
        $mform->setType('invoice_company_name', PARAM_TEXT);
        $mform->setDefault('invoice_company_name', 'LucyBridge Academy');
        $mform->addHelpButton('invoice_company_name', 'invoice_company_name', 'local_chapa_subscription');
        
        $mform->addElement('textarea', 'invoice_company_address', get_string('invoice_company_address', 'local_chapa_subscription'), array('rows' => 3, 'cols' => 50));
        $mform->setType('invoice_company_address', PARAM_TEXT);
        $mform->addHelpButton('invoice_company_address', 'invoice_company_address', 'local_chapa_subscription');
        
        $mform->addElement('text', 'invoice_company_phone', get_string('invoice_company_phone', 'local_chapa_subscription'), array('size' => 20));
        $mform->setType('invoice_company_phone', PARAM_TEXT);
        $mform->addHelpButton('invoice_company_phone', 'invoice_company_phone', 'local_chapa_subscription');
        
        $mform->addElement('text', 'invoice_company_email', get_string('invoice_company_email', 'local_chapa_subscription'), array('size' => 50));
        $mform->setType('invoice_company_email', PARAM_EMAIL);
        $mform->addHelpButton('invoice_company_email', 'invoice_company_email', 'local_chapa_subscription');
        
        // Email Templates section
        $mform->addElement('header', 'email_templates', get_string('email_templates', 'local_chapa_subscription'));
        
        $mform->addElement('textarea', 'renewal_reminder_template', get_string('renewal_reminder_template', 'local_chapa_subscription'), array('rows' => 5, 'cols' => 80));
        $mform->setType('renewal_reminder_template', PARAM_TEXT);
        $mform->setDefault('renewal_reminder_template', 'Dear {firstname} {lastname},\n\nThis is a reminder that your {plan} subscription will auto-renew on {enddate}. The upcoming charge is {amount} {currency}.\n\nIf you wish to make changes, please visit your subscription settings before the renewal date.\n\nBest regards,\n{site}');
        $mform->addHelpButton('renewal_reminder_template', 'renewal_reminder_template', 'local_chapa_subscription');
        
        $mform->addElement('textarea', 'renewal_success_template', get_string('renewal_success_template', 'local_chapa_subscription'), array('rows' => 5, 'cols' => 80));
        $mform->setType('renewal_success_template', PARAM_TEXT);
        $mform->setDefault('renewal_success_template', 'Dear {firstname} {lastname},\n\nYour {plan} subscription has been renewed successfully. Amount paid: {amount} {currency}\n\nYour subscription is now valid until {enddate}.\n\nBest regards,\n{site}');
        $mform->addHelpButton('renewal_success_template', 'renewal_success_template', 'local_chapa_subscription');
        
        $mform->addElement('textarea', 'renewal_failed_template', get_string('renewal_failed_template', 'local_chapa_subscription'), array('rows' => 5, 'cols' => 80));
        $mform->setType('renewal_failed_template', PARAM_TEXT);
        $mform->setDefault('renewal_failed_template', 'Dear {firstname} {lastname},\n\nYour {plan} subscription renewal failed. Please update your payment method and try again.\n\nBest regards,\n{site}');
        $mform->addHelpButton('renewal_failed_template', 'renewal_failed_template', 'local_chapa_subscription');
        
        $mform->addElement('textarea', 'subscription_expired_template', get_string('subscription_expired_template', 'local_chapa_subscription'), array('rows' => 5, 'cols' => 80));
        $mform->setType('subscription_expired_template', PARAM_TEXT);
        $mform->setDefault('subscription_expired_template', 'Dear {firstname} {lastname},\n\nYour {plan} subscription has expired. You have been moved to the Free Preview plan.\n\nTo continue with full access, please renew your subscription.\n\nBest regards,\n{site}');
        $mform->addHelpButton('subscription_expired_template', 'subscription_expired_template', 'local_chapa_subscription');

        // Receipt email template
        $mform->addElement('textarea', 'receipt_email_template', get_string('receipt_email_template', 'local_chapa_subscription'), array('rows' => 10, 'cols' => 80));
        $mform->setType('receipt_email_template', PARAM_RAW);
        $mform->setDefault('receipt_email_template', '<div style="font-family: Arial, sans-serif; max-width: 600px; margin:0 auto;">
  <div style="padding:16px; border-bottom:1px solid #eee;">
    <h2 style="margin:0;">Payment Receipt</h2>
    <div style="color:#666; font-size:12px;">{site}</div>
  </div>
  <div style="padding:16px;">
    <p>Hello {firstname},</p>
    <p>Thank you for your payment. Here are your receipt details:</p>
    <table style="width:100%; border-collapse: collapse;">
      <tr><td style="padding:8px 0; color:#555;">Plan</td><td style="padding:8px 0; text-align:right;"><strong>{plan}</strong></td></tr>
      <tr><td style="padding:8px 0; color:#555;">Amount</td><td style="padding:8px 0; text-align:right;"><strong>{amount} {currency}</strong></td></tr>
      <tr><td style="padding:8px 0; color:#555;">Duration</td><td style="padding:8px 0; text-align:right;">{duration}</td></tr>
      <tr><td style="padding:8px 0; color:#555;">Discount</td><td style="padding:8px 0; text-align:right;">{discount}</td></tr>
      <tr><td style="padding:8px 0; color:#555;">Payment Method</td><td style="padding:8px 0; text-align:right;">{method}</td></tr>
      <tr><td style="padding:8px 0; color:#555;">Reference</td><td style="padding:8px 0; text-align:right;">{reference}</td></tr>
      <tr><td style="padding:8px 0; color:#555;">Date</td><td style="padding:8px 0; text-align:right;">{date}</td></tr>
    </table>
    <p style="margin-top:16px;">
      <a href="{invoice_url}" style="background:#2563eb; color:white; text-decoration:none; padding:10px 16px; border-radius:6px; display:inline-block;">View Invoice</a>
    </p>
    <p style="color:#999; font-size:12px;">If you have any questions, reply to this email.</p>
  </div>
  <div style="padding:16px; border-top:1px solid #eee; color:#777; font-size:12px;">Regards,<br>{site}</div>
</div>');
        $mform->addHelpButton('receipt_email_template', 'receipt_email_template', 'local_chapa_subscription');
        
        $this->add_action_buttons();
    }
    
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        // Validate prices
        if ($data['basic_price'] <= 0) {
            $errors['basic_price'] = get_string('error_invalid_price', 'local_chapa_subscription');
        }
        if ($data['standard_price'] <= 0) {
            $errors['standard_price'] = get_string('error_invalid_price', 'local_chapa_subscription');
        }
        if ($data['premium_price'] <= 0) {
            $errors['premium_price'] = get_string('error_invalid_price', 'local_chapa_subscription');
        }
        
        // Validate discounts
        if ($data['discount_3_months'] < 0 || $data['discount_3_months'] > 100) {
            $errors['discount_3_months'] = get_string('error_invalid_discount', 'local_chapa_subscription');
        }
        if ($data['discount_6_months'] < 0 || $data['discount_6_months'] > 100) {
            $errors['discount_6_months'] = get_string('error_invalid_discount', 'local_chapa_subscription');
        }
        if ($data['discount_12_months'] < 0 || $data['discount_12_months'] > 100) {
            $errors['discount_12_months'] = get_string('error_invalid_discount', 'local_chapa_subscription');
        }
        
        return $errors;
    }
}

// Get available cohorts
$cohorts = $DB->get_records('cohort', array(), 'name ASC', 'id, name');

// Create form instance (pass cohorts via customdata)
$form = new chapa_subscription_settings_form(null, array('cohorts' => $cohorts));

// Handle form submission
if ($form->is_cancelled()) {
    redirect(new moodle_url('/admin/settings.php', array('section' => 'localplugins')));
} else if ($data = $form->get_data()) {
    // Save settings
    $settings = array(
        'sandbox_mode' => $data->sandbox_mode,
        'chapa_public_key' => $data->chapa_public_key,
        'chapa_secret_key' => $data->chapa_secret_key,
        'chapa_encryption_key' => $data->chapa_encryption_key,
        'basic_price' => $data->basic_price,
        'standard_price' => $data->standard_price,
        'premium_price' => $data->premium_price,
        'discount_3_months' => $data->discount_3_months,
        'discount_6_months' => $data->discount_6_months,
        'discount_12_months' => $data->discount_12_months,
        'free_preview_cohort' => $data->free_preview_cohort,
        'basic_cohort' => $data->basic_cohort,
        'standard_cohort' => $data->standard_cohort,
        'premium_cohort' => $data->premium_cohort,
        'enable_invoices' => $data->enable_invoices,
        'invoice_company_name' => $data->invoice_company_name,
        'invoice_company_address' => $data->invoice_company_address,
        'invoice_company_phone' => $data->invoice_company_phone,
        'invoice_company_email' => $data->invoice_company_email,
        'renewal_reminder_template' => $data->renewal_reminder_template,
        'renewal_success_template' => $data->renewal_success_template,
        'renewal_failed_template' => $data->renewal_failed_template,
        'subscription_expired_template' => $data->subscription_expired_template,
    );
    
    foreach ($settings as $name => $value) {
        $existing = $DB->get_record('local_chapa_settings', array('name' => $name));
        if ($existing) {
            $existing->value = $value;
            $existing->timemodified = time();
            $DB->update_record('local_chapa_settings', $existing);
        } else {
            $record = new stdClass();
            $record->name = $name;
            $record->value = $value;
            $record->timecreated = time();
            $record->timemodified = time();
            $DB->insert_record('local_chapa_settings', $record);
        }
    }
    
    // Update plan prices in database (convert ETB to cents)
    $plans = array(
        'basic' => $data->basic_price * 100, // Convert ETB to cents
        'standard' => $data->standard_price * 100, // Convert ETB to cents
        'premium' => $data->premium_price * 100, // Convert ETB to cents
    );
    
    foreach ($plans as $shortname => $price_cents) {
        $plan = $DB->get_record('local_chapa_plans', array('shortname' => $shortname));
        if ($plan) {
            $plan->monthlyprice = $price_cents;
            $plan->timemodified = time();
            $DB->update_record('local_chapa_plans', $plan);
        }
    }
    
    redirect($PAGE->url, get_string('success_settings_saved', 'local_chapa_subscription'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Load existing settings
$settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
$defaultdata = array();
foreach ($settings as $setting) {
    $defaultdata[$setting->name] = $setting->value;
}
$form->set_data($defaultdata);

// Output page
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('settings', 'local_chapa_subscription'));

$form->display();

echo $OUTPUT->footer();
