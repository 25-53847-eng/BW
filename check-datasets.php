<?php
require 'db_config.php';

echo "=== ACTUAL DATASET NAMES IN DELIVERY_RECORDS ===\n";
$result = $conn->query("SELECT DISTINCT dataset_name, COUNT(*) as cnt FROM delivery_records GROUP BY dataset_name");
while ($row = $result->fetch_assoc()) {
    $name = $row['dataset_name'] ?? 'NULL';
    echo "'{$name}' => {$row['cnt']} records\n";
}

echo "\n=== WITH SOLD_TO NOT EMPTY ===\n";
$result = $conn->query("
    SELECT DISTINCT dataset_name, COUNT(*) as cnt 
    FROM delivery_records 
    WHERE sold_to IS NOT NULL AND sold_to != '' AND TRIM(sold_to) != ''
    GROUP BY dataset_name
");
while ($row = $result->fetch_assoc()) {
    $name = $row['dataset_name'] ?? 'NULL';
    echo "'{$name}' => {$row['cnt']} records\n";
}

echo "\n=== TOP COMPANIES BY SOLD_TO (NO DATASET FILTER) ===\n";
$result = $conn->query("
    SELECT sold_to, COUNT(*) as cnt, SUM(quantity) as qty
    FROM delivery_records 
    WHERE sold_to IS NOT NULL AND sold_to != '' AND TRIM(sold_to) != ''
    AND sold_to NOT REGEXP '^[0-9]+$'
    GROUP BY sold_to
    ORDER BY qty DESC
    LIMIT 15
");
while ($row = $result->fetch_assoc()) {
    echo "{$row['sold_to']} => {$row['qty']} units\n";
}
?>
