<?php
require 'db_config.php';

// Simple test to see what's causing failures
$test_data = [
    [
        'invoice_no' => 'TEST-001',
        'serial_no' => '',
        'delivery_month' => '', // Empty - should use fallback
        'delivery_day' => '', // Empty - should use fallback
        'delivery_year' => '',
        'record_date' => '',
        'delivery_date' => '',
        'item_code' => '', // Empty - should generate UNKNOWN-xxx
        'item_name' => '', // Empty - should use item_code
        'company_name' => 'Andison Industrial',
        'sold_to' => '',
        'quantity' => 1,
        'status' => 'Delivered',
        'highlight_color' => '',
        'cell_styles' => '',
        'notes' => 'Test record',
        'uom' => '',
        'unit_type' => '',
        'sold_to_month' => '',
        'sold_to_day' => '',
        'groupings' => '',
        'dataset_name' => 'TEST-IMPORT'
    ]
];

// Import test data
$json = json_encode(['data' => $test_data]);
$_SERVER['REQUEST_METHOD'] = 'POST';

// Simulate the import by including the import logic
$request = json_decode($json, true);
$data = $request['data'];
$warranty_rows = [];
$dataset_name = 'TEST-IMPORT';

// Process first record
$record = $data[0];
$index = 0;

// Map
$mapped = array_combine(array_map('strtolower', array_keys($record)), $record);

$item_code = trim(strval($mapped['item_code'])) ?: '';
$item_name = trim(strval($mapped['item_name'])) ?: '';

if (empty($item_code)) {
    $item_code = 'UNKNOWN-' . uniqid();
}
if (empty($item_name)) {
    $item_name = $item_code;
}

$delivery_month = trim(strval($mapped['delivery_month'])) ?: '';
$delivery_day = intval($mapped['delivery_day']) ?: 0;

// Fallback
if (empty($delivery_month) || $delivery_day == 0) {
    $today = new DateTime();
    if (empty($delivery_month)) {
        $delivery_month = $today->format('F');
    }
    if ($delivery_day == 0) {
        $delivery_day = intval($today->format('d'));
    }
}

echo "=== TEST IMPORT RESULT ===\n";
echo "Item Code: " . $item_code . "\n";
echo "Item Name: " . $item_name . "\n";
echo "Delivery Month: " . $delivery_month . "\n";
echo "Delivery Day: " . $delivery_day . "\n";
echo "Company: " . $mapped['company_name'] . "\n";

// Try to insert
$sql = "INSERT INTO delivery_records (invoice_no, serial_no, delivery_month, delivery_day, delivery_year, record_date, delivery_date, item_code, item_name, unit_type, company_name, sold_to, quantity, status, highlight_color, cell_styles, notes, uom, sold_to_month, sold_to_day, groupings, dataset_name)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "PREPARE ERROR: " . $conn->error . "\n";
} else {
    $year = 2026;
    $invoice = 'TEST-001';
    $serial = '';
    $company = 'Andison Industrial';
    $sold_to = '';
    $qty = 1;
    $status = 'Delivered';
    $color = '';
    $styles = '';
    $notes = 'Test';
    $uom = '';
    $month = '';
    $day = 0;
    $grouping = '';
    $ds = 'TEST-IMPORT';
    $record_date = '';
    $delivery_date = '';
    
    if ($stmt->execute()) {
        echo "✓ INSERT SUCCESSFUL\n";
    } else {
        echo "EXECUTE ERROR: " . $stmt->error . "\n";
    }
    $stmt->close();
}
