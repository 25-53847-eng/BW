<?php
require 'db_config.php';

echo "Testing insert with defaults...\n";

$sql = "INSERT INTO delivery_records (invoice_no, delivery_month, delivery_day, item_code, item_name, company_name, quantity, status, dataset_name)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("PREPARE ERROR: " . $conn->error);
}

$invoice = 'TEST-' . time();
$month = 'May';
$day = 8;
$code = 'TEST-CODE';
$name = 'Test Item';
$company = 'Test Company';
$qty = 1;
$status = 'Delivered';
$dataset = 'TEST-DATASET';

$stmt->bind_param('ssisssiss', $invoice, $month, $day, $code, $name, $company, $qty, $status, $dataset);

if ($stmt->execute()) {
    echo "✓ INSERT SUCCESSFUL - ID: " . $conn->insert_id . "\n";
} else {
    echo "✗ INSERT FAILED: " . $stmt->error . "\n";
}

$stmt->close();
