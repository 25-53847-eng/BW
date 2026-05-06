<?php
require '../db_config.php';

// Check what would show up in inventory with our filter
$r = $conn->query("SELECT item_code, item_name, quantity, sold_to, company_name FROM delivery_records 
WHERE (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '') 
LIMIT 20");

echo "Sample records WITHOUT sold_to (what should be in inventory):\n";
while ($row = $r->fetch_assoc()) {
    echo "  - Code: " . $row['item_code'] . ", Name: " . $row['item_name'] . ", Qty: " . $row['quantity'] . ", Sold_to: [" . ($row['sold_to'] ?? 'NULL') . "], Company: " . $row['company_name'] . "\n";
}

echo "\n";

// Check inventory.php main query to see what it's actually getting
$owner_user_id = $_SESSION['user_id'] ?? 'NOT_SET';
$owner_filter = "owner_user_id = '" . $owner_user_id . "' AND ";

$r2 = $conn->query("SELECT item_code, SUM(quantity) as total FROM delivery_records 
WHERE $owner_filter company_name = 'Stock Addition' 
AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '') 
GROUP BY item_code 
ORDER BY item_code 
LIMIT 20");

echo "What inventory.php should group:\n";
if ($r2 && $r2->num_rows > 0) {
    while ($row = $r2->fetch_assoc()) {
        echo "  - Code: " . $row['item_code'] . ", Total: " . $row['total'] . "\n";
    }
} else {
    echo "  (No Stock Addition records found - this is the issue!)\n";
}
?>

