<?php
require '../db_config.php';

$item_code = 'MC2-FPCB1';

echo "Stock Addition records for $item_code:\n\n";

$r = $conn->query("SELECT id, item_code, item_name, quantity, sold_to, company_name, dataset_name, owner_user_id FROM delivery_records 
WHERE company_name = 'Stock Addition' AND item_code = '$item_code'");

$total = 0;
$count = 0;
while ($row = $r->fetch_assoc()) {
    echo "ID: " . $row['id'] . ", Qty: " . $row['quantity'] . ", Dataset: " . $row['dataset_name'] . ", Owner: " . $row['owner_user_id'] . "\n";
    $total += $row['quantity'];
    $count++;
}

echo "\nTotal Stock Addition records: $count\n";
echo "Total quantity: $total\n";

if ($count > 1) {
    echo "\nWARNING: Multiple Stock Addition records exist! Should be consolidated into 1.\n";
}
?>

