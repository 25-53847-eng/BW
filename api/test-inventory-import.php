<?php
/**
 * Test inventory import with sample data
 */
header('Content-Type: application/json');

// Test data structure matching import-inventory.php expectations
$testData = [
    // Header row
    [
        'ITEMS' => 'MCX-TEST-001',
        'DESCRIPTION' => 'Test Multi Gas Indicator',
        'UOM' => 'Unit',
        'INVENTORY' => 50,
        'BOX' => 'BOX-A',
        'STATUS' => 'Active'
    ],
    // Data rows
    [
        'ITEMS' => 'MCX-001',
        'DESCRIPTION' => 'Multi Gas Detector - Type A',
        'UOM' => 'Unit',
        'INVENTORY' => 100,
        'BOX' => 'BOX-A1',
        'STATUS' => 'In Stock'
    ],
    [
        'ITEMS' => 'SGL-001',
        'DESCRIPTION' => 'Single Gas Detector - O2',
        'UOM' => 'Unit',
        'INVENTORY' => 50,
        'BOX' => 'BOX-B1',
        'STATUS' => 'In Stock'
    ],
    [
        'ITEMS' => 'SGL-002',
        'DESCRIPTION' => 'Single Gas Detector - LEL',
        'UOM' => 'Unit',
        'INVENTORY' => 75,
        'BOX' => 'BOX-B2',
        'STATUS' => 'In Stock'
    ],
];

$payload = [
    'data' => $testData,
    'filename' => 'test-inventory.xlsx',
];

echo json_encode([
    'test_payload' => $payload,
    'payload_size_bytes' => strlen(json_encode($payload)),
    'data_rows' => count($testData) - 1,
    'message' => 'This is test data that should import 3 items with 225 total quantity'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
