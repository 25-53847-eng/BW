<?php
require 'db_config.php';

echo "=== SOLD_TO VALUES (ALL) ===\n";
$result = $conn->query("
    SELECT sold_to, COUNT(*) as cnt, SUM(quantity) as total_qty
    FROM delivery_records 
    GROUP BY sold_to
    ORDER BY cnt DESC
    LIMIT 30
");
while ($row = $result->fetch_assoc()) {
    $show = $row['sold_to'] ?? 'NULL';
    $is_numeric = preg_match('/^[0-9]+$/', $show) ? 'YES' : 'NO';
    echo "'{$show}' (numeric={$is_numeric}) => {$row['cnt']} records, {$row['total_qty']} units\n";
}

echo "\n=== TOTAL RECORDS ===\n";
$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records");
$row = $result->fetch_assoc();
echo "Total: {$row['cnt']} records\n";

echo "\n=== NON-NUMERIC SOLD_TO ===\n";
$result = $conn->query("
    SELECT sold_to, COUNT(*) as cnt, SUM(quantity) as total_qty
    FROM delivery_records 
    WHERE sold_to IS NOT NULL AND sold_to != '' AND TRIM(sold_to) != ''
    AND sold_to NOT REGEXP '^[0-9]+$'
    GROUP BY sold_to
    ORDER BY total_qty DESC
");
while ($row = $result->fetch_assoc()) {
    echo "'{$row['sold_to']}' => {$row['cnt']} records, {$row['total_qty']} units\n";
}
?>
