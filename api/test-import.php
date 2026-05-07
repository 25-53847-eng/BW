<?php
header('Content-Type: application/json');

ob_start();

// Test that we can respond with JSON
$testData = [
    'success' => true,
    'message' => 'API is responding correctly',
    'timestamp' => date('Y-m-d H:i:s'),
    'session_status' => session_status() === PHP_SESSION_NONE ? 'not started' : 'active'
];

ob_end_clean();
echo json_encode($testData);
exit;
?>
