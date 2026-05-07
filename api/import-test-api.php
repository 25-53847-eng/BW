<?php
ob_start();

// Log everything to understand what's happening
$logFile = __DIR__ . '/../logs/import-test.log';
if (!is_dir(dirname($logFile))) {
    @mkdir(dirname($logFile), 0755, true);
}

error_log("\n=== IMPORT TEST REQUEST ===", 3, $logFile);
error_log("Timestamp: " . date('Y-m-d H:i:s'), 3, $logFile);
error_log("Content-Type: " . $_SERVER['CONTENT_TYPE'], 3, $logFile);
error_log("Content-Length: " . $_SERVER['CONTENT_LENGTH'], 3, $logFile);

// Get raw input
$json = file_get_contents('php://input');
error_log("Input size: " . strlen($json) . " bytes", 3, $logFile);

if (strlen($json) > 0) {
    error_log("First 200 chars: " . substr($json, 0, 200), 3, $logFile);
}

// Try to decode
$data = json_decode($json, true);

if ($data === null) {
    $error = json_last_error_msg();
    error_log("JSON Error: " . $error, 3, $logFile);
    error_log("---", 3, $logFile);
    
    // Clean buffers and return JSON error
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'JSON Parse Error: ' . $error,
        'input_size' => strlen($json)
    ]);
    exit;
}

error_log("Parsed successfully. Rows count: " . count($data['data'] ?? []), 3, $logFile);
error_log("Dataset: " . ($data['dataset_name'] ?? 'N/A'), 3, $logFile);
error_log("---", 3, $logFile);

// Clean buffers and return success
while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Test API received ' . count($data['data'] ?? []) . ' rows',
    'input_size' => strlen($json),
    'dataset_name' => $data['dataset_name'] ?? 'unknown'
]);
