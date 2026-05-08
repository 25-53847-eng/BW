<?php
/**
 * Inventory diagnostic - check Stock Addition records
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'inventory_analysis' => [],
    'issues' => [],
];

if ($conn instanceof mysqli) {
    // 1. Count Stock Addition (Inventory) records
    $result = $conn->query("SELECT COUNT(*) as cnt, SUM(quantity) as total_qty FROM delivery_records WHERE company_name = 'Stock Addition'");
    if ($result) {
        $row = $result->fetch_assoc();
        $report['inventory_analysis']['stock_addition_total'] = [
            'records' => intval($row['cnt']),
            'total_qty' => intval($row['total_qty'] ?? 0),
        ];
    }
    
    // 2. Check by dataset
    $result = $conn->query("
        SELECT dataset_name, COUNT(*) as cnt, SUM(quantity) as total_qty
        FROM delivery_records
        WHERE company_name = 'Stock Addition'
        GROUP BY dataset_name
    ");
    
    if ($result) {
        $by_dataset = [];
        while ($row = $result->fetch_assoc()) {
            $by_dataset[] = [
                'dataset' => $row['dataset_name'],
                'records' => intval($row['cnt']),
                'total_qty' => intval($row['total_qty'] ?? 0),
            ];
        }
        $report['inventory_analysis']['by_dataset'] = $by_dataset;
    }
    
    // 3. Check for Andison Manila inventory
    $result = $conn->query("
        SELECT COUNT(*) as cnt, SUM(quantity) as total_qty
        FROM delivery_records
        WHERE company_name = 'Stock Addition'
        AND LOWER(sold_to) IN ('andison manila', 'to andison manila', 'stock in manila')
    ");
    
    if ($result) {
        $row = $result->fetch_assoc();
        $report['inventory_analysis']['andison_inventory_marked'] = [
            'records' => intval($row['cnt']),
            'total_qty' => intval($row['total_qty'] ?? 0),
        ];
    }
    
    // 4. Check for inventory items without sold_to (unassigned)
    $result = $conn->query("
        SELECT COUNT(*) as cnt, SUM(quantity) as total_qty
        FROM delivery_records
        WHERE company_name = 'Stock Addition'
        AND (sold_to IS NULL OR sold_to = '' OR sold_to = '-')
    ");
    
    if ($result) {
        $row = $result->fetch_assoc();
        $report['inventory_analysis']['unassigned_inventory'] = [
            'records' => intval($row['cnt']),
            'total_qty' => intval($row['total_qty'] ?? 0),
            'note' => 'Inventory items not marked as Andison Manila',
        ];
    }
    
    // 5. Check if max_input_vars might have truncated inventory uploads
    $result = $conn->query("
        SELECT DATE(created_at) as import_date, COUNT(*) as cnt, SUM(quantity) as qty
        FROM delivery_records
        WHERE company_name = 'Stock Addition'
        GROUP BY DATE(created_at)
        ORDER BY import_date DESC
        LIMIT 10
    ");
    
    if ($result) {
        $recent = [];
        while ($row = $result->fetch_assoc()) {
            $recent[] = [
                'date' => $row['import_date'],
                'records' => intval($row['cnt']),
                'total_qty' => intval($row['qty'] ?? 0),
            ];
        }
        $report['inventory_analysis']['recent_inventory_imports'] = $recent;
    }
    
    // 6. Check for potential truncation patterns
    $result = $conn->query("
        SELECT COUNT(*) as cnt FROM delivery_records WHERE company_name = 'Stock Addition'
    ");
    
    if ($result) {
        $row = $result->fetch_assoc();
        $total = intval($row['cnt']);
        
        // If exactly at max_input_vars boundary, likely truncated
        if ($total >= 9990 && $total <= 10010) {
            $report['issues'][] = "⚠️  Inventory count near max_input_vars boundary (10000) - possible truncation";
        }
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
