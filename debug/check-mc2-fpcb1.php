<?php
require '../db_config.php';

$item_code = 'MC2-FPCB1';

// Check Stock Addition
$r1 = $conn->query("SELECT SUM(quantity) as total FROM delivery_records 
WHERE item_code = '$item_code' AND company_name = 'Stock Addition' AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')");
$row1 = $r1->fetch_assoc();
echo "Stock Addition total: " . ($row1['total'] ?? 0) . "\n";

// Check all records walang sold_to (including non-Stock Addition)
$r2 = $conn->query("SELECT SUM(quantity) as total FROM delivery_records 
WHERE item_code = '$item_code' AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')");
$row2 = $r2->fetch_assoc();
echo "ALL walang-sold_to total: " . ($row2['total'] ?? 0) . "\n";

// Check all records
$r3 = $conn->query("SELECT SUM(quantity) as total FROM delivery_records WHERE item_code = '$item_code'");
$row3 = $r3->fetch_assoc();
echo "ALL records total: " . ($row3['total'] ?? 0) . "\n";

// Show individual records
echo "\nIndividual records:\n";
$r4 = $conn->query("SELECT item_code, item_name, quantity, sold_to, company_name FROM delivery_records WHERE item_code = '$item_code' ORDER BY company_name");
while ($row = $r4->fetch_assoc()) {
    echo "- Company: " . $row['company_name'] . ", Qty: " . $row['quantity'] . ", Sold_to: [" . ($row['sold_to'] ?? 'NULL') . "]\n";
}
?>

