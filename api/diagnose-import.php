<?php
/**
 * Diagnostic script to identify import issues
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$diagnostics = [];

// 1. Check PHP configuration limits
$diagnostics['php_limits'] = [
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_input_vars' => ini_get('max_input_vars'),
];

// 2. Check database connection
$diagnostics['database'] = [
    'connected' => isset($conn) && $conn ? true : false,
    'driver' => $conn instanceof mysqli ? 'MySQLi' : ($conn instanceof PDO ? 'PDO' : 'Unknown'),
];

// 3. Check delivery_records table
if ($conn instanceof mysqli) {
    $result = $conn->query("SELECT COUNT(*) as total FROM delivery_records");
    if ($result) {
        $row = $result->fetch_assoc();
        $diagnostics['database']['delivery_records_count'] = intval($row['total'] ?? 0);
    }
    
    // Check for recently imported records
    $result = $conn->query("SELECT COUNT(*) as total FROM delivery_records WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
    if ($result) {
        $row = $result->fetch_assoc();
        $diagnostics['database']['records_last_24hrs'] = intval($row['total'] ?? 0);
    }
    
    // Sample inventory company to check data
    $result = $conn->query("SELECT COUNT(*) as total, SUM(quantity) as qty FROM delivery_records WHERE company_name = 'Andison Industrial'");
    if ($result) {
        $row = $result->fetch_assoc();
        $diagnostics['database']['andison_records'] = [
            'count' => intval($row['total'] ?? 0),
            'total_qty' => intval($row['qty'] ?? 0)
        ];
    }
}

// 4. Check for import API specific issues
$diagnostics['import_api_check'] = [
    'import_data_exists' => file_exists(__DIR__ . '/import-data.php'),
    'import_inventory_exists' => file_exists(__DIR__ . '/import-inventory.php'),
];

// 5. Analyze if there's a JSON payload limit issue
$diagnostics['json_analysis'] = [
    'max_json_size_estimated' => 'post_max_size sets limit',
    'note' => 'If post_max_size < 50M, large Excel imports will be truncated',
];

echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
