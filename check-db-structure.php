<?php
require 'db_config.php';

echo "=== APPROVED_COMPANIES COLUMNS ===\n";
$result = $conn->query("DESCRIBE approved_companies");
while ($row = $result->fetch_assoc()) {
    echo "- {$row['Field']}\n";
}

echo "\n=== APPROVED_COMPANIES DATA (Sample) ===\n";
$result = $conn->query("SELECT * FROM approved_companies LIMIT 10");
while ($row = $result->fetch_assoc()) {
    echo "- {$row['company_name']}\n";
}

echo "\n=== DELIVERY_RECORDS COUNT BY DATASET ===\n";
$result = $conn->query("SELECT dataset_name, COUNT(*) as cnt FROM delivery_records GROUP BY dataset_name");
while ($row = $result->fetch_assoc()) {
    echo "'{$row['dataset_name']}' => {$row['cnt']} records\n";
}

echo "\n=== ALL DELIVERY_RECORDS COUNT ===\n";
$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records");
$row = $result->fetch_assoc();
echo "Total: {$row['cnt']}\n";
?>
