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
 * Admin reports page for the local_chapa_subscription plugin.
 *
 * @package    local_chapa_subscription
 * @copyright  2024 LucyBridge Academy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Check admin permissions
require_admin();

$PAGE->set_url('/local/chapa_subscription/admin/reports.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Subscription Reports');
$PAGE->set_heading('Subscription Reports');

// Navigation
$PAGE->navbar->add('Site administration', new moodle_url('/admin/search.php'));
$PAGE->navbar->add('Plugins', new moodle_url('/admin/plugins.php'));
$PAGE->navbar->add('Local plugins', new moodle_url('/admin/category.php?category=localplugins'));
$PAGE->navbar->add('Chapa Subscription', new moodle_url('/local/chapa_subscription/admin/reports.php'));
$PAGE->navbar->add('Reports');

// Get filter parameters
$plan_filter = optional_param('plan', '', PARAM_ALPHA);
$status_filter = optional_param('status', '', PARAM_ALPHA);
$date_from = optional_param('date_from', '', PARAM_TEXT);
$date_to = optional_param('date_to', '', PARAM_TEXT);
$search = optional_param('search', '', PARAM_TEXT);
$download = optional_param('download', '', PARAM_ALPHA);

// Build filter conditions
$where_conditions = array();
$params = array();

if ($plan_filter) {
    $where_conditions[] = "p.shortname = ?";
    $params[] = $plan_filter;
}

if ($status_filter) {
    if ($status_filter === 'active_pending_cancel') {
        $where_conditions[] = "ls.status = ? AND ls.auto_renew = 0";
        $params[] = 'active';
    } else {
        $where_conditions[] = "ls.status = ?";
        $params[] = $status_filter;
    }
}

if ($date_from) {
    $where_conditions[] = "ls.timecreated >= ?";
    $params[] = strtotime($date_from);
}

if ($date_to) {
    $where_conditions[] = "ls.timecreated <= ?";
    $params[] = strtotime($date_to . ' 23:59:59');
}

if ($search) {
    $where_conditions[] = "(u.firstname LIKE ? OR u.lastname LIKE ? OR u.email LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Latest subscription per user (for grouping by users)
$latestsql = "SELECT s1.*
              FROM {local_chapa_subscriptions} s1
              JOIN (
                SELECT userid, MAX(timecreated) AS maxtime
                FROM {local_chapa_subscriptions}
                GROUP BY userid
              ) sx ON s1.userid = sx.userid AND s1.timecreated = sx.maxtime";

// Get user-level report based on latest subscription
$sql = "SELECT ls.*, p.fullname as plan_name, p.shortname as plan_shortname, p.monthlyprice,
               u.firstname, u.lastname, u.email, u.username,
               u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename,
               (SELECT COUNT(1) FROM {local_chapa_payments} pay WHERE pay.userid = u.id) as payment_count,
               (SELECT COALESCE(SUM(pay2.amount),0)
                FROM {local_chapa_payments} pay2 WHERE pay2.userid = u.id AND pay2.chapa_status = 'success') as total_paid
        FROM ($latestsql) ls
        LEFT JOIN {local_chapa_plans} p ON ls.planid = p.id
        LEFT JOIN {user} u ON ls.userid = u.id
        $where_clause
        ORDER BY ls.timecreated DESC";

$subscriptions = $DB->get_records_sql($sql, $params);

// CSV export
if ($download === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=subscriptions_report.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, array('User', 'Email', 'Current Plan', 'Status', 'Start Date', 'Next Billing', 'Payments', 'Total Paid (ETB)'));
    foreach ($subscriptions as $sub) {
        $fullname = trim($sub->firstname . ' ' . $sub->lastname);
        $row = array(
            $fullname,
            $sub->email,
            $sub->plan_name,
            ucfirst($sub->status),
            date('Y-m-d H:i', $sub->timecreated),
            $sub->endtime ? date('Y-m-d', $sub->endtime) : 'N/A',
            (int)$sub->payment_count,
            number_format(($sub->total_paid / 100), 2)
        );
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// Get plan options for filter
$plans = $DB->get_records('local_chapa_plans', array(), 'fullname ASC');

// Get statistics based on latest subscription per user
$stats_sql = "SELECT 
    COUNT(*) as total_users,
    COUNT(CASE WHEN ls.status = 'active' THEN 1 END) as active_users,
    COUNT(CASE WHEN ls.status = 'cancelled' THEN 1 END) as cancelled_users,
    COUNT(CASE WHEN ls.status = 'expired' THEN 1 END) as expired_users,
    SUM(CASE WHEN ls.status = 'active' THEN p.monthlyprice ELSE 0 END) as monthly_revenue
    FROM ($latestsql) ls
    LEFT JOIN {local_chapa_plans} p ON ls.planid = p.id";

$stats = $DB->get_record_sql($stats_sql);

// Plan-wise status counts (based on latest subscription per user)
$plan_stats_sql = "SELECT p.fullname AS plan,
    COUNT(*) AS total,
    COUNT(CASE WHEN ls.status = 'active' THEN 1 END) AS active,
    COUNT(CASE WHEN ls.status = 'cancelled' THEN 1 END) AS cancelled,
    COUNT(CASE WHEN ls.status = 'expired' THEN 1 END) AS expired,
    COUNT(CASE WHEN ls.status = 'pending' THEN 1 END) AS pending
    FROM ($latestsql) ls
    LEFT JOIN {local_chapa_plans} p ON ls.planid = p.id
    GROUP BY p.fullname
    ORDER BY p.fullname";

$planstats = $DB->get_records_sql($plan_stats_sql);

echo $OUTPUT->header();

// Modern styles for stat cards
echo '<style>
.stat-card { border: 0; border-radius: 14px; color: #fff; box-shadow: 0 12px 24px rgba(0,0,0,.12); }
.stat-card .card-body { padding: 22px; }
.stat-card .card-title { font-weight: 700; letter-spacing: .2px; opacity: .95; }
.stat-card h3 { font-weight: 800; margin: 6px 0 0; }
.stat-total { background: linear-gradient(135deg, #4338CA 0%, #6366F1 100%); }
.stat-active { background: linear-gradient(135deg, #059669 0%, #10B981 100%); }
.stat-cancelled { background: linear-gradient(135deg, #F59E0B 0%, #F97316 100%); }
.stat-revenue { background: linear-gradient(135deg, #0EA5E9 0%, #06B6D4 100%); }
</style>';

// Statistics cards
echo '<div class="row mb-4">';
echo '<div class="col-md-3">';
echo '<div class="card stat-card stat-total">';
echo '<div class="card-body">';
echo '<h5 class="card-title">Total Users</h5>';
echo '<h3>' . $stats->total_users . '</h3>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<div class="card stat-card stat-active">';
echo '<div class="card-body">';
echo '<h5 class="card-title">Active Users</h5>';
echo '<h3>' . $stats->active_users . '</h3>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<div class="card stat-card stat-cancelled">';
echo '<div class="card-body">';
echo '<h5 class="card-title">Cancelled</h5>';
echo '<h3>' . $stats->cancelled_users . '</h3>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<div class="card stat-card stat-revenue">';
echo '<div class="card-body">';
echo '<h5 class="card-title">Monthly Revenue</h5>';
echo '<h3>' . number_format($stats->monthly_revenue / 100, 0) . ' ETB</h3>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

// Filters
echo '<div class="card mb-4">';
echo '<div class="card-header">';
echo '<h5>Filters</h5>';
echo '</div>';
echo '<div class="card-body">';
echo '<div class="d-flex justify-content-between mb-3">';
echo '<div><h5 class="m-0">Filters</h5></div>';
// Build CSV download URL preserving filters
$dlparams = $params;
$csvurl = new moodle_url('/local/chapa_subscription/admin/reports.php', array(
    'plan' => $plan_filter,
    'status' => $status_filter,
    'date_from' => $date_from,
    'date_to' => $date_to,
    'search' => $search,
    'download' => 'csv'
));
echo '<div><a class="btn btn-outline-success" href="' . $csvurl . '"><i class="fa fa-download"></i> Download CSV</a></div>';
echo '</div>';
echo '<form method="get" class="row">';

echo '<div class="col-md-2">';
echo '<label for="plan">Plan:</label>';
echo '<select name="plan" id="plan" class="form-control">';
echo '<option value="">All Plans</option>';
foreach ($plans as $plan) {
    $selected = ($plan_filter == $plan->shortname) ? 'selected' : '';
    echo "<option value=\"{$plan->shortname}\" $selected>{$plan->fullname}</option>";
}
echo '</select>';
echo '</div>';

echo '<div class="col-md-2">';
echo '<label for="status">Status:</label>';
echo '<select name="status" id="status" class="form-control">';
echo '<option value="">All Status</option>';
echo '<option value="active"' . ($status_filter == 'active' ? ' selected' : '') . '>Active</option>';
echo '<option value="active_pending_cancel"' . ($status_filter == 'active_pending_cancel' ? ' selected' : '') . '>Active (Pending cancellation)</option>';
echo '<option value="cancelled"' . ($status_filter == 'cancelled' ? ' selected' : '') . '>Cancelled</option>';
echo '<option value="expired"' . ($status_filter == 'expired' ? ' selected' : '') . '>Expired</option>';
echo '<option value="pending"' . ($status_filter == 'pending' ? ' selected' : '') . '>Pending</option>';
echo '</select>';
echo '</div>';

echo '<div class="col-md-2">';
echo '<label for="date_from">From Date:</label>';
echo '<input type="date" name="date_from" id="date_from" class="form-control" value="' . $date_from . '">';
echo '</div>';

echo '<div class="col-md-2">';
echo '<label for="date_to">To Date:</label>';
echo '<input type="date" name="date_to" id="date_to" class="form-control" value="' . $date_to . '">';
echo '</div>';

echo '<div class="col-md-2">';
echo '<label for="search">Search:</label>';
echo '<input type="text" name="search" id="search" class="form-control" placeholder="Name or email" value="' . $search . '">';
echo '</div>';

echo '<div class="col-md-2">';
echo '<label>&nbsp;</label>';
echo '<div>';
echo '<button type="submit" class="btn btn-primary">Filter</button>';
echo '<a href="' . $PAGE->url . '" class="btn btn-secondary ml-2">Clear</a>';
echo '</div>';
echo '</div>';

echo '</form>';
echo '</div>';
echo '</div>';

// Subscriptions table
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5>Users (grouped by latest subscription)</h5>';
echo '</div>';
echo '<div class="card-body">';

if (empty($subscriptions)) {
    echo '<div class="alert alert-info">No subscriptions found matching the criteria.</div>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>User</th>';
    echo '<th>Plan</th>';
    echo '<th>Status</th>';
    echo '<th>Start Date</th>';
    echo '<th>Next Billing</th>';
    echo '<th>Payments</th>';
    echo '<th>Total Paid</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($subscriptions as $sub) {
        echo '<tr>';
        echo '<td>';
        $userobj = new stdClass();
        $userobj->firstname = $sub->firstname;
        $userobj->lastname = $sub->lastname;
        $userobj->firstnamephonetic = $sub->firstnamephonetic;
        $userobj->lastnamephonetic = $sub->lastnamephonetic;
        $userobj->middlename = $sub->middlename;
        $userobj->alternatename = $sub->alternatename;
        echo '<strong>' . fullname($userobj) . '</strong><br>';
        echo '<small class="text-muted">' . s($sub->email) . '</small>';
        echo '</td>';
        echo '<td>';
        echo '<span class="badge badge-primary">' . $sub->plan_name . '</span><br>';
        echo '<small>' . number_format($sub->monthlyprice / 100, 0) . ' ETB/month</small>';
        echo '</td>';
        echo '<td>';
        $status_class = '';
        $status_label = ucfirst($sub->status);
        switch ($sub->status) {
            case 'active':
                if (isset($sub->auto_renew) && !$sub->auto_renew) {
                    $status_class = 'badge-warning';
                    $status_label = 'Active (Pending cancellation)';
                } else {
                    $status_class = 'badge-success';
                }
                break;
            case 'cancelled':
                $status_class = 'badge-danger';
                break;
            case 'expired':
                $status_class = 'badge-warning';
                break;
            case 'pending':
                $status_class = 'badge-info';
                break;
        }
        echo '<span class="badge ' . $status_class . '">' . $status_label . '</span>';
        echo '</td>';
        echo '<td>' . date('Y-m-d H:i', $sub->timecreated) . '</td>';
        echo '<td>' . ($sub->endtime ? date('Y-m-d', $sub->endtime) : 'N/A') . '</td>';
        echo '<td>' . (int)$sub->payment_count . '</td>';
        echo '<td>' . number_format(($sub->total_paid / 100), 0) . ' ETB</td>';
        echo '<td>';
        echo '<div class="btn-group" role="group">';
        $manageurl = new moodle_url('/local/chapa_subscription/admin/manage_subscription.php', array('id' => $sub->id));
        $paymentsurl = new moodle_url('/local/chapa_subscription/admin/user_payments.php', array('user_id' => $sub->userid));
        echo '<a href="' . $manageurl . '" class="btn btn-sm btn-outline-primary">Manage</a>';
        echo '<a href="' . $paymentsurl . '" class="btn btn-sm btn-outline-info">Payments</a>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

echo '</div>';
echo '</div>';

// Plan-wise status table
echo '<div class="card mt-4">';
echo '<div class="card-header">';
echo '<h5>Plan-wise Status (latest subscription per user)</h5>';
echo '</div>';
echo '<div class="card-body">';
if (empty($planstats)) {
    echo '<div class="text-muted">No data.</div>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped">';
    echo '<thead><tr><th>Plan</th><th>Total Users</th><th>Active</th><th>Cancelled</th><th>Expired</th><th>Pending</th></tr></thead>';
    echo '<tbody>';
    foreach ($planstats as $row) {
        echo '<tr>';
        echo '<td>' . s($row->plan) . '</td>';
        echo '<td>' . (int)$row->total . '</td>';
        echo '<td>' . (int)$row->active . '</td>';
        echo '<td>' . (int)$row->cancelled . '</td>';
        echo '<td>' . (int)$row->expired . '</td>';
        echo '<td>' . (int)$row->pending . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}
echo '</div>';
echo '</div>';

echo $OUTPUT->footer();
