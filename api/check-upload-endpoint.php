<?php
/**
 * Check upload-data.php to see what endpoint it calls
 */
header('Content-Type: application/json');

$report = [
    'upload_endpoints' => [],
    'check_what_happened' => 'Need to identify which upload method was used',
];

// Check upload-data.php JavaScript to see where it POSTs
$upload_data_content = file_get_contents(__DIR__ . '/../upload-data.php');

// Find the fetch URL
if (preg_match('/fetch\([\'"]([^\'"]*)[\'"]/i', $upload_data_content, $matches)) {
    $report['upload_endpoints'][] = 'Main endpoint: ' . $matches[1];
}

// Check what inventory.php does
$inventory_content = file_get_contents(__DIR__ . '/../inventory.php');

if (preg_match('/import.*?php|upload.*?php/i', $inventory_content, $matches)) {
    $report['inventory_note'] = 'inventory.php likely uses upload-data.php logic';
}

// The key question: which endpoint received the data?
$report['diagnosis'] = [
    '1' => 'If you uploaded from "Upload Data" page → uses /api/import-data.php (general sales endpoint)',
    '2' => 'If you uploaded from "Inventory" page → should use specific inventory endpoint',
    '3' => 'Only 738 records imported suggests truncation OR many rows were invalid/skipped',
    'Question' => 'Did you upload from "Upload Data" page or "Inventory" page?'
];

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
