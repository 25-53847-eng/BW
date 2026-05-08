<?php
/**
 * Detailed import diagnostics - identifies data loss points
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'issues_found' => [],
    'recommendations' => [],
    'database_analysis' => [],
];

// 1. Check if max_input_vars is limiting imports
$max_input_vars = intval(ini_get('max_input_vars')) ?: 1000;
if ($max_input_vars < 5000) {
    $report['issues_found'][] = "⚠️  max_input_vars is {$max_input_vars} - might limit large imports";
    $report['recommendations'][] = "Increase max_input_vars to 10000+ in php.ini";
}

// 2. Check POST size
$post_max = ini_get('post_max_size');
$upload_max = ini_get('upload_max_filesize');
if ($post_max !== $upload_max) {
    $report['issues_found'][] = "⚠️  post_max_size ($post_max) differs from upload_max_filesize ($upload_max)";
}

// 3. Analyze dataset counts
if ($conn instanceof mysqli) {
    // Count by dataset
    $result = $conn->query("
        SELECT dataset_name, COUNT(*) as cnt, SUM(quantity) as total_qty
        FROM delivery_records
        GROUP BY dataset_name
        ORDER BY cnt DESC
    ");
    
    if ($result) {
        $datasets = [];
        while ($row = $result->fetch_assoc()) {
            $datasets[] = [
                'name' => $row['dataset_name'] ?? 'NULL',
                'records' => intval($row['cnt']),
                'total_qty' => intval($row['total_qty'] ?? 0),
            ];
        }
        $report['database_analysis']['datasets'] = $datasets;
    }
    
    // Check for truncated imports (very large single-day imports suggesting batching)
    $result = $conn->query("
        SELECT DATE(created_at) as import_date, COUNT(*) as records_imported
        FROM delivery_records
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY DATE(created_at)
        ORDER BY import_date DESC
    ");
    
    if ($result) {
        $recent_imports = [];
        while ($row = $result->fetch_assoc()) {
            $recent_imports[] = [
                'date' => $row['import_date'],
                'count' => intval($row['records_imported']),
            ];
        }
        $report['database_analysis']['recent_imports'] = $recent_imports;
    }
    
    // Check if any imports failed or were incomplete
    $result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
    if ($result) {
        $row = $result->fetch_assoc();
        if ($row['cnt'] == 0) {
            $report['issues_found'][] = "⚠️  No records imported in last 24 hours";
        }
    }
}

// 4. Check PHP configuration
$report['php_config'] = [
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'max_input_vars' => $max_input_vars,
    'post_max_size' => $post_max,
    'upload_max_filesize' => $upload_max,
];

// 5. Recommendations summary
if (empty($report['issues_found'])) {
    $report['recommendations'][] = "✅ All system limits appear adequate";
    $report['recommendations'][] = "Check: Are Excel column names spelled correctly? (must match: Invoice No., Serial No., etc.)";
    $report['recommendations'][] = "Check: Are there validation errors silently skipping rows?";
} else {
    $report['recommendations'][] = "Review browser console for JavaScript errors during upload";
    $report['recommendations'][] = "Check server logs: tail -f " . dirname(__DIR__) . "/logs/*";
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
