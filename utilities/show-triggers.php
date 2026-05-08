<?php
require_once '../db_config.php';

echo "=== TRIGGER DEFINITIONS ===\n\n";

$triggers = $conn->query("SHOW CREATE TRIGGER trg_delivery_records_set_owner");
if($row = $triggers->fetch_assoc()) {
    echo "TRIGGER 1: trg_delivery_records_set_owner\n";
    echo "---\n";
    echo $row['SQL Original Statement'] . "\n\n";
}

$triggers2 = $conn->query("SHOW CREATE TRIGGER delivery_deduct_inventory");
if($row2 = $triggers2->fetch_assoc()) {
    echo "TRIGGER 2: delivery_deduct_inventory\n";
    echo "---\n";
    echo $row2['SQL Original Statement'] . "\n";
}

$conn->close();
?>


