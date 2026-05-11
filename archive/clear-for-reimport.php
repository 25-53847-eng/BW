<?php
require_once 'db_config.php';

echo "Deleting incomplete import...\n";

// Delete all records from the 2024 to NOW dataset
$stmt = $conn->prepare("DELETE FROM delivery_records WHERE dataset_name = ?");
$stmt->bind_param('s', $dataset);
$dataset = '2024 to NOW BW Sales Record';
$stmt->execute();
$deleted = $stmt->affected_rows;
$stmt->close();

echo "Deleted: " . $deleted . " records\n";

// Verify deletion
$result = $conn->query("SELECT COUNT(*) as count FROM delivery_records WHERE dataset_name = '2024 to NOW BW Sales Record'");
$row = $result->fetch_assoc();
echo "Remaining records in dataset: " . $row['count'] . "\n";

echo "\nNow ready for fresh import...\n";
?>
