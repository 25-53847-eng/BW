<?php
require '../db_config.php';

$item_code = 'MC2-FPCB1';

echo "Checking for unconsolidated records...\n\n";

$r = $conn->query("SELECT COUNT(*) as cnt, SUM(quantity) as total FROM delivery_records 
WHERE item_code = '$item_code' 
AND company_name != 'Stock Addition'
AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')");

$row = $r->fetch_assoc();
echo "Non-Stock-Addition records walang sold_to: " . $row['cnt'] . " records, " . $row['total'] . " units\n";

if ($row['cnt'] > 0) {
    echo "\nThese should have been consolidated! Sample:\n";
    $r2 = $conn->query("SELECT company_name, quantity, sold_to FROM delivery_records 
    WHERE item_code = '$item_code' 
    AND company_name != 'Stock Addition'
    AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')
    LIMIT 10");
    
    while ($row = $r2->fetch_assoc()) {
        echo "- Company: " . $row['company_name'] . ", Qty: " . $row['quantity'] . ", Sold_to: [" . ($row['sold_to'] ?? 'NULL') . "]\n";
    }
} else {
    echo "? All records properly consolidated!\n";
}

echo "\n244 + 5 = 249. Is there a specific place where the 5 units should have come from?\n";
?>

