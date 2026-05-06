<?php
require '../db_config.php';

$item_code = 'MC2-FPCB1';

// Check records where sold_to has specific problematic values
echo "Records with 'Sales record not found':\n";
$r1 = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records 
WHERE item_code = '$item_code' AND sold_to = 'Sales record not found'");
$row1 = $r1->fetch_assoc();
echo "Count: " . $row1['cnt'] . "\n";

// Check qty sum of those
$r2 = $conn->query("SELECT SUM(quantity) as total FROM delivery_records 
WHERE item_code = '$item_code' AND sold_to = 'Sales record not found'");
$row2 = $r2->fetch_assoc();
echo "Total Qty: " . ($row2['total'] ?? 0) . "\n\n";

// Check records that might need to be in stock (blank/missing sold_to or only spaces)
echo "Records with various empty-like sold_to:\n";
$r3 = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records 
WHERE item_code = '$item_code' AND (sold_to = '' OR sold_to = ' ' OR sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')");
$row3 = $r3->fetch_assoc();
echo "Walang sold_to count: " . $row3['cnt'] . "\n";

$r4 = $conn->query("SELECT SUM(quantity) as total FROM delivery_records 
WHERE item_code = '$item_code' AND (sold_to = '' OR sold_to = ' ' OR sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')");
$row4 = $r4->fetch_assoc();
echo "Walang sold_to total qty: " . ($row4['total'] ?? 0) . "\n\n";

// Show first 10 'walang' records
echo "Sample walang-sold_to records:\n";
$r5 = $conn->query("SELECT quantity, sold_to FROM delivery_records 
WHERE item_code = '$item_code' AND (sold_to = '' OR sold_to = ' ' OR sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')
LIMIT 10");
while ($row = $r5->fetch_assoc()) {
    echo "- Qty: " . $row['quantity'] . ", Sold_to: [" . ($row['sold_to'] ?? 'NULL') . "]\n";
}
?>

