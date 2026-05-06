<?php
require '../db_config.php';

echo "=== CHECKING INVENTORY TABLE ===\n\n";

$inv_table = $conn->query("SELECT TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'inventory' AND TABLE_SCHEMA = DATABASE()")->fetch_assoc();
echo "Inventory table collation: " . ($inv_table['TABLE_COLLATION'] ?: 'VIEW or NOT FOUND') . "\n\n";

$item_code = $conn->query("SELECT COLUMN_NAME, COLUMN_TYPE, COLLATION_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'inventory' AND COLUMN_NAME = 'item_code'")->fetch_assoc();
if($item_code) {
    echo "inventory.item_code:\n";
    echo "  Type: {$item_code['COLUMN_TYPE']}\n";
    echo "  Collation: {$item_code['COLLATION_NAME']}\n";
} else {
    echo "inventory.item_code: NOT FOUND (might be a VIEW)\n";
}

echo "\n=== DELIVERY_RECORDS item_code (for comparison) ===\n";
$dr_item_code = $conn->query("SELECT COLUMN_NAME, COLUMN_TYPE, COLLATION_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'delivery_records' AND COLUMN_NAME = 'item_code'")->fetch_assoc();
if($dr_item_code) {
    echo "delivery_records.item_code:\n";
    echo "  Type: {$dr_item_code['COLUMN_TYPE']}\n";
    echo "  Collation: {$dr_item_code['COLLATION_NAME']}\n";
}

if($item_code && $item_code['COLLATION_NAME'] !== $dr_item_code['COLLATION_NAME']) {
    echo "\n⚠️ COLLATION MISMATCH FOUND!\n";
    echo "  inventory.item_code: {$item_code['COLLATION_NAME']}\n";
    echo "  delivery_records.item_code: {$dr_item_code['COLLATION_NAME']}\n";
} else {
    echo "\n✓ Collations match\n";
}

$conn->close();
?>

