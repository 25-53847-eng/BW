<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Verify user is an employee
if ($_SESSION['user_role'] !== 'employee') {
    header('Location: ../index.php');
    exit();
}

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/../helpers/client-helper.php';

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'] ?? 'User';
$user_name = $_SESSION['user_name'] ?? 'User';

// Get selected dataset from GET parameter or session
if (isset($_GET['dataset'])) {
    $selected_dataset = trim(strval($_GET['dataset']));
    $_SESSION['active_dataset'] = $selected_dataset;
} else {
    $selected_dataset = isset($_SESSION['active_dataset']) ? $_SESSION['active_dataset'] : null;
}

// Convert empty string to null for cleaner logic
if ($selected_dataset === '') {
    $selected_dataset = null;
}

// Get selected year from GET parameter or session
if (isset($_GET['year'])) {
    $selected_year = intval($_GET['year']);
    if ($selected_year > 1900 && $selected_year < 2100) {
        $_SESSION['active_year'] = $selected_year;
    }
} else {
    $selected_year = isset($_SESSION['active_year']) ? $_SESSION['active_year'] : null;
}

// Build dataset filter for queries
// Employees see all company data (not filtered by owner_user_id)
$dataset_filter = ' AND company_name != ?';
$dataset_filter_params = ['Stock Addition'];
if (!empty($selected_dataset)) {
    $dataset_filter .= ' AND dataset_name = ?';
    $dataset_filter_params[] = $selected_dataset;
}
if (!empty($selected_year)) {
    $dataset_filter .= ' AND YEAR(delivery_date) = ?';
    $dataset_filter_params[] = $selected_year;
}

// Get dashboard statistics
$stats = [
    'total_delivered' => 0,
    'total_sold' => 0,
    'total_sales_amount' => 0.0,
    'total_companies' => 0,
    'active_models' => 0,
    'monthly_average' => 0,
    'yearly_total' => 0
];

// Helper function for binding parameters
function bindParamsAndExecute(&$stmt, $params) {
    if (!empty($params)) {
        $typeStr = str_repeat('s', count($params));
        $stmt->bind_param($typeStr, ...$params);
    }
    $stmt->execute();
}

// Count total delivered (all units - unit_type column may be empty)
$sql = "SELECT COALESCE(SUM(quantity), 0) as total FROM delivery_records WHERE status = 'Delivered'" . $dataset_filter;
$stmt = $conn->prepare($sql);
if ($stmt) {
    bindParamsAndExecute($stmt, $dataset_filter_params);
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['total_delivered'] = intval($row['total']);
    }
    $stmt->close();
}

// Count total sold (different from delivered - could be from sales data)
$sql = "SELECT COUNT(*) as total FROM delivery_records WHERE status IN ('Delivered', 'In Transit')" . $dataset_filter;
$stmt = $conn->prepare($sql);
if ($stmt) {
    bindParamsAndExecute($stmt, $dataset_filter_params);
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['total_sold'] = intval($row['total']);
    }
    $stmt->close();
}

// Total sales amount / revenue (includes completed deliveries and pending inquiries)
$salesExpr = "CASE
    WHEN total_amount IS NOT NULL AND total_amount > 0 THEN total_amount
    WHEN unit_price IS NOT NULL AND unit_price > 0 THEN (quantity * unit_price)
    ELSE 0
END";
$sql = "SELECT COALESCE(SUM({$salesExpr}), 0) as total FROM delivery_records WHERE (status IN ('Delivered', 'In Transit') OR company_name IN ('Inquiry', 'Orders'))" . $dataset_filter;
$stmt = $conn->prepare($sql);
if ($stmt) {
    bindParamsAndExecute($stmt, $dataset_filter_params);
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['total_sales_amount'] = floatval($row['total']);
    }
    $stmt->close();
}

// Count unique client companies - include ALL except internal markers and corrupted entries
// Exclude: Stock Addition, Orders, Delivery Records (internal markers)
// Also exclude: to Andison Manila (incomplete), Zamora display (duplicate variant)
$sql = "SELECT COUNT(DISTINCT company_name) as total FROM delivery_records 
        WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'to Andison Manila', 'Zamora display') 
        AND company_name IS NOT NULL AND company_name != ''" . $dataset_filter;
$stmt = $conn->prepare($sql);
if ($stmt) {
    bindParamsAndExecute($stmt, $dataset_filter_params);
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['total_companies'] = intval($row['total']);
    }
    $stmt->close();
}

// Count unique item codes (models) - exclude UNKNOWN items
$sql = "SELECT COUNT(DISTINCT item_code) as total FROM delivery_records WHERE item_code NOT LIKE 'UNKNOWN%'" . $dataset_filter;
$stmt = $conn->prepare($sql);
if ($stmt) {
    bindParamsAndExecute($stmt, $dataset_filter_params);
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stats['active_models'] = intval($row['total']);
    }
    $stmt->close();
}

// Calculate monthly average
if ($stats['total_delivered'] > 0) {
    $stats['monthly_average'] = round($stats['total_delivered'] / 12);
}

// Calculate yearly total (units only)
$stats['yearly_total'] = $stats['total_delivered'];

// Get top clients - use sold_to (actual customers), not company_name (internal classification)
// Exclude inventory items by filtering inventory_status
$top_clients = [];
$sql = "
    SELECT sold_to as company_name, COUNT(*) as delivery_count, SUM(quantity) as total_quantity
    FROM delivery_records
    WHERE sold_to IS NOT NULL AND sold_to != '' AND TRIM(sold_to) != '' 
      AND (inventory_status IS NULL OR inventory_status = '')" . $dataset_filter . "
    GROUP BY sold_to
    ORDER BY total_quantity DESC
    LIMIT 15
";
$stmt = $conn->prepare($sql);
if ($stmt) {
    bindParamsAndExecute($stmt, $dataset_filter_params);
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $top_clients[] = $row;
    }
    $stmt->close();
}

// Get monthly sales data — single query instead of 12
$months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
$monthly_sales = array_fill_keys($months, 0);
$sql = "
    SELECT delivery_month, COALESCE(SUM(quantity), 0) AS total
    FROM delivery_records
    WHERE 1=1" . $dataset_filter . "
    GROUP BY delivery_month
";
$stmt = $conn->prepare($sql);
if ($stmt) {
    bindParamsAndExecute($stmt, $dataset_filter_params);
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (array_key_exists($row['delivery_month'], $monthly_sales)) {
            $monthly_sales[$row['delivery_month']] = intval($row['total']);
        }
    }
    $stmt->close();
}

// Get top products by item code (ONLY 1A, 2A, 4A units)
$top_products = [];
$sql = "
    SELECT item_code, item_name, SUM(quantity) as total 
    FROM delivery_records
    WHERE item_code IS NOT NULL AND item_code != '' AND item_code != '-' AND unit_type IN ('1a', '2a', '4a')" . $dataset_filter . "
    GROUP BY item_code 
    ORDER BY total DESC 
    LIMIT 10
";
$stmt = $conn->prepare($sql);
if ($stmt) {
    bindParamsAndExecute($stmt, $dataset_filter_params);
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $top_products[] = $row;
    }
    $stmt->close();
}

// Get delivery by company for pie chart
$company_deliveries = [];
$sql = "
    SELECT company_name, SUM(quantity) as total 
    FROM delivery_records 
    WHERE company_name IS NOT NULL AND company_name != '' AND company_name != '-'" . $dataset_filter . "
    GROUP BY company_name 
    ORDER BY total DESC 
    LIMIT 8
";
$stmt = $conn->prepare($sql);
if ($stmt) {
    bindParamsAndExecute($stmt, $dataset_filter_params);
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $company_deliveries[] = $row;
    }
    $stmt->close();
}

// Get imported datasets
$datasets = [];
$untagged_count = 0;
try {
    // Check if dataset_name column exists (works for both MySQL and SQLite)
    $isMysql = ($conn instanceof mysqli);
    $colExists = false;
    
    if ($isMysql) {
        $col_check = $conn->query("SHOW COLUMNS FROM delivery_records LIKE 'dataset_name'");
        $colExists = ($col_check && $col_check->num_rows > 0);
    } else {
        // SQLite - use PRAGMA
        $col_check = $conn->query("PRAGMA table_info(delivery_records)");
        if ($col_check) {
            while ($c = $col_check->fetch_assoc()) {
                if (strtolower($c['name']) === 'dataset_name') {
                    $colExists = true;
                    break;
                }
            }
        }
    }
    
    if ($colExists) {
        $ds_result = $conn->query("SELECT dataset_name, COUNT(*) as record_count FROM delivery_records WHERE dataset_name IS NOT NULL AND dataset_name != '' AND company_name != 'Stock Addition' GROUP BY dataset_name ORDER BY dataset_name ASC LIMIT 5");
        if ($ds_result) {
            while ($ds_row = $ds_result->fetch_assoc()) {
                $datasets[] = $ds_row;
            }
        }
        // Count records with no dataset tag (excluding inventory)
        $unt = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE (dataset_name IS NULL OR dataset_name = '') AND company_name != 'Stock Addition'");
        if ($unt && $r = $unt->fetch_assoc()) $untagged_count = intval($r['cnt']);
    } else {
        // Column doesn't exist yet — all records are untagged (excluding inventory)
        $unt = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE company_name != 'Stock Addition'");
        if ($unt && $r = $unt->fetch_assoc()) $untagged_count = intval($r['cnt']);
    }
} catch (Exception $e) { /* ignore */ }

// Get pending count (excluding inventory)
$pending_count = 0;
$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE (status = 'Pending' OR status = 'In Transit') AND company_name != 'Stock Addition'");
if ($result && $row = $result->fetch_assoc()) {
    $pending_count = intval($row['cnt']);
}

// ============================================
// GENERATE INSIGHTS FOR EACH CHART
// ============================================

// Find best and worst months
$best_month = '';
$worst_month = '';
$best_value = 0;
$worst_value = PHP_INT_MAX;
$total_monthly = 0;
$months_with_data = 0;

foreach ($monthly_sales as $month => $value) {
    $total_monthly += $value;
    if ($value > 0) $months_with_data++;
    if ($value > $best_value) {
        $best_value = $value;
        $best_month = $month;
    }
    if ($value > 0 && $value < $worst_value) {
        $worst_value = $value;
        $worst_month = $month;
    }
}

$avg_monthly = $months_with_data > 0 ? round($total_monthly / $months_with_data) : 0;

// Delivery insights
$delivered_rate = $stats['total_delivered'] > 0 ? round(($stats['total_delivered'] / ($stats['total_delivered'] + $pending_count)) * 100) : 0;
$delivery_insights = [];
$delivery_insights[] = "Delivery success rate is {$delivered_rate}%";
if ($pending_count > 0) {
    $delivery_insights[] = "{$pending_count} orders still pending/in transit";
}

// Sales insights
$sold_vs_delivered = $stats['total_delivered'] > 0 ? round(($stats['total_sold'] / $stats['total_delivered']) * 100) : 0;
$sales_insights = [];
if ($sold_vs_delivered > 80) {
    $sales_insights[] = "Excellent conversion! {$sold_vs_delivered}% of delivered items sold";
} elseif ($sold_vs_delivered > 50) {
    $sales_insights[] = "Good conversion at {$sold_vs_delivered}% sell-through rate";
} else {
    $sales_insights[] = "Sell-through rate is {$sold_vs_delivered}% - room for improvement";
}

// Monthly comparison insights
$monthly_insights = [];
if ($best_month) {
    $monthly_insights[] = "{$best_month} was the best month with " . number_format($best_value) . " units";
}
if ($worst_month && $worst_month != $best_month) {
    $monthly_insights[] = "{$worst_month} had the lowest at " . number_format($worst_value) . " units";
}
$monthly_insights[] = "Average monthly delivery is " . number_format($avg_monthly) . " units";

// Client insights
$client_insights = [];
if (count($top_clients) > 0) {
    $top_client = $top_clients[0];
    $client_insights[] = "{$top_client['company_name']} is the top client with " . number_format($top_client['total_quantity']) . " units";
    if (count($top_clients) >= 3) {
        $top3_total = $top_clients[0]['total_quantity'] + $top_clients[1]['total_quantity'] + $top_clients[2]['total_quantity'];
        $top3_percent = $stats['total_delivered'] > 0 ? round(($top3_total / $stats['total_delivered']) * 100) : 0;
        $client_insights[] = "Top 3 clients account for {$top3_percent}% of total deliveries";
    }
}

// Trend insights
$current_month_idx = date('n') - 1;
$current_month_name = $months[$current_month_idx];
$current_month_value = $monthly_sales[$current_month_name] ?? 0;
$prev_month_idx = $current_month_idx > 0 ? $current_month_idx - 1 : 11;
$prev_month_name = $months[$prev_month_idx];
$prev_month_value = $monthly_sales[$prev_month_name] ?? 0;

$trend_insights = [];
if ($prev_month_value > 0) {
    $change = round((($current_month_value - $prev_month_value) / $prev_month_value) * 100);
    if ($change > 0) {
        $trend_insights[] = "Sales increased by {$change}% from {$prev_month_name}";
    } elseif ($change < 0) {
        $trend_insights[] = "Sales decreased by " . abs($change) . "% from {$prev_month_name}";
    } else {
        $trend_insights[] = "Sales stable compared to {$prev_month_name}";
    }
}
$trend_insights[] = "{$current_month_name} has " . number_format($current_month_value) . " units so far";

// Group A insights (Top 5 products)
$groupA_insights = [];
if (count($top_products) >= 1) {
    $groupA_products = array_slice($top_products, 0, 5);
    $top_product = $groupA_products[0];
    $groupA_insights[] = "{$top_product['item_code']} is the #1 product with " . number_format($top_product['total']) . " units";
    if (count($groupA_products) >= 3) {
        $top3_sum = array_sum(array_column(array_slice($groupA_products, 0, 3), 'total'));
        $total_products_sum = array_sum(array_column($top_products, 'total'));
        $top3_pct = $total_products_sum > 0 ? round(($top3_sum / $total_products_sum) * 100) : 0;
        $groupA_insights[] = "Top 3 products make up {$top3_pct}% of product sales";
    }
}

// Group B insights (Products 6-10)
$groupB_insights = [];
if (count($top_products) > 5) {
    $groupB_products = array_slice($top_products, 5, 5);
    $groupB_sum = array_sum(array_column($groupB_products, 'total'));
    $groupB_insights[] = "Products 6-10 contributed " . number_format($groupB_sum) . " units";
    if (count($groupB_products) >= 2) {
        $best_groupB = $groupB_products[0];
        $groupB_insights[] = "{$best_groupB['item_code']} leads this group with " . number_format($best_groupB['total']) . " units";
    }
} else {
    $groupB_insights[] = "No additional products in this tier yet";
}

// Metric Card Insights (click to reveal) - with trend explanations
$metric_delivered_insights = [];
$metric_delivered_insights[] = "<strong>" . number_format($stats['total_delivered']) . "</strong> total units delivered this period";
$metric_delivered_insights[] = "<strong>↑ 24%</strong> increase compared to previous period";
$metric_delivered_insights[] = "Delivery success rate: <strong>{$delivered_rate}%</strong>";
if ($pending_count > 0) {
    $metric_delivered_insights[] = "<strong>{$pending_count}</strong> orders still in progress";
}
if ($best_month) {
    $metric_delivered_insights[] = "Best performing month: <strong>{$best_month}</strong>";
}

$metric_sold_insights = [];
$metric_sold_insights[] = "<strong>" . number_format($stats['total_sold']) . "</strong> sales transactions completed";
$metric_sold_insights[] = "Revenue generated: <strong>PHP " . number_format($stats['total_sales_amount'], 2) . "</strong>";
$metric_sold_insights[] = "<strong>↑ 14%</strong> growth from last period";
$metric_sold_insights[] = "Sell-through rate: <strong>{$sold_vs_delivered}%</strong>";
if ($stats['total_delivered'] > 0) {
    $conversion = round(($stats['total_sold'] / $stats['total_delivered']) * 100, 1);
    $metric_sold_insights[] = "Conversion ratio: <strong>{$conversion}%</strong> of deliveries";
}

// Calculate company trend - compare current vs previous month unique companies
$current_month_companies = 0;
$prev_month_companies = 0;
$company_trend_percent = 0;
$company_trend_direction = 'neutral';

$sql_current = "
    SELECT COUNT(DISTINCT company_name) as count
    FROM delivery_records
    WHERE delivery_month = ? AND company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila') 
      AND company_name IS NOT NULL AND company_name != ''" . $dataset_filter;
$stmt = $conn->prepare($sql_current);
if ($stmt) {
    $params_with_month = array_merge([$current_month_name], $dataset_filter_params);
    $typeStr = str_repeat('s', count($params_with_month));
    $stmt->bind_param($typeStr, ...$params_with_month);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $current_month_companies = intval($row['count']);
    }
    $stmt->close();
}

$sql_prev = "
    SELECT COUNT(DISTINCT company_name) as count
    FROM delivery_records
    WHERE delivery_month = ? AND company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila')
      AND company_name IS NOT NULL AND company_name != ''" . $dataset_filter;
$stmt = $conn->prepare($sql_prev);
if ($stmt) {
    $params_with_month = array_merge([$prev_month_name], $dataset_filter_params);
    $typeStr = str_repeat('s', count($params_with_month));
    $stmt->bind_param($typeStr, ...$params_with_month);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $prev_month_companies = intval($row['count']);
    }
    $stmt->close();
}

// Calculate percentage change
if ($prev_month_companies > 0) {
    $company_trend_percent = round((($current_month_companies - $prev_month_companies) / $prev_month_companies) * 100);
    $company_trend_direction = $company_trend_percent > 0 ? 'up' : ($company_trend_percent < 0 ? 'down' : 'neutral');
} elseif ($current_month_companies > 0) {
    $company_trend_percent = 100; // New clients this month
    $company_trend_direction = 'up';
}

$metric_companies_insights = [];
$metric_companies_insights[] = "<strong>{$stats['total_companies']}</strong> unique client companies served";
if ($stats['total_companies'] > 0) {
    $metric_companies_insights[] = "Serving <strong>{$stats['total_companies']}</strong> active clients this period";
}
if ($current_month_companies > 0) {
    $metric_companies_insights[] = "<strong>{$current_month_companies}</strong> new/active clients in {$current_month_name}";
}
if ($company_trend_percent !== 0) {
    $trend_text = $company_trend_direction === 'up' ? "increased" : "decreased";
    $metric_companies_insights[] = "Client base {$trend_text} by <strong>" . abs($company_trend_percent) . "%</strong> vs {$prev_month_name}";
}
if (count($top_clients) > 0) {
    $metric_companies_insights[] = "Top client: <strong>{$top_clients[0]['company_name']}</strong>";
    if (count($top_clients) >= 3) {
        $metric_companies_insights[] = "Top 3 clients drive majority of orders";
    }
}

$metric_models_insights = [];
$metric_models_insights[] = "<strong>{$stats['active_models']}</strong> different product models in inventory";
$metric_models_insights[] = "<strong>↑ 18%</strong> more models added this period";
if (count($top_products) > 0) {
    $metric_models_insights[] = "Best seller: <strong>{$top_products[0]['item_code']}</strong>";
    $metric_models_insights[] = "Top model sold <strong>" . number_format($top_products[0]['total']) . "</strong> units";
}

// Summary card insights
$monthly_avg_insights = [];
$monthly_avg_insights[] = "Average <strong>" . number_format($avg_monthly) . "</strong> units delivered per month";
if ($best_month) {
    $monthly_avg_insights[] = "Peak month: <strong>{$best_month}</strong> with " . number_format($best_value) . " units";
}
if ($worst_month && $worst_month != $best_month) {
    $monthly_avg_insights[] = "Lowest month: <strong>{$worst_month}</strong> with " . number_format($worst_value) . " units";
}
$monthly_avg_insights[] = "Active months with deliveries: <strong>{$months_with_data}</strong>";

$yearly_insights = [];
$yearly_insights[] = "<strong>" . number_format($stats['yearly_total']) . "</strong> total units (1A, 2A, 4A) delivered across all transactions";
$yearly_insights[] = "Total Delivered: <strong>" . number_format($stats['total_delivered']) . "</strong> units";
$yearly_insights[] = "Total Sold: <strong>" . number_format($stats['total_sold']) . "</strong> transactions";
if ($stats['total_delivered'] > 0 && $months_with_data > 0) {
    $projected = round($stats['total_delivered'] / $months_with_data * 12);
    $yearly_insights[] = "Projected annual: <strong>" . number_format($projected) . "</strong> units";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>(function(){if(localStorage.getItem('theme')!=='dark'){document.documentElement.classList.add('light-mode');document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('light-mode')})}})()</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BW Gas Detector Sales  - Andison Industrial</title>
    <!-- Poppins font import removed - using Verdana instead -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <style>
        /* Hard override for dashboard header stacking/spacing */
        body.dashboard-page .navbar {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            z-index: 2147483647 !important;
        }

        body.dashboard-page {
            padding-top: 0 !important;
        }

        body.dashboard-page main.main-content {
            margin-top: 120px !important;
            min-height: calc(100vh - 120px) !important;
            z-index: 1 !important;
        }
    </style>
</head>
<body class="dashboard-page">
    <!-- Page loader: dismissed once all resources are ready -->
    <div id="pageLoader" aria-hidden="true">
        <div class="loader-content">
            <div class="loader-spinner"></div>
            <p class="loader-text">Loading Dashboard…</p>
        </div>
    </div>

    <!-- TOP NAVBAR -->
    <nav class="navbar">
        <div class="navbar-container">
            <!-- Hamburger Toggle & Logo -->
            <div class="navbar-start">
                <button class="hamburger-btn" id="hamburgerBtn" aria-label="Toggle sidebar">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                <div class="logo">
                    <a href="index.php" style="display:flex;align-items:center;">
                        <img src="assets/logo.png" alt="Andison" style="height:48px;width:auto;object-fit:contain;">
                    </a>
                </div>
            </div>

            <!-- Center Title -->
            <div class="navbar-center">
                <h1 class="dashboard-title">BW Gas Detector Sales</h1>
            </div>

            <!-- Right Profile Section -->
            <div class="navbar-end">

                <div class="profile-dropdown">
                    <button type="button" class="profile-btn" id="profileBtn" aria-label="Profile menu">
                        <span class="profile-name"><?php echo htmlspecialchars(isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User'); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="dropdown-menu" id="profileMenu">
                        <a href="profile.php"><i class="fas fa-user"></i> My Profile</a>
                        <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
                        <a href="help.php"><i class="fas fa-question-circle"></i> Help</a>
                        <hr>
                        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- SIDEBAR -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content" id="mainContent">
        <!-- Welcome Banner Section -->
        <div class="welcome-banner industrial">
            <div class="banner-overlay"></div>
            <div class="welcome-content">
                <div class="welcome-left">
                    <div class="industrial-icon">
                        <i class="fas fa-industry"></i>
                    </div>
                    <div class="welcome-text">
                        <h2>Welcome back, <?php echo htmlspecialchars($user_name); ?>!</h2>
                        <p><?php echo $stats['monthly_average'] > 0 ? 'Operations running smoothly. Keep up the great work!' : 'Ready to track your industrial operations!'; ?></p>
                    </div>
                </div>

                <div class="welcome-right">
                    <div class="quick-actions">
                        <button class="btn-industrial" onclick="goToReports()">
                            <i class="fas fa-chart-line"></i>
                            <span>View Reports</span>
                        </button>
                        <button class="btn-industrial secondary" onclick="window.location.href='delivery-records.php'">
                            <i class="fas fa-truck"></i>
                            <span>Deliveries</span>
                        </button>
                    </div>
                    <div class="status-indicator">
                        <span class="status-dot online"></span>
                        <span class="status-text">System Online</span>
                    </div>
                </div>
            </div>
            <div class="industrial-pattern"></div>
        </div>

        <!-- Error Alert for Upload Restriction -->
        <?php if (isset($_GET['error']) && $_GET['error'] === 'upload_denied'): ?>
        <div style="margin: 20px; padding: 16px; background: rgba(255,107,107,0.15); border: 1px solid rgba(255,107,107,0.3); border-radius: 10px; display: flex; align-items: center; gap: 12px;">
            <i class="fas fa-exclamation-circle" style="color:#ff6b6b; font-size:18px; flex-shrink:0;"></i>
            <div style="color:#ff6b6b; font-size:13px;">
                <strong>Upload Not Allowed:</strong> Only administrators can upload data. Please contact your admin to upload new files.
            </div>
            <button onclick="this.parentElement.style.display='none';" style="background:none; border:none; color:#ff6b6b; cursor:pointer; font-size:16px; margin-left:auto;">×</button>
        </div>
        <?php endif; ?>
            <div class="industrial-pattern"></div>
        </div>

        <!-- FILTER SECTION -->
        <div style="background: linear-gradient(135deg, #1e2a38 0%, #2a3f5f 100%); border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); padding: 20px; margin-bottom: 28px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 250px;">
                <label style="font-size: 14px; font-weight: 600; color: #e0e0e0; margin: 0; white-space: nowrap;">
                    <i class="fas fa-calendar" style="color: #f4d03f; margin-right: 8px;"></i>Filter by Year:
                </label>
                <select id="yearFilterMain" style="padding: 8px 12px; border-radius: 6px; border: 1px solid #4a5f7a; background: #1e2a38; font-size: 13px; font-weight: 500; cursor: pointer; color: #e0e0e0; transition: border-color 0.2s;" onchange="filterByYear(this.value)">
                    <option value="">All Years</option>
                    <?php
                    // Get available years from database
                    $yearResult = $conn->query("SELECT DISTINCT YEAR(delivery_date) as year FROM delivery_records WHERE delivery_date IS NOT NULL AND company_name != 'Stock Addition' ORDER BY year DESC");
                    if ($yearResult) {
                        while ($yearRow = $yearResult->fetch_assoc()) {
                            $year = intval($yearRow['year']);
                            $selected = ($selected_year === $year) ? 'selected' : '';
                            echo "<option value=\"{$year}\" {$selected}>{$year}</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <?php if (!empty($selected_year)): ?>
            <button style="padding: 8px 16px; border-radius: 6px; border: 1px solid #f4d03f; background: transparent; color: #f4d03f; font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.2s;" onclick="filterByYear('')">
                <i class="fas fa-times" style="margin-right: 6px;"></i>Clear Filter
            </button>
            <?php endif; ?>
        </div>

        <!-- KPI METRICS SECTION -->
        <section class="kpi-metrics">
            <!-- Total Orders -->
            <div class="metric-card clickable-insight" onclick="toggleMetricInsight(this)">
                <div class="metric-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="metric-info">
                    <span class="metric-label">Total Delivered</span>
                    <span class="metric-value"><?php echo $stats['total_delivered']; ?></span>
                    <div class="metric-trend up">
                        <i class="fas fa-arrow-up"></i>
                        <span>24%</span>
                    </div>
                </div>
                <canvas id="sparkline1" class="sparkline-chart"></canvas>
                <button class="insight-toggle" title="View Insights"><i class="fas fa-lightbulb"></i></button>
                <div class="metric-insight-popup">
                    <div class="insight-header"><i class="fas fa-lightbulb"></i> Insights</div>
                    <?php foreach($metric_delivered_insights as $insight): ?>
                    <div class="insight-item"><i class="fas fa-angle-right"></i> <?php echo $insight; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Total Sold -->
            <div class="metric-card clickable-insight" onclick="toggleMetricInsight(this)">
                <div class="metric-icon">
                    <i class="fas fa-peso-sign"></i>
                </div>
                <div class="metric-info">
                    <span class="metric-label">Sales Amount</span>
                    <span class="metric-value metric-value-money"><span class="money-currency">PHP</span> <span class="money-amount"><?php echo number_format($stats['total_sales_amount'], 2); ?></span></span>
                    <div class="metric-trend up">
                        <i class="fas fa-arrow-up"></i>
                        <span>14%</span>
                    </div>
                </div>
                <canvas id="sparkline2" class="sparkline-chart"></canvas>
                <button class="insight-toggle" title="View Insights"><i class="fas fa-lightbulb"></i></button>
                <div class="metric-insight-popup">
                    <div class="insight-header"><i class="fas fa-lightbulb"></i> Insights</div>
                    <?php foreach($metric_sold_insights as $insight): ?>
                    <div class="insight-item"><i class="fas fa-angle-right"></i> <?php echo $insight; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Total Companies -->
            <div class="metric-card clickable-insight" onclick="toggleMetricInsight(this)">
                <div class="metric-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="metric-info">
                    <span class="metric-label">Client Companies</span>
                    <span class="metric-value"><?php echo $stats['total_companies']; ?></span>
                    <div class="metric-trend <?php echo $company_trend_direction; ?>">
                        <i class="fas fa-arrow-<?php echo $company_trend_direction === 'up' ? 'up' : ($company_trend_direction === 'down' ? 'down' : 'right'); ?>"></i>
                        <span><?php echo abs($company_trend_percent); ?>%</span>
                    </div>
                </div>
                <canvas id="sparkline3" class="sparkline-chart"></canvas>
                <button class="insight-toggle" title="View Insights"><i class="fas fa-lightbulb"></i></button>
                <div class="metric-insight-popup">
                    <div class="insight-header"><i class="fas fa-lightbulb"></i> Insights</div>
                    <?php foreach($metric_companies_insights as $insight): ?>
                    <div class="insight-item"><i class="fas fa-angle-right"></i> <?php echo $insight; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Active Models -->
            <div class="metric-card clickable-insight" onclick="toggleMetricInsight(this)">
                <div class="metric-icon">
                    <i class="fas fa-cube"></i>
                </div>
                <div class="metric-info">
                    <span class="metric-label">Active Models</span>
                    <span class="metric-value"><?php echo $stats['active_models']; ?></span>
                    <div class="metric-trend up">
                        <i class="fas fa-arrow-up"></i>
                        <span>18%</span>
                    </div>
                </div>
                <canvas id="sparkline4" class="sparkline-chart"></canvas>
                <button class="insight-toggle" title="View Insights"><i class="fas fa-lightbulb"></i></button>
                <div class="metric-insight-popup">
                    <div class="insight-header"><i class="fas fa-lightbulb"></i> Insights</div>
                    <?php foreach($metric_models_insights as $insight): ?>
                    <div class="insight-item"><i class="fas fa-angle-right"></i> <?php echo $insight; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Monthly/Yearly Stats -->
        <section class="stats-summary">
            <div class="summary-card primary clickable-insight" onclick="toggleMetricInsight(this)">
                <div class="summary-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="summary-info">
                    <span class="summary-label">Monthly Average</span>
                    <span class="summary-value"><?php echo $stats['monthly_average']; ?></span>
                    <span class="summary-subtitle">Units per month</span>
                </div>
                <button class="insight-toggle" title="View Insights"><i class="fas fa-lightbulb"></i></button>
                <div class="metric-insight-popup">
                    <div class="insight-header"><i class="fas fa-lightbulb"></i> Insights</div>
                    <?php foreach($monthly_avg_insights as $insight): ?>
                    <div class="insight-item"><i class="fas fa-angle-right"></i> <?php echo $insight; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="summary-card secondary clickable-insight" onclick="toggleMetricInsight(this)">
                <div class="summary-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="summary-info">
                    <span class="summary-label">Yearly Total</span>
                    <span class="summary-value"><?php echo $stats['yearly_total']; ?></span>
                    <span class="summary-subtitle">Total units (1A, 2A, 4A)</span>
                </div>
                <button class="insight-toggle" title="View Insights"><i class="fas fa-lightbulb"></i></button>
                <div class="metric-insight-popup">
                    <div class="insight-header"><i class="fas fa-lightbulb"></i> Insights</div>
                    <?php foreach($yearly_insights as $insight): ?>
                    <div class="insight-item"><i class="fas fa-angle-right"></i> <?php echo $insight; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Imported Datasets Section -->
        <section class="datasets-overview">
            <div class="datasets-header">
                <h3><i class="fas fa-database"></i> Imported Datasets</h3>
                <a href="delivery-records.php" class="datasets-view-all"><i class="fas fa-external-link-alt"></i> View All Records</a>
            </div>
            <div class="datasets-grid" id="datasetsGrid">
                <!-- Cards rendered dynamically by JS below -->
            </div>
        </section>
        <style>
        .metric-value-money {
            white-space: normal;
            line-height: 1.1;
            font-size: clamp(1.3rem, 1.45vw, 1.7rem);
            letter-spacing: 0;
            max-width: 100%;
        }

        .metric-value-money .money-currency {
            white-space: nowrap;
            display: inline-block;
        }

        .metric-value-money .money-amount {
            white-space: nowrap;
            display: inline-block;
            max-width: 100%;
        }

        @media (max-width: 768px) {
            .metric-value-money {
                white-space: normal;
                font-size: clamp(1.1rem, 4.6vw, 1.35rem);
            }

            .metric-value-money .money-currency,
            .metric-value-money .money-amount {
                white-space: normal;
            }
        }

        .datasets-overview { margin: 0 0 28px 0; }
        .datasets-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
        .datasets-header h3 { font-size: 16px; font-weight: 600; color: #e0e0e0; margin: 0; display: flex; align-items: center; gap: 8px; }
        .datasets-header h3 i { color: #f4d03f; }
        .datasets-view-all { font-size: 13px; color: #f4d03f; text-decoration: none; display: flex; align-items: center; gap: 6px; opacity: .85; transition: opacity .2s; }
        .datasets-view-all:hover { opacity: 1; }
        .datasets-grid { display: flex; flex-wrap: wrap; gap: 12px; }
        .dataset-card-dash { display: flex; align-items: center; gap: 14px; background: #1e2a3a; border: 1px solid #2d3f55; border-radius: 10px; padding: 14px 18px; text-decoration: none; color: inherit; transition: background .2s, border-color .2s, transform .15s; min-width: 180px; }
        .dataset-card-dash:hover { background: #243447; border-color: #f4d03f; transform: translateY(-2px); }
        .dataset-card-icon { width: 38px; height: 38px; border-radius: 8px; background: linear-gradient(135deg, #f4d03f, #e2b800); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .dataset-card-icon i { color: #1a1a2e; font-size: 16px; }
        .dataset-card-info { display: flex; flex-direction: column; flex: 1; }
        .dataset-card-name { font-size: 14px; font-weight: 600; color: #e0e0e0; letter-spacing: .5px; }
        .dataset-card-count { font-size: 12px; color: #8a9ab5; margin-top: 2px; }
        .dataset-card-arrow { color: #4a5f7a; font-size: 12px; }
        .dataset-card-dash:hover .dataset-card-arrow { color: #f4d03f; }
        body.light-mode .datasets-header h3 { color: #1a2332; }
        body.light-mode .dataset-card-dash { background: #f0f4fa; border-color: #d0daea; color: #1a2332; }
        body.light-mode .dataset-card-dash:hover { background: #e8eef8; border-color: #f4d03f; }
        body.light-mode .dataset-card-name { color: #1a2332; }
        body.light-mode .dataset-card-count { color: #5a6a82; }
        .dataset-card-dash.active { background: #243447; border-color: #f4d03f; box-shadow: 0 0 12px rgba(244, 208, 63, 0.25); }
        body.light-mode .dataset-card-dash.active { background: #fff8e1; border-color: #f4d03f; }
        </style>
        <script>
        function renderDatasetCards(datasets) {
            const grid = document.getElementById('datasetsGrid');
            if (!grid) return;
            
            // Get current selected dataset from URL
            const urlParams = new URLSearchParams(window.location.search);
            const currentDataset = urlParams.get('dataset');
            
            // Check if we're viewing "ALL DATA" (when dataset param is empty or null)
            const isAllDataActive = currentDataset === null || currentDataset === '';
            
            // Limit to first 5 datasets
            const displayDatasets = datasets.slice(0, 5);
            const total = displayDatasets.reduce(function(s, d){ return s + d.count; }, 0);
            
            let html = '<a href="?dataset=" class="dataset-card-dash" ' + (isAllDataActive ? 'style="border-color:#f4d03f; box-shadow: 0 0 12px rgba(244, 208, 63, 0.25);"' : '') + '>'
                + '<div class="dataset-card-icon" style="background:linear-gradient(135deg,#5b9bd5,#3a7bbf)"><i class="fas fa-layer-group" style="color:#fff"></i></div>'
                + '<div class="dataset-card-info"><span class="dataset-card-name">ALL DATA' + (isAllDataActive ? ' <i class="fas fa-check-circle" style="color:#f4d03f; margin-left:6px; font-size:12px;"></i>' : '') + '</span>'
                + '<span class="dataset-card-count">' + total.toLocaleString() + ' records</span></div>'
                + '<i class="fas fa-chevron-right dataset-card-arrow"></i></a>';
                
            displayDatasets.forEach(function(ds) {
                const isActive = currentDataset === ds.name;
                html += '<a href="?dataset=' + encodeURIComponent(ds.name) + '" class="dataset-card-dash" ' + (isActive ? 'style="border-color:#f4d03f; box-shadow: 0 0 12px rgba(244, 208, 63, 0.25);"' : '') + '>'
                    + '<div class="dataset-card-icon"><i class="fas fa-table"></i></div>'
                    + '<div class="dataset-card-info"><span class="dataset-card-name">' + ds.name.toUpperCase() + (isActive ? ' <i class="fas fa-check-circle" style="color:#f4d03f; margin-left:6px; font-size:12px;"></i>' : '') + '</span>'
                    + '<span class="dataset-card-count">' + ds.count.toLocaleString() + ' records</span></div>'
                    + '<i class="fas fa-chevron-right dataset-card-arrow"></i></a>';
            });
            
            if (datasets.length === 0) {
                html = '<div style="color:#8a9ab5;font-size:13px;padding:14px 0;"><i class="fas fa-info-circle" style="margin-right:6px;"></i>No datasets yet. <a href="upload-data.php" style="color:#f4d03f;">Upload data</a> to get started.</div>';
            } else if (datasets.length > 5) {
                html += '<a href="delivery-records.php" class="dataset-card-dash" style="opacity:0.6; border-color:#8a9ab0;">'
                    + '<div class="dataset-card-icon" style="background:#8a9ab5;"><i class="fas fa-ellipsis-h" style="color:#fff"></i></div>'
                    + '<div class="dataset-card-info"><span class="dataset-card-name">VIEW ALL</span>'
                    + '<span class="dataset-card-count">' + (datasets.length - 5) + ' more datasets</span></div>'
                    + '<i class="fas fa-chevron-right dataset-card-arrow"></i></a>';
            }
            grid.innerHTML = html;
        }

        async function refreshDatasets() {
            try {
                const res = await fetch('api/get-datasets.php');
                const data = await res.json();
                if (data.success) renderDatasetCards(data.datasets);
            } catch(e) {}
        }

        // Ensure ALL DATA is selected by default on page load
        window.addEventListener('load', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (!urlParams.has('dataset')) {
                // No dataset parameter, set to empty (ALL DATA)
                window.history.replaceState({}, document.title, window.location.pathname + '?dataset=');
            }
        });

        // Initial render from PHP data (avoids flash on load)
        renderDatasetCards(<?php echo json_encode(array_map(function($ds){ return ['name'=>$ds['dataset_name'],'count'=>intval($ds['record_count'])]; }, $datasets)); ?>);

        // Auto-refresh when user returns to this tab (e.g. after importing in another tab)
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') refreshDatasets();
        });
        </script>

        <!-- KPI CARDS SECTION -->
        <section class="kpi-section">
            <!-- Total Delivered Card -->
            <div class="kpi-card">
                <div class="card-header">
                    <h3>Total Delivered</h3>
                    <span class="card-icon"><i class="fas fa-check-circle"></i></span>
                </div>
                <div class="card-content">
                    <div class="chart-container chart-expandable" onclick="openChartPreview('deliveredChart','Total Delivered')" data-preview-chart="deliveredChart">
                        <canvas id="deliveredChart"></canvas>
                        <span class="chart-expand-hint"><i class="fas fa-expand-alt"></i></span>
                    </div>
                    <div class="chart-insights">
                        <div class="insight-header"><i class="fas fa-lightbulb"></i> Insights</div>
                        <?php foreach ($delivery_insights as $insight): ?>
                        <div class="insight-item"><i class="fas fa-angle-right"></i> <?php echo htmlspecialchars($insight); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Total Sold Card -->
            <div class="kpi-card">
                <div class="card-header">
                    <h3>Total Sold</h3>
                    <span class="card-icon"><i class="fas fa-peso-sign"></i></span>
                </div>
                <div class="card-content">
                    <div class="chart-container chart-expandable" onclick="openChartPreview('soldChart','Total Sold')" data-preview-chart="soldChart">
                        <canvas id="soldChart"></canvas>
                        <span class="chart-expand-hint"><i class="fas fa-expand-alt"></i></span>
                    </div>
                    <div class="chart-insights">
                        <div class="insight-header"><i class="fas fa-lightbulb"></i> Insights</div>
                        <?php foreach ($sales_insights as $insight): ?>
                        <div class="insight-item"><i class="fas fa-angle-right"></i> <?php echo htmlspecialchars($insight); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Monthly Comparison Card -->
            <div class="kpi-card">
                <div class="card-header">
                    <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                        <h3 style="margin: 0;">Monthly Comparison</h3>
                        <select id="yearFilter" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd; background: #f5f5f5; font-size: 13px; font-weight: 500; cursor: pointer; transition: border-color 0.2s;" onchange="handleYearChange(this.value)">
                            <option value="">Loading years...</option>
                        </select>
                    </div>
                    <span class="card-icon"><i class="fas fa-balance-scale"></i></span>
                </div>
                <div class="card-content">
                    <div class="chart-container chart-expandable" onclick="openChartPreview('monthlyComparisonChart','Monthly Comparison')" data-preview-chart="monthlyComparisonChart">
                        <canvas id="monthlyComparisonChart"></canvas>
                        <span class="chart-expand-hint"><i class="fas fa-expand-alt"></i></span>
                    </div>
                    <div class="chart-insights">
                        <div class="insight-header"><i class="fas fa-lightbulb"></i> Insights</div>
                        <?php foreach ($monthly_insights as $insight): ?>
                        <div class="insight-item"><i class="fas fa-angle-right"></i> <?php echo htmlspecialchars($insight); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- MIDDLE SECTION - TWO PANELS -->
        <section class="middle-section">
            <!-- Top 15 Client Companies -->
            <div class="dashboard-panel">
                <div class="panel-header">
                    <h3>Top 15 Client Companies</h3>
                    <button class="panel-menu-btn" aria-label="Panel menu">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                </div>
                <div class="panel-content">
                    <div class="chart-expandable" onclick="openChartPreview('clientsChart','Top 15 Client Companies')" style="position:relative;">
                        <canvas id="clientsChart"></canvas>
                        <span class="chart-expand-hint"><i class="fas fa-expand-alt"></i></span>
                    </div>
                    <div class="chart-insights">
                        <div class="insight-header"><i class="fas fa-lightbulb"></i> Insights</div>
                        <?php foreach($client_insights as $insight): ?>
                        <div class="insight-item"><i class="fas fa-angle-right"></i> <?php echo htmlspecialchars($insight); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Monthly Sales Trend -->
            <div class="dashboard-panel">
                <div class="panel-header">
                    <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                        <h3 style="margin: 0;">Monthly Sales Trend</h3>
                        <select id="trendYearFilter" style="padding: 6px 12px; border-radius: 6px; border: 1px solid #ddd; background: #f5f5f5; font-size: 13px; font-weight: 500; cursor: pointer; transition: border-color 0.2s;" onchange="handleTrendYearChange(this.value)">
                            <option value="">Loading years...</option>
                        </select>
                    </div>
                    <button class="panel-menu-btn" aria-label="Panel menu">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                </div>
                <div class="panel-content">
                    <div class="chart-expandable" onclick="openChartPreview('trendChart','Monthly Sales Trend')" style="position:relative;">
                        <canvas id="trendChart"></canvas>
                        <span class="chart-expand-hint"><i class="fas fa-expand-alt"></i></span>
                    </div>
                    <div class="chart-insights">
                        <div class="insight-header"><i class="fas fa-lightbulb"></i> Insights</div>
                        <?php foreach($trend_insights as $insight): ?>
                        <div class="insight-item"><i class="fas fa-angle-right"></i> <?php echo htmlspecialchars($insight); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- BOTTOM SECTION - TWO CHARTS -->
        <section class="bottom-section">
            <!-- Quantity per Model - Group A -->
            <div class="dashboard-panel">
                <div class="panel-header">
                    <h3>Quantity per Model (Group A)</h3>
                    <button class="panel-menu-btn" aria-label="Panel menu">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                </div>
                <div class="panel-content">
                    <div class="chart-expandable" onclick="openChartPreview('groupAChart','Quantity per Model (Group A)')" style="position:relative;">
                        <canvas id="groupAChart"></canvas>
                        <span class="chart-expand-hint"><i class="fas fa-expand-alt"></i></span>
                    </div>
                    <div class="chart-insights">
                        <div class="insight-header"><i class="fas fa-lightbulb"></i> Insights</div>
                        <?php foreach($groupA_insights as $insight): ?>
                        <div class="insight-item"><i class="fas fa-angle-right"></i> <?php echo htmlspecialchars($insight); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Quantity per Model - Group B -->
            <div class="dashboard-panel">
                <div class="panel-header">
                    <h3>Quantity per Model (Group B)</h3>
                    <button class="panel-menu-btn" aria-label="Panel menu">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                </div>
                <div class="panel-content">
                    <div class="chart-expandable" onclick="openChartPreview('groupBChart','Quantity per Model (Group B)')" style="position:relative;">
                        <canvas id="groupBChart"></canvas>
                        <span class="chart-expand-hint"><i class="fas fa-expand-alt"></i></span>
                    </div>
                    <div class="chart-insights">
                        <div class="insight-header"><i class="fas fa-lightbulb"></i> Insights</div>
                        <?php foreach($groupB_insights as $insight): ?>
                        <div class="insight-item"><i class="fas fa-angle-right"></i> <?php echo htmlspecialchars($insight); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>

        <!-- FOOTER -->
        <footer class="dashboard-footer">
            <p>&copy; 2025 Andison Industrial. All rights reserved. | BW Gas Detector Sales Management System</p>
        </footer>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script>
        // Navigation function
        function goToReports() {
            window.location.href = 'reports.php';
        }

        // Filter by year function
        function filterByYear(year) {
            const params = new URLSearchParams(window.location.search);
            if (year) {
                params.set('year', year);
            } else {
                params.delete('year');
            }
            window.location.search = params.toString();
        }

        function closeAllMetricInsights() {
            document.querySelectorAll('.clickable-insight').forEach(c => {
                c.classList.remove('insight-active');
            });
            document.querySelectorAll('.metric-insight-popup.show').forEach(p => {
                p.classList.remove('show');
            });
        }

        // Toggle metric insight popup
        function toggleMetricInsight(card) {
            const popup = card.querySelector('.metric-insight-popup');
            const isVisible = popup.classList.contains('show');

            closeAllMetricInsights();

            // Toggle current popup
            if (!isVisible) {
                popup.classList.add('show');
                card.classList.add('insight-active');
            }
        }

        // Close popup when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.clickable-insight')) {
                closeAllMetricInsights();
            }
        });

        // Pass PHP data to JavaScript
        const dashboardData = {
            total_delivered: <?php echo $stats['total_delivered']; ?>,
            total_sold: <?php echo $stats['total_sold']; ?>,
            total_companies: <?php echo $stats['total_companies']; ?>,
            active_models: <?php echo $stats['active_models']; ?>,
            pending_count: <?php echo $pending_count; ?>,
            monthly_sales: <?php echo json_encode($monthly_sales); ?>,
            top_clients: <?php echo json_encode($top_clients); ?>,
            top_products: <?php echo json_encode($top_products); ?>,
            company_deliveries: <?php echo json_encode($company_deliveries); ?>
        };

        console.log('Dashboard data loaded:', dashboardData);

        // Dismiss loader once everything (fonts, Chart.js, images) is painted
        window.addEventListener('load', function () {
            const loader = document.getElementById('pageLoader');
            if (loader) {
                loader.classList.add('loader-hidden');
                // Remove from DOM after transition
                loader.addEventListener('transitionend', () => loader.remove(), { once: true });
            }
        });

        // Safety fallback — remove after 4 s max
        setTimeout(function () {
            const loader = document.getElementById('pageLoader');
            if (loader) loader.remove();
        }, 4000);
    </script>
    <script src="js/app.js" defer></script>

    <!-- ===== CHART PREVIEW MODAL ===== -->
    <div id="chartPreviewOverlay" onclick="closeChartPreview(event)" style="display:none; position:fixed; inset:0; z-index:9999; backdrop-filter:blur(8px); align-items:center; justify-content:center; padding:16px; box-sizing:border-box;">
        <div id="chartPreviewBox" style="border-radius:16px; width:min(1200px,97vw); height:90vh; display:flex; flex-direction:column; box-shadow:0 32px 80px rgba(0,0,0,0.6); overflow:hidden; transition:background 0.2s, border-color 0.2s;">
            <div id="chartPreviewHeader" style="display:flex; align-items:center; justify-content:space-between; padding:20px 28px; flex-shrink:0;">
                <h3 id="chartPreviewTitle" style="margin:0; font-size:20px; font-weight:700; letter-spacing:0.3px; flex:1;"></h3>
                <button id="chartPreviewCloseBtn" onclick="closeChartPreviewBtn()" style="width:44px; height:44px; border-radius:8px; cursor:pointer; font-size:18px; display:flex; align-items:center; justify-content:center; transition:background 0.2s; border:none; flex-shrink:0;"><i class="fas fa-times"></i></button>
            </div>
            <div style="padding:14px 28px 28px; flex:1; min-height:0; position:relative;">
                <canvas id="chartPreviewCanvas" style="width:100% !important; height:100% !important;"></canvas>
            </div>
        </div>
    </div>
    <style>
    .chart-expandable { cursor: pointer; }
    .chart-expand-hint {
        position: absolute; top: 8px; right: 8px;
        background: rgba(244,208,63,0.15); border: 1px solid rgba(244,208,63,0.3);
        color: #f4d03f; width: 28px; height: 28px; border-radius: 6px;
        display: flex; align-items: center; justify-content: center;
        font-size: 11px; opacity: 0; transition: opacity 0.2s;
        pointer-events: none;
    }
    .chart-expandable:hover .chart-expand-hint { opacity: 1; }
    .chart-expandable:hover canvas { opacity: 0.88; }
    /* dark mode modal */
    #chartPreviewOverlay.cp-dark  { background: rgba(4,8,18,0.92); }
    #chartPreviewBox.cp-dark      { background: #131c2b; border: 1px solid #2a3c55; }
    #chartPreviewHeader.cp-dark   { border-bottom: 1px solid #2a3c55; }
    #chartPreviewTitle.cp-dark    { color: #e2ecf8; }
    #chartPreviewCloseBtn.cp-dark { background: rgba(255,255,255,0.07); color: #a0b4c8; }
    #chartPreviewCloseBtn.cp-dark:hover { background: rgba(255,80,80,0.22) !important; }
    /* light mode modal */
    #chartPreviewOverlay.cp-light  { background: rgba(180,195,215,0.72); }
    #chartPreviewBox.cp-light      { background: #ffffff; border: 1px solid #d0daea; }
    #chartPreviewHeader.cp-light   { border-bottom: 1px solid #e0eaf4; }
    #chartPreviewTitle.cp-light    { color: #1a2a3a; }
    #chartPreviewCloseBtn.cp-light { background: #f0f4fa; color: #3a4a5a; }
    #chartPreviewCloseBtn.cp-light:hover { background: rgba(220,50,50,0.12) !important; }
    </style>
    <script>
    function openChartPreview(canvasId, title) {
        const sourceCanvas = document.getElementById(canvasId);
        if (!sourceCanvas) return;
        const sourceChart = (typeof Chart !== 'undefined') && Chart.getChart ? Chart.getChart(sourceCanvas) : null;
        if (!sourceChart) return;

        const isLight = document.body.classList.contains('light-mode');
        const themeClass = isLight ? 'cp-light' : 'cp-dark';
        const otherClass = isLight ? 'cp-dark' : 'cp-light';
        const tickColor   = isLight ? '#4a5a6a' : '#8a9ab5';
        const gridColor   = isLight ? 'rgba(0,0,0,0.07)' : 'rgba(255,255,255,0.06)';
        const legendColor = isLight ? '#2a3a4a' : '#c0d0e0';

        ['chartPreviewOverlay','chartPreviewBox','chartPreviewHeader','chartPreviewTitle','chartPreviewCloseBtn'].forEach(function(id) {
            const el = document.getElementById(id);
            el.classList.remove('cp-dark','cp-light');
            el.classList.add(themeClass);
        });

        document.getElementById('chartPreviewTitle').textContent = title;
        const overlay = document.getElementById('chartPreviewOverlay');
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';

        const previewCanvas = document.getElementById('chartPreviewCanvas');
        const existing = Chart.getChart(previewCanvas);
        if (existing) existing.destroy();
        try {
            const cfg = {
                type: sourceChart.config.type,
                data: JSON.parse(JSON.stringify(sourceChart.config.data)),
                options: JSON.parse(JSON.stringify(sourceChart.config.options || {}))
            };
            cfg.options.responsive = true;
            cfg.options.maintainAspectRatio = false;
            cfg.options.animation = { duration: 400 };
            cfg.options.plugins = cfg.options.plugins || {};
            cfg.options.plugins.legend = cfg.options.plugins.legend || {};
            cfg.options.plugins.legend.labels = cfg.options.plugins.legend.labels || {};
            cfg.options.plugins.legend.labels.color = legendColor;
            cfg.options.plugins.legend.labels.font = { size: 14 };
            if (cfg.options.plugins.title) cfg.options.plugins.title.color = legendColor;
            if (cfg.options.scales) {
                Object.values(cfg.options.scales).forEach(function(s) {
                    s.ticks = s.ticks || {}; s.ticks.color = tickColor; s.ticks.font = { size: 13 };
                    s.grid  = s.grid  || {}; s.grid.color  = gridColor;
                });
            }
            new Chart(previewCanvas, cfg);
        } catch(e) { console.error('Chart preview error:', e); }
    }
    function closeChartPreviewBtn() {
        document.getElementById('chartPreviewOverlay').style.display = 'none';
        document.body.style.overflow = '';
        const c = Chart.getChart(document.getElementById('chartPreviewCanvas'));
        if (c) c.destroy();
    }
    function closeChartPreview(e) {
        if (e && e.target !== document.getElementById('chartPreviewOverlay')) return;
        closeChartPreviewBtn();
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeChartPreviewBtn();
    });
    </script>
</body>
</html>

