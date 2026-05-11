<?php
require_once 'db_config.php';

// Check for Andison Industrial records
echo "=== Checking for Andison Industrial in company_name ===\n";
$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE company_name = 'Andison Industrial'");
$row = $result->fetch_assoc();
echo "Records with company_name = 'Andison Industrial': " . $row['cnt'] . "\n";

echo "\n=== Sample of Andison Industrial records ===\n";
$result = $conn->query("SELECT id, invoice_no, item_code, item_name, company_name, sold_to FROM delivery_records WHERE company_name = 'Andison Industrial' LIMIT 10");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Invoice: " . $row['invoice_no'] . " | Item: " . $row['item_code'] . " | Company: " . $row['company_name'] . "\n";
}

// Count also by sold_to since the page uses that for display if company_name is empty
echo "\n\n=== Checking for Andison Industrial in sold_to ===\n";
$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE sold_to = 'Andison Industrial'");
$row = $result->fetch_assoc();
echo "Records with sold_to = 'Andison Industrial': " . $row['cnt'] . "\n";
?>
