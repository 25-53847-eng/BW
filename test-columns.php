<?php
require_once 'db_config.php';

// Check what's in sold_to column
$sql = "SELECT DISTINCT sold_to FROM delivery_records LIMIT 20";
$result = $conn->query($sql);
echo "Values in sold_to column:\n";
while ($row = $result->fetch_assoc()) {
    echo "  - '" . $row['sold_to'] . "'\n";
}

// Check what's in the first few records
$sql2 = "SELECT id, company_name, sold_to FROM delivery_records LIMIT 10";
$result2 = $conn->query($sql2);
echo "\nFirst 10 records:\n";
while ($row = $result2->fetch_assoc()) {
    echo "  ID:" . $row['id'] . " | company_name: '" . $row['company_name'] . "' | sold_to: '" . $row['sold_to'] . "'\n";
}

// Check if sold_to is mostly empty or has values
$sql3 = "SELECT COUNT(*) as total, 
         COUNT(CASE WHEN sold_to IS NULL OR sold_to = '' THEN 1 END) as empty_sold_to,
         COUNT(CASE WHEN sold_to IS NOT NULL AND sold_to != '' THEN 1 END) as non_empty_sold_to
         FROM delivery_records";
$result3 = $conn->query($sql3);
$row3 = $result3->fetch_assoc();
echo "\nSold_to column stats:\n";
echo "  Total records: " . $row3['total'] . "\n";
echo "  Empty/NULL: " . $row3['empty_sold_to'] . "\n";
echo "  Non-empty: " . $row3['non_empty_sold_to'] . "\n";
?>
