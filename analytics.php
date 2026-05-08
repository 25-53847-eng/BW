<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php', true, 302);
    exit;
}

// Database connection
require_once 'db_config.php';
require_once 'dataset-indicator.php';

// Get selected dataset from URL or session
$selected_dataset = isset($_GET['dataset']) ? trim($_GET['dataset']) : (isset($_SESSION['active_dataset']) ? $_SESSION['active_dataset'] : 'all');

// Update session if dataset is passed via GET
if (isset($_GET['dataset'])) {
    $_SESSION['active_dataset'] = $selected_dataset;
}

// Build dataset filter
$dataset_filter = "";
if ($selected_dataset !== 'all' && $selected_dataset !== '') {
    $safe_dataset = $conn->real_escape_string($selected_dataset);
    $dataset_filter = " AND dataset_name = '$safe_dataset'";
}

// Get selected year from URL or session (default to current year)
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : (isset($_SESSION['active_year']) ? intval($_SESSION['active_year']) : intval(date('Y')));

// Update session if year is passed via GET
if (isset($_GET['year'])) {
    $_SESSION['active_year'] = $selected_year;
}

// Get available years for dropdown
$available_years = [intval(date('Y'))];
$r = $conn->query("SELECT DISTINCT delivery_year FROM delivery_records WHERE delivery_year > 0 AND delivery_year < 2100 ORDER BY delivery_year DESC LIMIT 50");
if ($r) {
    $available_years = [];
    while ($row = $r->fetch_assoc()) {
        $available_years[] = intval($row['delivery_year']);
    }
    if (empty($available_years)) {
        $available_years = [intval(date('Y'))];
    }
}

// Get available datasets for dropdown
$available_datasets = [];
$r = $conn->query("SELECT DISTINCT dataset_name FROM delivery_records WHERE dataset_name IS NOT NULL AND dataset_name != '' ORDER BY dataset_name ASC");
if ($r) {
    while ($row = $r->fetch_assoc()) {
        $available_datasets[] = $row['dataset_name'];
    }
}

// Build year filter
$year_filter = " AND delivery_year = $selected_year";
$combined_filter = $dataset_filter . $year_filter;

// Initialize variables
$totalAndison = 0;
$companyCount = 0;
$monthlyData = [];
$topCompanies = [];
$productsData = [];
$groupA = [];
$groupB = [];

// Function to identify grouping based on item name
function identifyGrouping($itemName) {
    $lowerName = strtolower($itemName);
    
    // Multi Gas indicators
    if (strpos($lowerName, 'multi') !== false || 
        strpos($lowerName, 'quattro') !== false || 
        strpos($lowerName, 'quad') !== false ||
        strpos($lowerName, 'o2/lel/h2s/co') !== false ||
        preg_match('/o2.*lel|lel.*o2/', $lowerName)) {
        return 'Group B - Multi Gas';
    }
    
    // Single Gas indicators
    if (strpos($lowerName, 'single') !== false ||
        preg_match('/\b(O2|LEL|H2S|CO)\b/i', $lowerName)) {
        return 'Group A - Single Gas';
    }
    
    // Default to Single Gas if unclear
    return 'Group A - Single Gas';
}

// Function to check if item is warranty replacement
function isWarrantyReplacementItem($itemName, $groupings) {
    $lowerGroupings = strtolower(trim($groupings ?? ''));
    return strpos($lowerGroupings, 'warranty replacement') !== false || strpos($lowerGroupings, '3a') !== false;
}

// Total delivered to Andison (all records in delivery_records)
$result = $conn->query("SELECT COUNT(*) as total_orders, COALESCE(SUM(quantity), 0) as total_units FROM delivery_records WHERE sold_to = 'Andison Industrial'$combined_filter");
if ($result && $row = $result->fetch_assoc()) {
    $totalAndison = intval($row['total_units']);
}

// Get unique companies count
$result = $conn->query("SELECT COUNT(DISTINCT company_name) as company_count FROM delivery_records WHERE company_name IS NOT NULL AND company_name != ''$combined_filter");
if ($result && $row = $result->fetch_assoc()) {
    $companyCount = intval($row['company_count']);
}

// Monthly data for charts
$result = $conn->query("
    SELECT delivery_month, 
           COUNT(*) as order_count,
           COALESCE(SUM(quantity), 0) as total_qty
    FROM delivery_records 
    WHERE sold_to = 'Andison Industrial' AND delivery_month IS NOT NULL AND delivery_month != ''$combined_filter
    GROUP BY delivery_month 
    ORDER BY CASE delivery_month
        WHEN 'January' THEN 1 WHEN 'February' THEN 2 WHEN 'March' THEN 3
        WHEN 'April' THEN 4 WHEN 'May' THEN 5 WHEN 'June' THEN 6
        WHEN 'July' THEN 7 WHEN 'August' THEN 8 WHEN 'September' THEN 9
        WHEN 'October' THEN 10 WHEN 'November' THEN 11 WHEN 'December' THEN 12
    END
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $monthlyData[] = $row;
    }
}

// Top 15 companies by quantity - use sold_to (actual customers), not company_name (internal classification)
// Exclude inventory items by filtering inventory_status
$result = $conn->query("
    SELECT sold_to as company_name, 
           COUNT(*) as order_count,
           COALESCE(SUM(quantity), 0) as total_qty
    FROM delivery_records 
    WHERE sold_to IS NOT NULL AND sold_to != '' AND TRIM(sold_to) != '' 
      AND (inventory_status IS NULL OR inventory_status = '')$combined_filter
    GROUP BY sold_to 
    ORDER BY total_qty DESC 
    LIMIT 15
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $topCompanies[] = $row;
    }
}

// Products/Items data - exclude warranty items, orders, and inventory items, and show only significant items
$result = $conn->query("
    SELECT item_name, item_code,
           COUNT(*) as order_count,
           COALESCE(SUM(quantity), 0) as total_qty,
           COUNT(DISTINCT company_name) as company_count
    FROM delivery_records 
    WHERE sold_to = 'Andison Industrial'
        AND item_name IS NOT NULL
        AND company_name NOT IN ('Orders', 'Inquiry', 'Stock Addition')
        AND (sold_to IS NOT NULL AND sold_to != '')
        AND NOT (LOWER(TRIM(COALESCE(sold_to, ''))) IN ('stock in manila') OR LOWER(TRIM(COALESCE(sold_to, ''))) LIKE '%stock in manila%')
        AND NOT (LOWER(TRIM(COALESCE(groupings, ''))) LIKE '%warranty replacement%' OR LOWER(TRIM(COALESCE(groupings, ''))) LIKE '%3a%')
        $combined_filter
    GROUP BY item_name, item_code
    HAVING COALESCE(SUM(quantity), 0) > 5
    ORDER BY total_qty DESC
    LIMIT 30
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $productsData[] = $row;
    }
}

// Separate into Group A (Single Gas) and Group B (Multi Gas) models
foreach ($productsData as $product) {
    $groupLabel = identifyGrouping($product['item_name'] ?? $product['item_code'] ?? '');
    if ($groupLabel === 'Group B - Multi Gas') {
        $groupB[] = $product;
    } else {
        $groupA[] = $product;
    }
}

// Prepare data for JavaScript - ensure all 12 months are shown
$monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$monthlyDataMap = [];

// Create map of existing data
foreach ($monthlyData as $row) {
    $monthlyDataMap[$row['delivery_month']] = intval($row['total_qty']);
}

// Build complete 12-month arrays
$monthlyLabels = [];
$monthlyDelivered = [];
foreach ($monthNames as $monthName) {
    $monthlyLabels[] = substr($monthName, 0, 3);
    $monthlyDelivered[] = isset($monthlyDataMap[$monthName]) ? $monthlyDataMap[$monthName] : 0;
}

$topCompaniesJs = [];
foreach ($topCompanies as $company) {
    $topCompaniesJs[] = [
        'name' => $company['company_name'],
        'value' => intval($company['total_qty'])
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>(function(){if(localStorage.getItem('theme')!=='dark'){document.documentElement.classList.add('light-mode');document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('light-mode')})}})()</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - BW Gas Detector</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="preload" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet"></noscript>
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        .page-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 22px;
            margin-bottom: 30px;
        }
        
        .gauge-card {
            background: linear-gradient(135deg, #4a7ba7 0%, #2e5c8a 100%);
            padding: 28px;
            border-radius: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }
        
        .gauge-card h3 {
            font-size: 16px;
            color: #f4f8ff;
            font-weight: 600;
            margin-bottom: 18px;
            text-transform: capitalize;
            text-shadow: 0 1px 2px rgba(7, 18, 35, 0.45);
        }
        
        .gauge-chart {
            position: relative;
            width: 160px;
            height: 160px;
            margin-bottom: 12px;
        }
        
        .gauge-value {
            font-size: 52px;
            font-weight: 700;
            color: #ffd84d;
            text-shadow: 0 2px 6px rgba(7, 18, 35, 0.5);
        }

        /* Keep card text readable even when global light-mode heading rules are active */
        html.light-mode .gauge-card h3,
        body.light-mode .gauge-card h3 {
            color: #f4f8ff !important;
            text-shadow: 0 1px 2px rgba(7, 18, 35, 0.45);
        }

        html.light-mode .gauge-value,
        body.light-mode .gauge-value {
            color: #ffd84d !important;
            text-shadow: 0 2px 6px rgba(7, 18, 35, 0.5);
        }
        
        .chart-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-container-full {
            background: #13172c;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            min-height: 500px;
        }
        
        .chart-title {
            font-size: 16px;
            font-weight: 600;
            color: #e0e0e0;
            margin-bottom: 20px;
        }
        
        .table-container {
            background: #13172c;
            padding: 25px;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table thead {
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }
        
        table th {
            padding: 15px;
            text-align: left;
            font-size: 12px;
            color: #a0a0a0;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: #e0e0e0;
            font-size: 14px;
        }
        
        table tbody tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-delivered {
            background: rgba(52, 211, 153, 0.2);
            color: #34d399;
        }
        
        .page-title {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title h1 {
            margin: 0;
            font-size: inherit;
            font-weight: inherit;
            line-height: 1.2;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 20px;
            margin-top: 30px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f4d03f;
        }
        
        .horizontal-bar-container {
            background: linear-gradient(135deg, #4a7ba7 0%, #2e5c8a 100%);
            padding: 40px;
            border-radius: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
        }
        
        .horizontal-bar-container h2 {
            text-align: center;
            color: #ffffff;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 30px;
        }
        
        .bar-row {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            gap: 15px;
        }
        
        .company-name {
            min-width: 280px;
            color: #fff;
            font-weight: 600;
            font-size: 13px;
            text-align: right;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .bar-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .bar {
            height: 26px;
            background: linear-gradient(90deg, #ffeb3b 0%, #f4d03f 100%);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 8px;
            font-weight: 700;
            color: #000;
            font-size: 13px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            min-width: 40px;
        }
        
        @media (max-width: 768px) {
            .chart-row {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 12px;
            }
            
            table th, table td {
                padding: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .page-section {
                gap: 15px;
            }
            
            .gauge-card {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
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
                        <a href="logout.php" id="logoutBtn"><i class="fas fa-sign-out-alt"></i> Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- SIDEBAR -->
    <?php require __DIR__ . '/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="content-wrapper">
            <div class="page-title" style="justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-chart-bar"></i>
                    <h1>Analytics Dashboard<?php echo renderDatasetIndicator($selected_dataset); ?></h1>
                </div>
                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <!-- Dataset Filter Dropdown -->
                    <?php if (!empty($available_datasets)): ?>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:13px;color:#ffffff;white-space:nowrap;font-weight:700;text-shadow:0 1px 3px rgba(0,0,0,0.4);background:linear-gradient(135deg, #4a7ba7 0%, #2e5c8a 100%);padding:6px 12px;border-radius:6px;display:inline-block;box-shadow:0 2px 6px rgba(0,0,0,0.2);">Dataset:</span>
                        <select id="analyticsDatasetFilter" style="background:linear-gradient(135deg, #4a7ba7 0%, #2e5c8a 100%);color:#f4f8ff;border:1.5px solid rgba(255,255,255,0.15);border-radius:8px;padding:8px 12px;font-size:12px;cursor:pointer;outline:none;min-width:120px;box-shadow:0 4px 12px rgba(0,0,0,0.3);transition:all 0.2s ease;font-weight:500;">
                            <option value="" <?php echo $selected_dataset === 'all' || $selected_dataset === '' ? 'selected' : ''; ?> style="background:#2e5c8a;color:#f4f8ff;">All Datasets</option>
                            <?php foreach ($available_datasets as $ds): ?>
                            <option value="<?php echo htmlspecialchars($ds); ?>" <?php echo $selected_dataset === $ds ? 'selected' : ''; ?> style="background:#2e5c8a;color:#f4f8ff;"><?php echo htmlspecialchars($ds); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Year Filter Dropdown -->
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span style="font-size:13px;color:#ffffff;white-space:nowrap;font-weight:700;text-shadow:0 1px 3px rgba(0,0,0,0.4);background:linear-gradient(135deg, #4a7ba7 0%, #2e5c8a 100%);padding:6px 12px;border-radius:6px;display:inline-block;box-shadow:0 2px 6px rgba(0,0,0,0.2);">Year:</span>
                        <select id="analyticsYearFilter" style="background:linear-gradient(135deg, #4a7ba7 0%, #2e5c8a 100%);color:#f4f8ff;border:1.5px solid rgba(255,255,255,0.15);border-radius:8px;padding:8px 12px;font-size:12px;cursor:pointer;outline:none;min-width:80px;box-shadow:0 4px 12px rgba(0,0,0,0.3);transition:all 0.2s ease;font-weight:500;">
                            <?php foreach ($available_years as $y): ?>
                            <option value="<?php echo $y; ?>" <?php echo $selected_year === $y ? 'selected' : ''; ?> style="background:#2e5c8a;color:#f4f8ff;"><?php echo $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- KEY METRICS SECTION -->
            <h2 class="section-title">📊 Key Metrics Overview</h2>
            <div class="page-section">
                <div class="gauge-card">
                    <h3>Total Quantity of Gas Detectors Delivered to Andison</h3>
                    <div class="gauge-chart chart-expandable" onclick="openChartPreview('gaugeChartAndison','Total Qty Delivered to Andison')" style="position:relative;">
                        <canvas id="gaugeChartAndison"></canvas>
                        <span class="chart-expand-hint"><i class="fas fa-expand-alt"></i></span>
                    </div>
                    <div class="gauge-value" id="gaugeValueAndison"><?php echo number_format($totalAndison); ?></div>
                </div>
                <div class="gauge-card">
                    <h3>Total Quantity of Gas Detectors Sold to Companies</h3>
                    <div class="gauge-chart chart-expandable" onclick="openChartPreview('gaugeChartCompanies','Total Qty Sold to Companies')" style="position:relative;">
                        <canvas id="gaugeChartCompanies"></canvas>
                        <span class="chart-expand-hint"><i class="fas fa-expand-alt"></i></span>
                    </div>
                    <div class="gauge-value" id="gaugeValueCompanies"><?php echo number_format($companyCount); ?></div>
                </div>
            </div>

            <!-- TOP 15 CLIENT COMPANIES -->
            <h2 class="section-title">🏆 Quantity of Gas Detectors Sold To Top 15 Client Companies</h2>
            <div class="horizontal-bar-container">
                <div id="topCompaniesContainer"></div>
            </div>

            <!-- MONTHLY DELIVERY & SALES SECTION -->
            <h2 class="section-title">📅 Monthly Delivery & Sales Trends</h2>
            <div class="chart-row">
                <div class="chart-container-full chart-expandable" onclick="openChartPreview('monthlyTrendChart','Monthly Delivery & Sales Trends')" style="position:relative;">
                    <div class="chart-title">Monthly Delivery to Andison & Sales to Companies</div>
                    <canvas id="monthlyTrendChart" style="max-height: 300px;"></canvas>
                    <span class="chart-expand-hint"><i class="fas fa-expand-alt"></i></span>
                </div>
                <div class="chart-container-full chart-expandable" onclick="openChartPreview('monthlyBarChart','Monthly Comparison')" style="position:relative;">
                    <div class="chart-title">Monthly Comparison</div>
                    <canvas id="monthlyBarChart" style="max-height: 300px;"></canvas>
                    <span class="chart-expand-hint"><i class="fas fa-expand-alt"></i></span>
                </div>
            </div>

            <!-- MONTHLY DETAIL TABLE -->
            <h2 class="section-title">Monthly Breakdown</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Orders</th>
                            <th>Units Delivered</th>
                            <th>Est. Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $prevQty = 0;
                        foreach ($monthlyData as $row): 
                            $qty = intval($row['total_qty']);
                            $revenue = number_format(($qty * 540) / 1000, 1);
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['delivery_month']); ?></strong></td>
                            <td><?php echo number_format($row['order_count']); ?></td>
                            <td><?php echo number_format($qty); ?></td>
                            <td>₱<?php echo $revenue; ?>K</td>
                        </tr>
                        <?php 
                            $prevQty = $qty;
                        endforeach; ?>
                        <?php if (empty($monthlyData)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #888;">No monthly data available</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- MODEL GROUP A ANALYTICS -->
            <h2 class="section-title">🎯 Group A Model Analytics</h2>
            <div class="chart-row">
                <div class="chart-container-full chart-expandable" onclick="openChartPreview('groupABarChart','Group A Model Performance')" style="position:relative;">
                    <div class="chart-title">Group A Model Performance</div>
                    <canvas id="groupABarChart" style="max-height: 450px;"></canvas>
                    <span class="chart-expand-hint"><i class="fas fa-expand-alt"></i></span>
                </div>
            </div>

            <!-- GROUP A DETAIL TABLE -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Model</th>
                            <th>Orders</th>
                            <th>Units Delivered</th>
                            <th>Company Distribution</th>
                            <th>Est. Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupA as $product): 
                            $qty = intval($product['total_qty']);
                            $revenue = number_format(($qty * 540) / 1000, 1);
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($product['item_name'] ?: $product['item_code']); ?></strong></td>
                            <td><?php echo number_format($product['order_count']); ?></td>
                            <td><?php echo number_format($qty); ?></td>
                            <td><?php echo $product['company_count']; ?> companies</td>
                            <td>₱<?php echo $revenue; ?>K</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($groupA)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #888;">No Group A (MCX3) data available</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- MODEL GROUP B ANALYTICS -->
            <h2 class="section-title">🚀 Group B Model Analytics</h2>
            <div class="chart-row">
                <div class="chart-container-full chart-expandable" onclick="openChartPreview('groupBBarChart','Group B Model Performance')" style="position:relative;">
                    <div class="chart-title">Group B Model Performance</div>
                    <canvas id="groupBBarChart" style="max-height: 450px;"></canvas>
                    <span class="chart-expand-hint"><i class="fas fa-expand-alt"></i></span>
                </div>
            </div>

            <!-- GROUP B DETAIL TABLE -->
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Model</th>
                            <th>Orders</th>
                            <th>Units Delivered</th>
                            <th>Company Distribution</th>
                            <th>Est. Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($groupB as $product): 
                            $qty = intval($product['total_qty']);
                            $revenue = number_format(($qty * 540) / 1000, 1);
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($product['item_name'] ?: $product['item_code']); ?></strong></td>
                            <td><?php echo number_format($product['order_count']); ?></td>
                            <td><?php echo number_format($qty); ?></td>
                            <td><?php echo $product['company_count']; ?> companies</td>
                            <td>₱<?php echo $revenue; ?>K</td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($groupB)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #888;">No Group B (MCXL) data available</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="js/app.js" defer></script>
    <script>
        console.log('=== ANALYTICS PAGE SCRIPT LOADED ===');
        
        // Data from PHP
        const analyticsData = {
            totalAndison: <?php echo $totalAndison; ?>,
            companyCount: <?php echo $companyCount; ?>,
            monthlyLabels: <?php echo json_encode($monthlyLabels); ?>,
            monthlyDelivered: <?php echo json_encode($monthlyDelivered); ?>,
            topCompanies: <?php echo json_encode($topCompaniesJs); ?>,
            groupA: <?php echo json_encode(array_map(function($p) { 
                return ['name' => $p['item_name'] ?: $p['item_code'], 'qty' => intval($p['total_qty'])]; 
            }, $groupA)); ?>,
            groupB: <?php echo json_encode(array_map(function($p) { 
                return ['name' => $p['item_name'] ?: $p['item_code'], 'qty' => intval($p['total_qty'])]; 
            }, $groupB)); ?>
        };
        
        // Gauge Charts for   Andison and Companies
        let gaugeChartAndison;
        let gaugeChartCompanies;
        let monthlyTrendChartInstance;
        let monthlyBarChartInstance;

        function createGaugeChart(canvasId, value, max) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            return new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Value', 'Remaining'],
                    datasets: [{
                        data: [value, Math.max(0, max - value)],
                        backgroundColor: ['#f4d03f', 'rgba(255, 255, 255, 0.1)'],
                        borderColor: 'transparent',
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    circumference: 180,
                    rotation: 270,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }

        // Initialize all charts and filters when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOMContentLoaded event fired');
        
            // Handle dataset filter change
            const datasetFilter = document.getElementById('analyticsDatasetFilter');
            if (datasetFilter) {
                datasetFilter.addEventListener('change', function() {
                    const selectedDataset = this.value;
                    const year = new URLSearchParams(window.location.search).get('year') || new Date().getFullYear();
                    
                    // Redirect to same page with new dataset parameter
                    const newUrl = window.location.pathname + '?year=' + encodeURIComponent(year) + (selectedDataset ? '&dataset=' + encodeURIComponent(selectedDataset) : '');
                    window.location.href = newUrl;
                });
            }
        
            // Handle year filter change
            const yearFilter = document.getElementById('analyticsYearFilter');
            if (yearFilter) {
                yearFilter.addEventListener('change', function() {
                    const selectedYear = this.value;
                    const dataset = new URLSearchParams(window.location.search).get('dataset') || '';
                    
                    // Redirect to same page with new year parameter
                    const newUrl = window.location.pathname + '?year=' + encodeURIComponent(selectedYear) + (dataset ? '&dataset=' + encodeURIComponent(dataset) : '');
                    window.location.href = newUrl;
                });
            }
        
            // Initialize charts with real data
            const maxAndison = Math.max(analyticsData.totalAndison + 100, 800);
            const maxCompanies = Math.max(analyticsData.companyCount + 50, 100);
            gaugeChartAndison = createGaugeChart('gaugeChartAndison', analyticsData.totalAndison, maxAndison);
            gaugeChartCompanies = createGaugeChart('gaugeChartCompanies', analyticsData.companyCount, maxCompanies);

            // Create Monthly Trend Chart Instance with real data
            const monthlyTrendCtx = document.getElementById('monthlyTrendChart').getContext('2d');
            monthlyTrendChartInstance = new Chart(monthlyTrendCtx, {
            type: 'line',
            data: {
                labels: analyticsData.monthlyLabels.length > 0 ? analyticsData.monthlyLabels : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    {
                        label: 'Units Delivered',
                        data: analyticsData.monthlyDelivered.length > 0 ? analyticsData.monthlyDelivered : [0],
                        borderColor: '#f4d03f',
                        backgroundColor: 'rgba(244, 208, 63, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: '#f4d03f',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#e0e0e0', padding: 15, font: { size: 13, weight: 500 } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#a0a0a0' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#a0a0a0' }
                    }
                }
            }
        });

        // Create Monthly Bar Chart Instance with real data
        const monthlyBarCtx = document.getElementById('monthlyBarChart').getContext('2d');
        monthlyBarChartInstance = new Chart(monthlyBarCtx, {
            type: 'bar',
            data: {
                labels: analyticsData.monthlyLabels.length > 0 ? analyticsData.monthlyLabels : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [
                    {
                        label: 'Units Delivered',
                        data: analyticsData.monthlyDelivered.length > 0 ? analyticsData.monthlyDelivered : [0],
                        backgroundColor: '#f4d03f',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: '#e0e0e0', padding: 15, font: { size: 13, weight: 500 } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(255, 255, 255, 0.05)' },
                        ticks: { color: '#a0a0a0' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#a0a0a0' }
                    }
                }
            }
        });

        // Group A Doughnut Chart with real data
        const groupALabels = analyticsData.groupA.map(p => p.name);
        const groupAValues = analyticsData.groupA.map(p => p.qty);
        // Group A Bar Chart with real data
        const groupABarCtx = document.getElementById('groupABarChart').getContext('2d');
        new Chart(groupABarCtx, {
            type: 'bar',
            data: {
                labels: groupALabels.length > 0 ? groupALabels : ['No Data'],
                datasets: [
                    {
                        label: 'Units Delivered',
                        data: groupAValues.length > 0 ? groupAValues : [0],
                        backgroundColor: '#2f5fa7',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { padding: 15, font: { size: 13, weight: 500 } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {},
                        ticks: {}
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxRotation: 90,
                            minRotation: 45,
                            autoSkip: false
                        }
                    }
                }
            }
        });

        // Group B Doughnut Chart with real data
        const groupBLabels = analyticsData.groupB.map(p => p.name);
        const groupBValues = analyticsData.groupB.map(p => p.qty);
        // Group B Bar Chart with real data
        const groupBBarCtx = document.getElementById('groupBBarChart').getContext('2d');
        new Chart(groupBBarCtx, {
            type: 'bar',
            data: {
                labels: groupBLabels.length > 0 ? groupBLabels : ['No Data'],
                datasets: [
                    {
                        label: 'Units Delivered',
                        data: groupBValues.length > 0 ? groupBValues : [0],
                        backgroundColor: '#ff6b6b',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { padding: 15, font: { size: 13, weight: 500 } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {},
                        ticks: {}
                    },
                    x: {
                        grid: { display: false },
                        ticks: {
                            maxRotation: 90,
                            minRotation: 45,
                            autoSkip: false
                        }
                    }
                }
            }
        });
        const gaugeData = {
            andison: 696,
            companies: 311
        };

        // Gauge Chart - Andison
        const gaugeAndisonCtx = document.getElementById('gaugeChartAndison').getContext('2d');
        new Chart(gaugeAndisonCtx, {
            type: 'doughnut',
            data: {
                labels: ['Delivered', 'Remaining'],
                datasets: [{
                    data: [gaugeData.andison, 300 - (gaugeData.andison % 300)],
                    backgroundColor: ['#f4d03f', 'rgba(255, 255, 255, 0.1)'],
                    borderColor: 'transparent',
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                circumference: 180,
                rotation: 270,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Gauge Chart - Companies
        const gaugeCompaniesCtx = document.getElementById('gaugeChartCompanies').getContext('2d');
        new Chart(gaugeCompaniesCtx, {
            type: 'doughnut',
            data: {
                labels: ['Sold', 'Remaining'],
                datasets: [{
                    data: [gaugeData.companies, 400 - (gaugeData.companies % 400)],
                    backgroundColor: ['#f4d03f', 'rgba(255, 255, 255, 0.1)'],
                    borderColor: 'transparent',
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                circumference: 180,
                rotation: 270,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Top 15 Companies Horizontal Bar Chart with real data
        const topCompaniesData = analyticsData.topCompanies.length > 0 ? analyticsData.topCompanies : [
            { name: 'No company data', value: 0 }
        ];

        const container = document.getElementById('topCompaniesContainer');
        console.log('Container found:', container ? 'YES' : 'NO');
        
        if (container) {
            const maxValue = Math.max(...topCompaniesData.map(d => d.value));
            console.log('Max value:', maxValue);
            console.log('Companies count:', topCompaniesData.length);

            topCompaniesData.forEach(company => {
                const barWidth = (company.value / maxValue) * 100;
                const barHTML = `
                    <div class="bar-row">
                        <div class="company-name">${company.name}</div>
                        <div class="bar-wrapper">
                            <div class="bar" style="width: ${barWidth}%; min-width: 40px;">
                                ${company.value}
                            </div>
                        </div>
                    </div>
                `;
                container.innerHTML += barHTML;
            });
            console.log('Companies populated successfully');
        }
        
        }); // Close DOMContentLoaded event listener
    </script>

    <!-- ===== CHART PREVIEW MODAL ===== -->
    <div id="chartPreviewOverlay" onclick="closeChartPreview(event)" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:10000;backdrop-filter:blur(8px);align-items:center;justify-content:center;padding:16px;box-sizing:border-box;">
        <div id="chartPreviewBox" style="border-radius:16px;width:min(1200px,97vw);height:90vh;display:flex;flex-direction:column;box-shadow:0 32px 80px rgba(0,0,0,0.6);overflow:hidden;">
            <div id="chartPreviewHeader" style="display:flex;align-items:center;justify-content:space-between;padding:18px 26px;flex-shrink:0;">
                <h3 id="chartPreviewTitle" style="margin:0;font-size:18px;font-weight:700;"></h3>
                <button id="chartPreviewCloseBtn" onclick="closeChartPreviewBtn()" style="width:34px;height:34px;border-radius:9px;cursor:pointer;font-size:15px;display:flex;align-items:center;justify-content:center;border:none;transition:background 0.2s;"><i class="fas fa-times"></i></button>
            </div>
            <div style="padding:10px 26px 26px;flex:1;min-height:0;position:relative;">
                <canvas id="chartPreviewCanvas" style="width:100% !important;height:100% !important;"></canvas>
            </div>
        </div>
    </div>
    <style>
    .chart-expandable{cursor:pointer;}
    .chart-expand-hint{position:absolute;top:8px;right:8px;background:rgba(244,208,63,0.15);border:1px solid rgba(244,208,63,0.3);color:#f4d03f;width:28px;height:28px;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;opacity:0;transition:opacity 0.2s;pointer-events:none;}
    .chart-expandable:hover .chart-expand-hint{opacity:1;}
    .chart-expandable:hover canvas{opacity:0.88;}
    #chartPreviewOverlay.cp-dark{background:rgba(4,8,18,0.92);}
    #chartPreviewBox.cp-dark{background:#131c2b;border:1px solid #2a3c55;}
    #chartPreviewHeader.cp-dark{border-bottom:1px solid #2a3c55;}
    #chartPreviewTitle.cp-dark{color:#e2ecf8;}
    #chartPreviewCloseBtn.cp-dark{background:rgba(255,255,255,0.07);color:#a0b4c8;}
    #chartPreviewOverlay.cp-light{background:rgba(180,195,215,0.72);}
    #chartPreviewBox.cp-light{background:#ffffff;border:1px solid #d0daea;}
    #chartPreviewHeader.cp-light{border-bottom:1px solid #e0eaf4;}
    #chartPreviewTitle.cp-light{color:#1a2a3a;}
    #chartPreviewCloseBtn.cp-light{background:#f0f4fa;color:#3a4a5a;}
    </style>
    <script>
    function openChartPreview(canvasId,title){
        const src=document.getElementById(canvasId);if(!src)return;
        const sc=(typeof Chart!=='undefined')&&Chart.getChart?Chart.getChart(src):null;if(!sc)return;
        const isLight=document.body.classList.contains('light-mode');
        const tc=isLight?'cp-light':'cp-dark';
        const tickC=isLight?'#4a5a6a':'#8a9ab5',gridC=isLight?'rgba(0,0,0,0.07)':'rgba(255,255,255,0.06)',legC=isLight?'#2a3a4a':'#c0d0e0';
        ['chartPreviewOverlay','chartPreviewBox','chartPreviewHeader','chartPreviewTitle','chartPreviewCloseBtn'].forEach(function(id){const el=document.getElementById(id);el.classList.remove('cp-dark','cp-light');el.classList.add(tc);});
        document.getElementById('chartPreviewTitle').textContent=title;
        const ov=document.getElementById('chartPreviewOverlay');ov.style.display='flex';document.body.style.overflow='hidden';
        const pc=document.getElementById('chartPreviewCanvas');const ex=Chart.getChart(pc);if(ex)ex.destroy();
        try{
            const cfg={type:sc.config.type,data:JSON.parse(JSON.stringify(sc.config.data)),options:JSON.parse(JSON.stringify(sc.config.options||{}))};
            cfg.options.responsive=true;cfg.options.maintainAspectRatio=false;cfg.options.animation={duration:400};
            cfg.options.plugins=cfg.options.plugins||{};
            cfg.options.plugins.legend=cfg.options.plugins.legend||{};
            cfg.options.plugins.legend.labels=cfg.options.plugins.legend.labels||{};
            cfg.options.plugins.legend.labels.color=legC;cfg.options.plugins.legend.labels.font={size:14};
            if(cfg.options.plugins.title)cfg.options.plugins.title.color=legC;
            if(cfg.options.scales){Object.values(cfg.options.scales).forEach(function(s){s.ticks=s.ticks||{};s.ticks.color=tickC;s.ticks.font={size:13};s.grid=s.grid||{};s.grid.color=gridC;});}
            new Chart(pc,cfg);
        }catch(e){console.error('Chart preview error:',e);}
    }
    function closeChartPreviewBtn(){document.getElementById('chartPreviewOverlay').style.display='none';document.body.style.overflow='';const c=Chart.getChart(document.getElementById('chartPreviewCanvas'));if(c)c.destroy();}
    function closeChartPreview(e){if(e&&e.target!==document.getElementById('chartPreviewOverlay'))return;closeChartPreviewBtn();}
    document.addEventListener('keydown',function(e){if(e.key==='Escape')closeChartPreviewBtn();});
    </script>
</body>
</html>

