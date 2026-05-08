<?php
/**
 * Debug Andison Manila inventory - find where data is going
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$report = [
    'andison_counts' => [],
    'issue_analysis' => [],
    'data_location_map' => [],
];

if ($conn instanceof mysqli) {
    // 1. Count by different Andison variations
    $queries = [
        'company_name = "to Andison Manila"' => 'to Andison Manila company',
        'company_name = "Andison Manila"' => 'Andison Manila company',
        'company_name = "Stock Addition" AND sold_to LIKE "%andison%"' => 'Stock Addition with andison in sold_to',
        'company_name = "Stock Addition" AND sold_to LIKE "%Stock in Manila%"' => 'Stock Addition with Stock in Manila',
        'LOWER(TRIM(sold_to)) IN ("andison manila", "to andison manila", "stock in manila")' => 'All andison manila variations',
        'company_name LIKE "%andison%" OR company_name LIKE "%manila%"' => 'Company name contains andison or manila',
    ];
    
    foreach ($queries as $condition => $label) {
        $result = $conn->query("
            SELECT COUNT(*) as cnt, SUM(quantity) as qty, COUNT(DISTINCT item_code) as items
            FROM delivery_records
            WHERE $condition
        ");
        
        if ($result) {
            $row = $result->fetch_assoc();
            $report['andison_counts'][$label] = [
                'records' => intval($row['cnt']),
                'total_qty' => intval($row['qty'] ?? 0),
                'unique_items' => intval($row['items']),
            ];
        }
    }
    
    // 2. Check what's actually in Stock Addition
    $result = $conn->query("
        SELECT COUNT(*) as cnt, SUM(quantity) as qty
        FROM delivery_records
        WHERE company_name = 'Stock Addition'
    ");
    
    if ($result) {
        $row = $result->fetch_assoc();
        $report['data_location_map']['stock_addition_total'] = [
            'records' => intval($row['cnt']),
            'total_qty' => intval($row['qty'] ?? 0),
        ];
    }
    
    // 3. Sample of what's in Stock Addition
    $result = $conn->query("
        SELECT item_code, sold_to, quantity, dataset_name
        FROM delivery_records
        WHERE company_name = 'Stock Addition'
        LIMIT 10
    ");
    
    if ($result) {
        $samples = [];
        while ($row = $result->fetch_assoc()) {
            $samples[] = [
                'item' => $row['item_code'],
                'sold_to' => $row['sold_to'],
                'qty' => $row['quantity'],
                'dataset' => $row['dataset_name'],
            ];
        }
        $report['data_location_map']['stock_addition_samples'] = $samples;
    }
    
    // 4. Check what routing happened
    $result = $conn->query("
        SELECT company_name, COUNT(*) as cnt, SUM(quantity) as qty
        FROM delivery_records
        GROUP BY company_name
        ORDER BY cnt DESC
        LIMIT 10
    ");
    
    if ($result) {
        $routing = [];
        while ($row = $result->fetch_assoc()) {
            $routing[] = [
                'company' => $row['company_name'],
                'records' => intval($row['cnt']),
                'qty' => intval($row['qty'] ?? 0),
            ];
        }
        $report['data_location_map']['all_companies'] = $routing;
    }
    
    // 5. Check for recently imported data
    $result = $conn->query("
        SELECT company_name, COUNT(*) as cnt
        FROM delivery_records
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY company_name
    ");
    
    if ($result) {
        $recent = [];
        while ($row = $result->fetch_assoc()) {
            $recent[] = [
                'company' => $row['company_name'],
                'records_in_last_hour' => intval($row['cnt']),
            ];
        }
        $report['issue_analysis']['recent_imports_last_hour'] = $recent;
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
