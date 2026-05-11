<?php
require_once 'db_config.php';

echo "=== Checking database after upload attempt ===\n\n";

$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records");
$row = $result->fetch_assoc();
echo "Total delivery_records: " . $row['cnt'] . "\n";

// Check for unique companies
echo "\n=== Unique company names ===\n";
$result = $conn->query("SELECT DISTINCT company_name, COUNT(*) as cnt FROM delivery_records GROUP BY company_name ORDER BY cnt DESC");
while ($row = $result->fetch_assoc()) {
    echo $row['company_name'] . ": " . $row['cnt'] . "\n";
}

// Show sample records with all important fields
echo "\n=== Sample records ===\n";
$result = $conn->query("SELECT id, invoice_no, item_code, item_name, company_name, sold_to, quantity FROM delivery_records LIMIT 10");
while ($row = $result->fetch_assoc()) {
    echo "Invoice: " . $row['invoice_no'] . " | Item: " . $row['item_code'] . " | Company: " . $row['company_name'] . " | Qty: " . $row['quantity'] . "\n";
}
?>
