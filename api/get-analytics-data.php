<?php
ob_start();
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once __DIR__ . '/../db_config.php';

$user_id = intval($_SESSION['user_id']);
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$dataset = isset($_GET['dataset']) ? trim($_GET['dataset']) : '';

header('Content-Type: application/json');

try {
    // Build dataset filter
    $dataset_filter = "";
    if (!empty($dataset)) {
        $safe_dataset = $conn->real_escape_string($dataset);
        $dataset_filter = " AND dataset_name = '$safe_dataset'";
    }

    // Build year filter
    $year_filter = " AND delivery_year = $year";
    $combined_filter = $dataset_filter . $year_filter;

    $response = [];

    // Total delivered to Andison
    $result = $conn->query("SELECT COALESCE(SUM(quantity), 0) as total_units FROM delivery_records WHERE sold_to = 'Andison Industrial'$combined_filter");
    if ($result && $row = $result->fetch_assoc()) {
        $response['totalAndison'] = intval($row['total_units']);
    }

    // Total unique companies
    $result = $conn->query("SELECT COUNT(DISTINCT company_name) as company_count FROM delivery_records WHERE company_name IS NOT NULL AND company_name != ''$combined_filter");
    if ($result && $row = $result->fetch_assoc()) {
        $response['companyCount'] = intval($row['company_count']);
    }

    // Monthly data
    $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    $monthlyData = [];
    $monthlyLabels = [];
    $monthlyDelivered = [];

    $result = $conn->query("
        SELECT delivery_month, COALESCE(SUM(quantity), 0) as total_qty
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
        $monthData = [];
        while ($row = $result->fetch_assoc()) {
            $monthData[$row['delivery_month']] = intval($row['total_qty']);
            $monthlyLabels[] = substr($row['delivery_month'], 0, 3);
        }
        // Ensure all months are present
        foreach ($months as $m) {
            $monthlyDelivered[] = isset($monthData[$m]) ? $monthData[$m] : 0;
        }
    }

    $response['monthlyLabels'] = array_map(function($m){ return substr($m,0,3); }, $months);
    $response['monthlyDelivered'] = $monthlyDelivered;

    // Top 15 companies
    $topCompanies = [];
    $result = $conn->query("
        SELECT sold_to as company_name, COALESCE(SUM(quantity), 0) as total_qty
        FROM delivery_records 
        WHERE sold_to IS NOT NULL AND sold_to != '' AND TRIM(sold_to) != '' 
          AND (inventory_status IS NULL OR inventory_status = '')$combined_filter
        GROUP BY sold_to 
        ORDER BY total_qty DESC 
        LIMIT 15
    ");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $topCompanies[] = [
                'name' => $row['company_name'],
                'value' => intval($row['total_qty'])
            ];
        }
    }
    $response['topCompanies'] = $topCompanies;

    // Group A and Group B products
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
        
        return 'Group A - Single Gas';
    }

    // Products/Items data
    $productsData = [];
    $groupA = [];
    $groupB = [];
    
    $result = $conn->query("
        SELECT item_name, item_code, COALESCE(SUM(quantity), 0) as total_qty
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
            $groupLabel = identifyGrouping($row['item_name'] ?? $row['item_code'] ?? '');
            $item = [
                'name' => $row['item_name'] ?: $row['item_code'],
                'qty' => intval($row['total_qty'])
            ];
            
            if ($groupLabel === 'Group B - Multi Gas') {
                $groupB[] = $item;
            } else {
                $groupA[] = $item;
            }
        }
    }

    $response['groupA'] = $groupA;
    $response['groupB'] = $groupB;

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'year' => $year,
        'data' => $response
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
