<?php
require 'db_config.php';

// Check what's in the "Sales record not found" entries
$sql = "SELECT * FROM delivery_records 
        WHERE company_name = 'Sales record not found' 
        LIMIT 20";

$result = $conn->query($sql);

echo "Sample 'Sales record not found' entries:\n\n";
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . "\n";
    echo "Item: " . $row['item_code'] . " - " . $row['item_description'] . "\n";
    echo "Qty: " . $row['quantity'] . "\n";
    echo "Invoice: " . $row['invoice_number'] . "\n";
    echo "Date: " . $row['delivery_date'] . "\n";
    echo "Serial: " . $row['serial_number'] . "\n";
    echo "---\n";
}

// Count how many
$sql = "SELECT COUNT(*) as cnt FROM delivery_records WHERE company_name = 'Sales record not found'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "\n\nTotal 'Sales record not found' entries: " . $row['cnt'] . "\n";

// Check if there's any pattern - maybe notes or reference in other fields
$sql = "SELECT DISTINCT item_code, item_description, COUNT(*) as cnt
        FROM delivery_records 
        WHERE company_name = 'Sales record not found'
        GROUP BY item_code, item_description
        ORDER BY cnt DESC
        LIMIT 10";

$result = $conn->query($sql);
echo "\n\nMost common items in 'Sales record not found':\n";
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['item_code'] . " (" . $row['cnt'] . "x)\n";
}
?>
