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
 * Modern subscription page with pricing table
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

// Check if user is logged in
require_login();

$PAGE->set_url('/local/chapa_subscription/subscribe.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('embedded'); // Minimal layout (hide header/sidebar/footer)
$PAGE->set_title(get_string('subscribe', 'local_chapa_subscription'));
$PAGE->set_heading(get_string('subscribe', 'local_chapa_subscription'));

// Navigation
$PAGE->navbar->add(get_string('pluginname', 'local_chapa_subscription'));
$PAGE->navbar->add(get_string('subscribe', 'local_chapa_subscription'));

// Get user's current subscription (latest active if multiples exist)
$current_records = $DB->get_records('local_chapa_subscriptions', 
    array('userid' => $USER->id, 'status' => 'active'), 'timecreated DESC', '*', 0, 1);
$current_subscription = $current_records ? reset($current_records) : null;
$current_plan_shortname = '';
if ($current_subscription) {
    $current_plan_rec = $DB->get_record('local_chapa_plans', array('id' => $current_subscription->planid));
    if ($current_plan_rec) {
        $current_plan_shortname = $current_plan_rec->shortname; // basic/standard/premium
    }
}

// Get available plans
$plans = $DB->get_records('local_chapa_plans', array(), 'monthlyprice ASC');

// Get settings
$settings = $DB->get_records('local_chapa_settings', array(), '', 'name, value');
$config = array();
foreach ($settings as $setting) {
    $config[$setting->name] = $setting->value;
}

// Get plan prices from admin settings (primary source)
// Admin settings store prices in ETB, convert to cents for database consistency
$plan_prices = array();
$plan_prices['basic'] = ($config['basic_price'] ?? 249) * 100; // Convert ETB to cents
$plan_prices['standard'] = ($config['standard_price'] ?? 299) * 100; // Convert ETB to cents
$plan_prices['premium'] = ($config['premium_price'] ?? 349) * 100; // Convert ETB to cents

// Ensure plans table is in sync with settings
$plans_db = $DB->get_records('local_chapa_plans', array(), 'monthlyprice ASC');
foreach ($plans_db as $plan) {
    if (isset($plan_prices[$plan->shortname]) && $plan->monthlyprice != $plan_prices[$plan->shortname]) {
        // Update plan table if it's out of sync
        $plan->monthlyprice = $plan_prices[$plan->shortname];
        $plan->timemodified = time();
        $DB->update_record('local_chapa_plans', $plan);
    }
}

// Plan data with features and exclusions
$plan_data = array(
    'basic' => array(
        'name' => 'Basic Plan',
        'price' => $plan_prices['basic'] ?? 24900,
        'monthly_price' => ($plan_prices['basic'] ?? 24900) / 100,
        'features' => array(
            'Full access to video lessons',
            'Short notes',
            'Basic support'
        ),
        'exclusions' => array(
            'No access to AI assistant',
            'No Question Bank',
            'No review/entrance exam videos',
            'No special Telegram channel'
        ),
        'color' => 'blue',
        'description' => 'Perfect for getting started with your learning journey'
    ),
    'standard' => array(
        'name' => 'Standard Plan',
        'price' => $plan_prices['standard'] ?? 29900,
        'monthly_price' => ($plan_prices['standard'] ?? 29900) / 100,
        'features' => array(
            'Full access to video lessons',
            'Short notes',
            'Access to AI assistant',
            'Review Question Videos',
            'Entrance Exam Question Videos',
            'Question Bank'
        ),
        'exclusions' => array(
            'No special Telegram channel',
            'No tailored question responses'
        ),
        'color' => 'purple',
        'popular' => true,
        'description' => 'Most popular choice with advanced features'
    ),
    'premium' => array(
        'name' => 'Premium Plan',
        'price' => $plan_prices['premium'] ?? 34900,
        'monthly_price' => ($plan_prices['premium'] ?? 34900) / 100,
        'features' => array(
            'Full access to video lessons',
            'Short notes',
            'Access to AI assistant',
            'Review Question Videos',
            'Entrance Exam Question Videos',
            'Question Bank',
            'Access to special Telegram channel',
            'Ability to forward questions and receive tailored responses',
            'Priority support'
        ),
        'exclusions' => array(),
        'color' => 'green',
        'description' => 'Complete access with premium support and features'
    )
);

// Duration options with discounts from settings
$duration_options = array(
    'monthly' => array('months' => 1, 'discount' => 0, 'label' => 'Monthly'),
    'quarterly' => array('months' => 3, 'discount' => $config['discount_3_months'] ?? 10, 'label' => '3 Months'),
    'semiannual' => array('months' => 6, 'discount' => $config['discount_6_months'] ?? 25, 'label' => '6 Months'),
    'annual' => array('months' => 12, 'discount' => $config['discount_12_months'] ?? 40, 'label' => '12 Months')
);

// Output page
echo $OUTPUT->header();

// Tailwind
echo '<script src="https://cdn.tailwindcss.com"></script>';
echo '<script>
  tailwind.config = { theme: { extend: { colors: { primary: "rgb(36, 116, 254)", secondary: "rgb(59, 130, 246)" }}}}
</script>';

// Enhanced Styles
echo '<style>
  .pricing-card {
    transition: all .4s cubic-bezier(0.4, 0, 0.2, 1);
    transform: translateY(0);
    display: flex;
    flex-direction: column;
    height: 100%;
    border: 2px solid transparent;
    position: relative;
    overflow: visible; /* Ensure badges like Most Popular are visible */
  }
  
  .pricing-card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
  }
  
  .pricing-card:hover {
    transform: translateY(-12px);
    box-shadow: 0 25px 50px rgba(36,116,254,.2);
    border-color: rgb(36,116,254);
  }
  
  .pricing-card:hover::before {
    opacity: 1;
  }
  
  .pricing-card.popular {
    transform: scale(1.05);
    border-color: #f59e0b;
    box-shadow: 0 20px 40px rgba(245,158,11,.2);
  }
  
  .pricing-card.popular::before {
    background: linear-gradient(90deg, #f59e0b 0%, #f97316 100%);
    opacity: 1;
    display: none;
  }
  
  .pricing-card.popular:hover {
    transform: scale(1.05) translateY(-12px);
    box-shadow: 0 30px 60px rgba(245,158,11,.3);
  }
  
  .pricing-card .card-content {
    flex: 1;
    display: flex;
    flex-direction: column;
  }
  
  .pricing-card .subscribe-section {
    margin-top: auto;
  }
  
  .feature-item {
    opacity: 0;
    transform: translateX(-20px);
    animation: slideIn .6s ease forwards;
    transition: all 0.3s ease;
  }
  
  .feature-item:hover {
    transform: translateX(5px);
    color: rgb(36,116,254);
  }
  
  .feature-item:nth-child(1) { animation-delay: .1s; }
  .feature-item:nth-child(2) { animation-delay: .2s; }
  .feature-item:nth-child(3) { animation-delay: .3s; }
  .feature-item:nth-child(4) { animation-delay: .4s; }
  .feature-item:nth-child(5) { animation-delay: .5s; }
  .feature-item:nth-child(6) { animation-delay: .6s; }
  .feature-item:nth-child(7) { animation-delay: .7s; }
  .feature-item:nth-child(8) { animation-delay: .8s; }
  .feature-item:nth-child(9) { animation-delay: .9s; }
  .feature-item:nth-child(10) { animation-delay: 1s; }
  
  @keyframes slideIn {
    to { opacity: 1; transform: translateX(0); }
  }
  
  .gradient-bg {
    background: linear-gradient(135deg, rgb(36,116,254) 0%, rgb(59,130,246) 50%, rgb(99,102,241) 100%);
    position: relative;
  }
  
  .gradient-bg::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url("data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'0.05\'%3E%3Ccircle cx=\'30\' cy=\'30\' r=\'2\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
    opacity: 0.3;
  }
  
  .pulse-animation {
    animation: pulse 2s infinite;
  }
  
  @keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
  }
  
  .price-display {
    transition: all 0.3s ease;
  }
  
  .subscribe-btn {
    position: relative;
    overflow: hidden;
  }
  
  .subscribe-btn::before {
    content: "";
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
  }
  
  .subscribe-btn:hover::before {
    left: 100%;
  }
  
  .duration-toggle {
    transition: all 0.3s ease;
    position: relative;
  }
  
  .duration-toggle:hover {
    transform: translateY(-2px);
  }
  
  .duration-toggle.active {
    background: linear-gradient(135deg, rgb(36,116,254), rgb(59,130,246));
    color: white;
    box-shadow: 0 4px 15px rgba(36,116,254,0.3);
  }
</style>';

echo '<div class="min-h-screen gradient-bg py-12 px-4 sm:px-6 lg:px-8">';
echo '<div class="max-w-7xl mx-auto">';

echo '<div class="text-center mb-16 relative z-10">';
echo '<div class="mb-8">';
echo '<h1 class="text-5xl md:text-7xl font-bold text-white mb-6 bg-gradient-to-r from-white to-blue-100 bg-clip-text text-transparent">Choose Your Learning Plan</h1>';
echo '<p class="text-xl md:text-2xl text-blue-100 max-w-4xl mx-auto leading-relaxed">Unlock your potential with our comprehensive learning platform. Select the plan that fits your needs and start your journey to success.</p>';
echo '</div>';

// Add some visual elements
echo '<div class="flex justify-center items-center space-x-4 mb-8">';
echo '<div class="w-16 h-1 bg-gradient-to-r from-transparent via-white to-transparent"></div>';
echo '<div class="w-3 h-3 bg-white rounded-full animate-pulse"></div>';
echo '<div class="w-16 h-1 bg-gradient-to-r from-transparent via-white to-transparent"></div>';
echo '</div>';

echo '</div>';

// Enhanced Toggle Section
echo '<div class="bg-white rounded-3xl shadow-2xl p-6 md:p-8 mb-8 border-2 border-gray-100 max-w-4xl mx-auto w-full">';
echo '<div class="text-center mb-8">';
echo '<h2 class="text-3xl font-bold text-gray-900 mb-4">Save More with Upfront Payments</h2>';
echo '<p class="text-gray-600 text-lg">Choose your payment duration and save money with our special discounts!</p>';
echo '</div>';

echo '<div class="flex justify-center">';
echo '<div class="w-full sm:w-auto overflow-x-auto">';
echo '<div class="bg-gradient-to-r from-gray-100 to-gray-200 p-2 rounded-2xl inline-flex shadow-inner min-w-max gap-2">';
foreach ($duration_options as $dkey => $d) {
    $active = $dkey === 'monthly' ? 'bg-white shadow-lg active' : '';
    $discount_text = $d['discount'] > 0 ? ' <span class="text-green-600 text-sm font-bold">('.$d['discount'].'% OFF)</span>' : '';
    echo '<button onclick="changeDuration(\''.$dkey.'\')" class="duration-toggle px-4 py-3 md:px-8 md:py-4 text-sm md:text-base rounded-xl font-bold transition-all duration-300 hover:scale-105 shrink-0 '.$active.'" data-duration="'.$dkey.'">';
    echo '<span class="flex items-center">';
    echo '<span class="text-lg">'.$d['label'].'</span>';
    if ($d['discount'] > 0) {
        echo '<span class="ml-2 bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs font-bold">'.$d['discount'].'% OFF</span>';
    }
    echo '</span>';
    echo '</button>';
}
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>'; 

// Enhanced Pricing Cards
echo '<div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8 mb-16">';
foreach ($plan_data as $pkey => $plan) {
    $popular = !empty($plan['popular']);
    $cardcls = $popular ? 'pricing-card popular' : 'pricing-card';
    $is_current = ($current_plan_shortname === $pkey);
    $is_lower = ($current_plan_shortname && (($pkey === 'basic' && $current_plan_shortname !== 'basic') || ($pkey === 'standard' && $current_plan_shortname === 'premium')));
    
    echo '<div class="'.$cardcls.' bg-white rounded-3xl shadow-2xl relative border-2 border-gray-100">';
    echo '<div class="card-content">';
    
    // Popular badge
    if ($popular) {
    echo '<div class="absolute -top-3 left-1/2 transform -translate-x-1/2 z-20 pointer-events-none">';
        echo '<span class="bg-gradient-to-r from-yellow-400 to-orange-500 text-white px-6 py-2 rounded-full text-sm font-bold shadow-lg pulse-animation">';
        echo '⭐ Most Popular';
        echo '</span>';
        echo '</div>';
    }
    
    // Card header with enhanced styling
    echo '<div class="p-8 text-center relative">';
    echo '<div class="mb-4">';
    echo '<h3 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">'.$plan['name'].'</h3>';
    echo '<p class="text-gray-600 text-sm">'.$plan['description'].'</p>';
    echo '</div>';
    
    // Enhanced price display
    echo '<div class="mb-6">';
    echo '<div class="price-display" data-plan="'.$pkey.'">';
    echo '<div class="flex items-baseline justify-center">';
    echo '<span class="text-4xl md:text-6xl font-bold text-primary">'.number_format($plan['monthly_price']).'</span>';
    echo '<span class="text-gray-600 ml-2 text-lg">ETB/month</span>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // Features section with enhanced styling
    echo '<div class="px-8 pb-4 flex-1">';
    echo '<div class="mb-6">';
    echo '<h4 class="text-xl font-bold text-gray-900 mb-4 flex items-center">';
    echo '<svg class="w-6 h-6 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">';
    echo '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>';
    echo '</svg>';
    echo 'What\'s Included';
    echo '</h4>';
    echo '<ul class="space-y-3">';
    foreach ($plan['features'] as $feat) {
        echo '<li class="feature-item flex items-start group">';
        echo '<svg class="w-5 h-5 text-green-500 mr-3 mt-0.5 flex-shrink-0 group-hover:text-green-600 transition-colors" fill="currentColor" viewBox="0 0 20 20">';
        echo '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>';
        echo '</svg>';
        echo '<span class="text-gray-700 group-hover:text-gray-900 transition-colors">'.$feat.'</span>';
        echo '</li>';
    }
    echo '</ul>';
    echo '</div>';
    
    // Exclusions section (if any)
    if (!empty($plan['exclusions'])) {
        echo '<div class="border-t pt-4">';
        echo '<h4 class="text-lg font-semibold text-gray-900 mb-3 flex items-center">';
        echo '<svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">';
        echo '<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>';
        echo '</svg>';
        echo 'Not Included';
        echo '</h4>';
        echo '<ul class="space-y-2">';
        foreach ($plan['exclusions'] as $exc) {
            echo '<li class="flex items-start text-gray-500">';
            echo '<svg class="w-4 h-4 text-red-400 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">';
            echo '<path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>';
            echo '</svg>';
            echo '<span class="text-sm">'.$exc.'</span>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
    echo '</div>';
    echo '</div>'; // card-content
    
    // Enhanced subscribe button section
    echo '<div class="subscribe-section px-8 pb-8">';
    if ($is_current) {
        echo '<div class="bg-gradient-to-r from-green-100 to-green-200 border-2 border-green-300 rounded-xl p-4 text-center">';
        echo '<div class="flex items-center justify-center mb-2">';
        echo '<svg class="w-6 h-6 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">';
        echo '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>';
        echo '</svg>';
        echo '<span class="text-green-800 font-bold text-lg">Current Plan</span>';
        echo '</div>';
        echo '<p class="text-green-700 text-sm">You are currently subscribed to this plan</p>';
        echo '</div>';
    } elseif ($is_lower) {
        echo '<div class="bg-gray-100 border-2 border-gray-300 rounded-xl p-4 text-center">';
        echo '<span class="text-gray-600 font-bold text-lg">Not Available</span>';
        echo '<p class="text-gray-500 text-sm mt-1">This plan is lower than your current subscription</p>';
        echo '</div>';
    } else {
        $label = $current_plan_shortname ? 'Upgrade Now' : 'Subscribe Now';
        $button_class = $popular ? 'bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600' : 'bg-gradient-to-r from-primary to-blue-600 hover:from-blue-600 hover:to-blue-700';
        echo '<a href="initiate_payment.php?plan='.$pkey.'&duration=monthly" class="block w-full '.$button_class.' text-white font-bold py-4 px-6 rounded-xl transition duration-300 transform hover:scale-105 text-center subscribe-btn shadow-lg hover:shadow-xl" data-plan="'.$pkey.'">';
        echo '<span class="flex items-center justify-center">';
        echo '<svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">';
        echo '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.293l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13a1 1 0 102 0V9.414l1.293 1.293a1 1 0 001.414-1.414z" clip-rule="evenodd"></path>';
        echo '</svg>';
        echo $label;
        echo '</span>';
        echo '</a>';
    }
    echo '</div>';
    echo '</div>'; // card
}
echo '</div>';

// Full-width CTA below all plans (dynamic based on subscription)
$has_active = !empty($current_subscription);
$cta_title = '';
$cta_desc = '';
$cta_classes = '';
if ($has_active) {
    $planrec = $DB->get_record('local_chapa_plans', array('id' => $current_subscription->planid));
    $planname = $planrec ? $planrec->fullname : ($current_plan_shortname ? ucfirst($current_plan_shortname).' Plan' : 'Your plan');
    $priceetb = $planrec ? number_format(($planrec->monthlyprice / 100), 0) : '';
    $cta_title = "I'm good — take me to Dashboard";
    $cta_desc = 'You\'re currently on ' . s($planname) . ($priceetb ? ' (' . $priceetb . ' ETB/month)' : '') . '. You can upgrade or manage your subscription anytime from your dashboard.';
    $cta_classes = 'bg-white border-blue-200 text-blue-700 hover:bg-blue-50';
} else {
    $cta_title = "I'll stay on Free plan";
    $cta_desc = 'Continue with Free Preview access. Explore selected content for free and upgrade anytime for full features.';
    $cta_classes = 'bg-white border-gray-300 text-gray-800 hover:bg-gray-50';
}

echo '<div class="mb-12">';
// Target URL depends on whether the user has an active plan
$cta_href = $has_active ? '/my/' : '/?redirect=0';
echo '  <a href="' . $cta_href . '" class="block w-full text-center py-5 px-6 rounded-2xl font-bold transition duration-300 transform hover:scale-[1.01] shadow-lg hover:shadow-xl border-2 ' . $cta_classes . '">';
echo '    <div class="text-lg md:text-xl">' . $cta_title . '</div>';
echo '    <div class="text-sm font-normal mt-1 opacity-80">' . $cta_desc . '</div>';
echo '  </a>';
echo '</div>';

// Enhanced JS for dynamic pricing
echo '<script>
// Data from PHP
const durationOptions = '.json_encode($duration_options).';
const planData = '.json_encode($plan_data).';

// console.debug("durationOptions", durationOptions);
// console.debug("planData", planData);

function changeDuration(duration) {
  // console.debug("changeDuration", duration);
  
  // Update toggle buttons
  document.querySelectorAll(".duration-toggle").forEach(function(btn) {
    btn.classList.remove("bg-white", "shadow-sm", "active");
    btn.classList.add("text-gray-600");
  });
  
  var activeBtn = document.querySelector("[data-duration=\"" + duration + "\"]");
  if (activeBtn) {
    activeBtn.classList.add("bg-white", "shadow-sm", "active");
    activeBtn.classList.remove("text-gray-600");
  }
  
  // Update pricing for each plan
  Object.keys(planData).forEach(function(planKey) {
    var plan = planData[planKey];
    var durationInfo = durationOptions[duration];
    var basePrice = plan.monthly_price;
    var months = durationInfo.months;
    var discount = durationInfo.discount;
    var totalPrice = basePrice * months;
    var finalPrice = totalPrice - (totalPrice * (discount / 100));
    
    var priceElement = document.querySelector("[data-plan=\"" + planKey + "\"].price-display");
    if (!priceElement) {
      // console.debug("Price element not found for plan", planKey);
      return;
    }
    
    if (duration === "monthly") {
      priceElement.innerHTML = "<div class=\"flex items-baseline justify-center\"><span class=\"text-6xl font-bold text-primary\">" + basePrice.toLocaleString() + "</span><span class=\"text-gray-600 ml-2 text-lg\">ETB/month</span></div>";
    } else {
      var monthlyEquivalent = finalPrice / months;
      var billingText = "";
      if (months === 3) billingText = "billed every 3 months";
      else if (months === 6) billingText = "billed every 6 months";
      else if (months === 12) billingText = "billed annually";
      
      priceElement.innerHTML = "<div class=\"text-center\"><div class=\"flex items-baseline justify-center mb-2\"><span class=\"text-6xl font-bold text-primary\">" + monthlyEquivalent.toFixed(0) + "</span><span class=\"text-gray-600 ml-2 text-lg\">ETB/month</span></div><div class=\"text-sm text-gray-500\">" + billingText + "</div><div class=\"text-green-600 text-sm font-bold mt-1\">Save " + discount + "%</div></div>";
    }
    
    // console.debug("Updated pricing for", planKey, duration, finalPrice);
    
    // Update subscribe button URL
    var subscribeBtn = document.querySelector("[data-plan=\"" + planKey + "\"].subscribe-btn");
    if (subscribeBtn) {
      subscribeBtn.href = "initiate_payment.php?plan=" + planKey + "&duration=" + duration;
    }
  });
}

// Initialize when DOM is ready
document.addEventListener("DOMContentLoaded", function() {
  // console.debug("init pricing");
  changeDuration("monthly");
});

// Also try immediate execution as fallback
setTimeout(function() {
  if (typeof changeDuration === "function") {
    // console.debug("fallback init");
    changeDuration("monthly");
  }
}, 100);
</script>';

echo $OUTPUT->footer();