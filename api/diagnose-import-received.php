<?php
/**
 * Temporary endpoint to log the exact import request and show what was received
 * This helps diagnose why rows are missing
 */
header('Content-Type: application/json');

$json = file_get_contents('php://input');
$request = json_decode($json, true);

$report = [
    'json_received_size' => strlen($json),
    'data_rows_count' => isset($request['data']) ? count($request['data']) : 0,
    'filename' => isset($request['filename']) ? $request['filename'] : 'unknown',
    'first_data_row' => isset($request['data'][0]) ? $request['data'][0] : null,
    'last_data_row' => isset($request['data']) && count($request['data']) > 1 ? $request['data'][count($request['data'])-1] : null,
    'sample_middle_rows' => [],
    'status' => 'DIAGNOSTIC ONLY - Showing what import received',
];

// Get some middle rows
if (isset($request['data']) && count($request['data']) > 10) {
    $midpoint = intval(count($request['data']) / 2);
    for ($i = $midpoint - 1; $i <= $midpoint + 1; $i++) {
        if (isset($request['data'][$i])) {
            $report['sample_middle_rows'][] = $request['data'][$i];
        }
    }
}

// Log to file for later analysis
$log_file = __DIR__ . '/../logs/import-diagnostic-' . date('Y-m-d-H-i-s') . '.json';
@file_put_contents($log_file, json_encode($report, JSON_PRETTY_PRINT));

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
