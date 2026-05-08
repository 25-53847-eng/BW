<?php
require '../db_config.php';

$item_code = 'MC2-FPCB1';

echo "MC2-FPCB1 by dataset:\n\n";

$r = $conn->query("SELECT dataset_name, SUM(quantity) as total 
FROM delivery_records 
WHERE item_code = '$item_code' 
AND company_name = 'Stock Addition' 
AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')
GROUP BY dataset_name");

while ($row = $r->fetch_assoc()) {
    echo "Dataset: " . $row['dataset_name'] . " = " . $row['total'] . " units\n";
}

echo "\n\nALL MC2-FPCB1 walang-sold_to by dataset:\n";

$r2 = $conn->query("SELECT dataset_name, SUM(quantity) as total 
FROM delivery_records 
WHERE item_code = '$item_code' 
AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')
GROUP BY dataset_name");

while ($row = $r2->fetch_assoc()) {
    echo "Dataset: " . $row['dataset_name'] . " = " . $row['total'] . " units\n";
}
?>

