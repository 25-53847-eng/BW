<?php
/**
 * Check current inventory status after upload
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$report = [
    'stock_addition' => [],
    'andison_manila' => [],
    'other_companies' => [],
    'total_all' => 0,
];

if ($conn instanceof mysqli) {
    // Get all company distribution
    $result = $conn->query("
        SELECT 
            company_name, 
            COUNT(*) as record_count,
            SUM(quantity) as total_qty,
            COUNT(DISTINCT item_code) as unique_items
        FROM delivery_records
        GROUP BY company_name
        ORDER BY record_count DESC
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $company = $row['company_name'];
            $data = [
                'records' => intval($row['record_count']),
                'total_quantity' => intval($row['total_qty'] ?? 0),
                'unique_items' => intval($row['unique_items']),
            ];
            
            if ($company === 'Stock Addition') {
                $report['stock_addition'] = $data;
            } elseif ($company === 'to Andison Manila') {
                $report['andison_manila'] = $data;
            } else {
                $report['other_companies'][$company] = $data;
            }
            
            $report['total_all'] += $row['record_count'];
        }
    }
    
    // Sample from each
    $report['samples'] = [];
    
    $result = $conn->query("
        SELECT item_code, quantity, sold_to FROM delivery_records 
        WHERE company_name = 'Stock Addition' LIMIT 3
    ");
    if ($result) {
        $report['samples']['Stock Addition'] = [];
        while ($row = $result->fetch_assoc()) {
            $report['samples']['Stock Addition'][] = $row;
        }
    }
    
    $result = $conn->query("
        SELECT item_code, quantity, sold_to FROM delivery_records 
        WHERE company_name = 'to Andison Manila' LIMIT 3
    ");
    if ($result) {
        $report['samples']['Andison Manila'] = [];
        while ($row = $result->fetch_assoc()) {
            $report['samples']['Andison Manila'][] = $row;
        }
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
