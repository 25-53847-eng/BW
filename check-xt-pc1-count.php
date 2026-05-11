<?php
include 'db_config.php';

echo "=== XT-PC1 DATABASE CHECK ===\n\n";

// Get delivery records details with company_name
echo "=== ALL XT-PC1 DELIVERY RECORDS ===\n";
$result = $conn->query("SELECT id, item_code, item_name, quantity, company_name, status, created_at FROM delivery_records WHERE item_code='XT-PC1' ORDER BY id");
$total = 0;
$stock_addition_count = 0;
while($row = $result->fetch_assoc()) {
  $total++;
  $is_inventory = ($row['company_name'] === 'Stock Addition') ? '✓ INVENTORY' : 'NOT INVENTORY';
  if ($row['company_name'] === 'Stock Addition') {
    $stock_addition_count++;
  }
  echo "ID=" . $row['id'] . " | Qty=" . $row['quantity'] . " | Company='" . $row['company_name'] . "' | Status=" . $row['status'] . " | " . $is_inventory . "\n";
}
echo "\nTotal XT-PC1 records: $total\n";
echo "Records marked as 'Stock Addition' (inventory): $stock_addition_count\n";
echo "\nExpected by user: 4\n";
echo "Missing: " . (4 - $stock_addition_count) . " records\n";
?>

