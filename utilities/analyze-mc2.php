<?php
require '../db_config.php';

$item_code = 'MC2-FPCB1';

// Check what "should" be in stock based on empty sold_to
echo "Analysis: What SHOULD be in MC2-FPCB1 stock:\n\n";

// Show all unique sold_to values
echo "All unique sold_to values for this item:\n";
$r = $conn->query("SELECT DISTINCT sold_to, COUNT(*) as cnt, SUM(quantity) as qty_sum FROM delivery_records 
WHERE item_code = '$item_code' 
GROUP BY sold_to
ORDER BY qty_sum DESC");

while ($row = $r->fetch_assoc()) {
    $sold_to_display = $row['sold_to'] === null || $row['sold_to'] === '' ? '(EMPTY/NULL)' : $row['sold_to'];
    echo "- $sold_to_display: " . $row['cnt'] . " records, " . $row['qty_sum'] . " units\n";
}

echo "\n\nTotal across all: ";
$r_total = $conn->query("SELECT SUM(quantity) as total FROM delivery_records WHERE item_code = '$item_code'");
$row_total = $r_total->fetch_assoc();
echo $row_total['total'] . " units\n";

echo "\nIf 'Sales record not found' counts as stock, should be: 244 + 424 = 668\n";
echo "But user says should be 249...\n";

// Check if there's data with spaces only in sold_to
echo "\n\nChecking for sold_to with only whitespace:\n";
$r_space = $conn->query("SELECT COUNT(*) as cnt, SUM(quantity) as total FROM delivery_records 
WHERE item_code = '$item_code' AND (sold_to = ' ' OR sold_to = '  ' OR LENGTH(TRIM(COALESCE(sold_to, ''))) = 0)");
$row_space = $r_space->fetch_assoc();
echo "Count: " . $row_space['cnt'] . ", Qty: " . $row_space['total'] . "\n";
?>


