<?php
require 'db_config.php';

$owner_user_id = 1;
$result = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM delivery_records WHERE owner_user_id = {$owner_user_id}) as delivery_count,
        (SELECT COUNT(*) FROM warranty_replacements WHERE owner_user_id = {$owner_user_id}) as warranty_count
");

if($result) {
  $row = $result->fetch_assoc();
  echo "Delivery: " . $row['delivery_count'] . "\n";
  echo "Warranty: " . $row['warranty_count'] . "\n";
  echo "Total: " . ($row['delivery_count'] + $row['warranty_count']) . "\n";
} else {
  echo "Query Error: " . $conn->error . "\n";
}
?>