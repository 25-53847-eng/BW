<?php
require '../db_config.php';

$item_code = 'MC2-FPCB1';

echo "MC2-FPCB1 stock by user:\n\n";

$r = $conn->query("SELECT owner_user_id, SUM(quantity) as total 
FROM delivery_records 
WHERE item_code = '$item_code' 
AND company_name = 'Stock Addition'
AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')
GROUP BY owner_user_id");

while ($row = $r->fetch_assoc()) {
    echo "User ID " . $row['owner_user_id'] . ": " . $row['total'] . " units\n";
}

echo "\n\nTotal across all users: ";
$r_total = $conn->query("SELECT SUM(quantity) as total FROM delivery_records 
WHERE item_code = '$item_code' 
AND company_name = 'Stock Addition'
AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')");
$row_total = $r_total->fetch_assoc();
echo $row_total['total'] . " units\n";
?>

