<?php
session_start();
require '../db_config.php';

echo "Sample Stock Addition records (what should show in inventory):\n\n";

$r = $conn->query("SELECT item_code, item_name, quantity, sold_to, company_name FROM delivery_records 
WHERE company_name = 'Stock Addition' 
AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '') 
ORDER BY item_code 
LIMIT 15");

while ($row = $r->fetch_assoc()) {
    echo "- " . $row['item_code'] . ": " . $row['item_name'] . " (Qty: " . $row['quantity'] . ")\n";
}

echo "\n\nTotal inventory items: ";
$r2 = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE company_name = 'Stock Addition' AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')");
$row = $r2->fetch_assoc();
echo $row['cnt'] . "\n";
?>

