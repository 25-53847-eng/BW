<?php
/**
 * Complete inventory audit - check all tables
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'tables_available' => [],
    'audit' => [],
];

if ($conn instanceof mysqli) {
    // 1. List all tables
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        $tables = [];
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        $report['tables_available'] = $tables;
    }
    
    // 2. Check if there's an 'inventory' table
    $result = $conn->query("SHOW TABLES LIKE 'inventory%'");
    if ($result && $result->num_rows > 0) {
        $report['audit']['has_inventory_table'] = true;
        
        // Count records in inventory table
        $inventoryResult = $conn->query("SELECT COUNT(*) as cnt, SUM(quantity) as qty FROM inventory");
        if ($inventoryResult) {
            $row = $inventoryResult->fetch_assoc();
            $report['audit']['inventory_table_records'] = [
                'records' => intval($row['cnt']),
                'total_qty' => intval($row['qty'] ?? 0),
            ];
        }
    } else {
        $report['audit']['has_inventory_table'] = false;
    }
    
    // 3. Check delivery_records with 'Inventory' status
    $result = $conn->query("
        SELECT COUNT(*) as cnt, SUM(quantity) as qty 
        FROM delivery_records 
        WHERE status = 'Inventory' OR company_name = 'Stock Addition'
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $report['audit']['delivery_records_as_inventory'] = [
            'records' => intval($row['cnt']),
            'total_qty' => intval($row['qty'] ?? 0),
        ];
    }
    
    // 4. Check all records with 'Stock' keyword
    $result = $conn->query("
        SELECT COUNT(*) as cnt, SUM(quantity) as qty 
        FROM delivery_records 
        WHERE company_name LIKE '%Stock%' OR item_name LIKE '%Stock%'
    ");
    if ($result) {
        $row = $result->fetch_assoc();
        $report['audit']['stock_keyword_records'] = [
            'records' => intval($row['cnt']),
            'total_qty' => intval($row['qty'] ?? 0),
        ];
    }
    
    // 5. Check last inserted records to see what was actually imported
    $result = $conn->query("
        SELECT id, created_at, company_name, status, item_code, quantity
        FROM delivery_records
        ORDER BY created_at DESC
        LIMIT 5
    ");
    if ($result) {
        $recent = [];
        while ($row = $result->fetch_assoc()) {
            $recent[] = [
                'id' => $row['id'],
                'created' => $row['created_at'],
                'company' => $row['company_name'],
                'status' => $row['status'],
                'code' => $row['item_code'],
                'qty' => $row['quantity'],
            ];
        }
        $report['audit']['last_5_records'] = $recent;
    }
    
    // 6. Check if inventory upload succeeded but with wrong parameters
    $result = $conn->query("
        SELECT DISTINCT dataset_name 
        FROM delivery_records
        WHERE dataset_name LIKE '%Inventory%'
    ");
    if ($result && $result->num_rows > 0) {
        $datasets = [];
        while ($row = $result->fetch_assoc()) {
            $datasets[] = $row['dataset_name'];
        }
        $report['audit']['inventory_datasets'] = $datasets;
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
