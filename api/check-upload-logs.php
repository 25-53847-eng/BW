<?php
/**
 * Check PHP error logs and recent uploads
 */
header('Content-Type: application/json');

$report = [
    'php_error_log' => [],
    'recent_uploads' => [],
    'inventory_records_by_dataset' => [],
];

// 1. Check PHP error log
$error_log = ini_get('error_log');
if ($error_log && file_exists($error_log)) {
    $lines = array_reverse(file($error_log, FILE_SKIP_EMPTY_LINES | FILE_NO_NEW_LINES));
    $report['php_error_log'] = array_slice($lines, 0, 20);
    $report['error_log_path'] = $error_log;
}

// 2. Check recent imports in database
require_once __DIR__ . '/../db_config.php';

if ($conn instanceof mysqli) {
    // Check all datasets and their import status
    $result = $conn->query("
        SELECT 
            DISTINCT dataset_name,
            COUNT(*) as total_records,
            SUM(quantity) as total_qty,
            COUNT(DISTINCT company_name) as company_count,
            MAX(created_at) as last_import,
            GROUP_CONCAT(DISTINCT company_name) as companies
        FROM delivery_records
        WHERE dataset_name LIKE '%Inventory%'
        GROUP BY dataset_name
        ORDER BY created_at DESC
        LIMIT 10
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report['recent_uploads'][] = [
                'dataset' => $row['dataset_name'],
                'records' => intval($row['total_records']),
                'total_qty' => intval($row['total_qty'] ?? 0),
                'companies' => $row['companies'],
                'last_import' => $row['last_import'],
            ];
        }
    }
    
    // Check which pages might have uploaded
    $report['debug_info'] = [
        'inventory_page' => 'inventory.php',
        'andison_page' => 'andison-manila.php',
        'employee_inventory' => 'employee/inventory.php',
    ];
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
