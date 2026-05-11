<?php
require_once 'db_config.php';

echo "=== Deleting bad import data ===\n\n";

// Delete all records from the dataset
$dataset = '2024 to NOW BW Sales Record';
$result = $conn->query("DELETE FROM delivery_records WHERE dataset_name = '$dataset'");
$affected = $conn->affected_rows;

echo "✅ Deleted $affected records from dataset: $dataset\n\n";

// Verify
$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records");
$row = $result->fetch_assoc();
echo "Total delivery_records remaining: " . $row['cnt'] . "\n";
?>
