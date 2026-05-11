<?php
require_once 'db_config.php';

echo "=== SALES OVERVIEW DEBUG ===\n\n";

// Check what unit types exist
$result = $conn->query('SELECT DISTINCT unit_type FROM delivery_records LIMIT 10');
echo "Unit types in database:\n";
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  - " . ($row['unit_type'] ?: 'NULL') . "\n";
    }
}

// Check total records
$result = $conn->query('SELECT COUNT(*) as cnt FROM delivery_records');
$row = $result->fetch_assoc();
echo "\nTotal delivery records: " . $row['cnt'] . "\n";

// Check records for year 2026
$result = $conn->query('SELECT COUNT(*) as cnt FROM delivery_records WHERE delivery_year = 2026');
$row = $result->fetch_assoc();
echo "Records for year 2026: " . $row['cnt'] . "\n";

// Check what years have data
$result = $conn->query('SELECT DISTINCT delivery_year FROM delivery_records ORDER BY delivery_year DESC LIMIT 10');
echo "\nYears with data:\n";
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  - " . $row['delivery_year'] . "\n";
    }
}

// Check specific unit type filter
$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE unit_type IN ('1a', '2a', '4a')");
$row = $result->fetch_assoc();
echo "\nRecords with unit_type IN ('1a', '2a', '4a'): " . $row['cnt'] . "\n";

// Check if any records with unit_type = 'XA'
$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE unit_type = 'XA'");
$row = $result->fetch_assoc();
echo "Records with unit_type = 'XA': " . $row['cnt'] . "\n";

// Sample records
echo "\nSample records:\n";
$result = $conn->query('SELECT id, invoice_no, unit_type, delivery_year, quantity, company_name FROM delivery_records LIMIT 5');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "  ID: {$row['id']}, Invoice: {$row['invoice_no']}, Type: {$row['unit_type']}, Year: {$row['delivery_year']}, Qty: {$row['quantity']}, Co: {$row['company_name']}\n";
    }
}
?>
