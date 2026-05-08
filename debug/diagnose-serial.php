<?php
require '../db_config.php';

// Check serial_no column
$result = $conn->query("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='delivery_records' AND COLUMN_NAME='serial_no'");

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo "? serial_no column exists: " . $row['COLUMN_TYPE'] . "\n";
} else {
    echo "? serial_no column does NOT exist!\n";
}

// Check if there are any non-empty serial numbers at all
$check = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE serial_no IS NOT NULL AND serial_no != ''");
if ($check) {
    $row = $check->fetch_assoc();
    echo "Records with non-empty serial_no: " . $row['cnt'] . "\n";
}

// Sample records
echo "\n=== Sample Records (invoice 5272760144) ===\n";
$sample = $conn->query("SELECT id, serial_no, quantity FROM delivery_records WHERE invoice_no = '5272760144' LIMIT 3");
if ($sample) {
    while ($row = $sample->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Serial_No: [" . ($row['serial_no'] ?: 'NULL/EMPTY') . "] | Qty: " . $row['quantity'] . "\n";
    }
}
?>

