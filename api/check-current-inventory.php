<?php
/**
 * Check what inventory data is in the database NOW
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$report = [
    'stock_addition' => [],
    'andison_manila' => [],
    'status' => 'checking',
];

if ($conn instanceof mysqli) {
    // 1. Stock Addition inventory
    $result = $conn->query("
        SELECT 
            COUNT(*) as record_count,
            SUM(quantity) as total_qty,
            COUNT(DISTINCT item_code) as unique_items
        FROM delivery_records
        WHERE company_name = 'Stock Addition'
    ");
    
    if ($result && $row = $result->fetch_assoc()) {
        $report['stock_addition'] = [
            'records' => intval($row['record_count']),
            'total_quantity' => intval($row['total_qty'] ?? 0),
            'unique_items' => intval($row['unique_items']),
        ];
    }
    
    // 2. Andison Manila inventory
    $result = $conn->query("
        SELECT 
            COUNT(*) as record_count,
            SUM(quantity) as total_qty,
            COUNT(DISTINCT item_code) as unique_items
        FROM delivery_records
        WHERE company_name = 'to Andison Manila'
    ");
    
    if ($result && $row = $result->fetch_assoc()) {
        $report['andison_manila'] = [
            'records' => intval($row['record_count']),
            'total_quantity' => intval($row['total_qty'] ?? 0),
            'unique_items' => intval($row['unique_items']),
        ];
    }
    
    // 3. Check sample quantities
    $report['sample_stock_addition'] = [];
    $result = $conn->query("
        SELECT item_code, quantity, sold_to
        FROM delivery_records
        WHERE company_name = 'Stock Addition'
        LIMIT 5
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report['sample_stock_addition'][] = $row;
        }
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
