<?php
/**
 * Check if there are duplicate rows that might be causing skips
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$report = [
    'inventory_status' => [],
    'potential_duplicates' => [],
    'datasets' => [],
];

if ($conn instanceof mysqli) {
    // Count by dataset
    $result = $conn->query("
        SELECT 
            dataset_name,
            COUNT(*) as cnt,
            SUM(quantity) as qty,
            GROUP_CONCAT(DISTINCT company_name) as companies
        FROM delivery_records
        WHERE company_name IN ('Stock Addition', 'to Andison Manila')
        GROUP BY dataset_name
        ORDER BY created_at DESC
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report['datasets'][] = [
                'dataset' => $row['dataset_name'],
                'records' => intval($row['cnt']),
                'qty' => intval($row['qty']),
                'companies' => $row['companies'],
            ];
        }
    }
    
    // Check for duplicate item combinations (same item + qty)
    $result = $conn->query("
        SELECT 
            item_code,
            quantity,
            COUNT(*) as cnt
        FROM delivery_records
        WHERE company_name IN ('Stock Addition', 'to Andison Manila')
        GROUP BY item_code, quantity
        HAVING cnt > 5
        ORDER BY cnt DESC
        LIMIT 10
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report['potential_duplicates'][] = [
                'item_code' => $row['item_code'],
                'quantity' => intval($row['quantity']),
                'times_repeated' => intval($row['cnt']),
            ];
        }
    }
    
    // Summary
    $report['inventory_status'] = [
        'total_stock_addition' => 0,
        'total_andison_manila' => 0,
        'expected_total' => 1946,
        'message' => 'Check if upload completed fully',
    ];
    
    $result = $conn->query("
        SELECT company_name, COUNT(*) as cnt
        FROM delivery_records
        WHERE company_name IN ('Stock Addition', 'to Andison Manila')
        GROUP BY company_name
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ($row['company_name'] === 'Stock Addition') {
                $report['inventory_status']['total_stock_addition'] = intval($row['cnt']);
            } else {
                $report['inventory_status']['total_andison_manila'] = intval($row['cnt']);
            }
        }
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
