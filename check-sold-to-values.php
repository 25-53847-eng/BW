<?php
require 'db_config.php';

echo "=== SOLD_TO VALUES (ALL, showing first 30) ===\n";
$result = $conn->query("
    SELECT DISTINCT sold_to, COUNT(*) as cnt, SUM(quantity) as qty
    FROM delivery_records 
    WHERE sold_to IS NOT NULL AND sold_to != '' AND TRIM(sold_to) != ''
    GROUP BY sold_to
    ORDER BY qty DESC
    LIMIT 30
");
while ($row = $result->fetch_assoc()) {
    echo "'{$row['sold_to']}' => {$row['cnt']} records, {$row['qty']} units\n";
}

echo "\n=== CHECK WHAT GETS FILTERED OUT BY REGEXP ===\n";
$result = $conn->query("
    SELECT DISTINCT sold_to
    FROM delivery_records 
    WHERE sold_to IS NOT NULL AND sold_to != '' AND TRIM(sold_to) != ''
    AND sold_to REGEXP '^[0-9]+$'
");
$numeric_count = 0;
while ($row = $result->fetch_assoc()) {
    echo "NUMERIC (filtered out): '{$row['sold_to']}'\n";
    $numeric_count++;
}
if ($numeric_count === 0) {
    echo "No numeric-only values found\n";
}

echo "\n=== VALID COMPANIES (NON-NUMERIC) ===\n";
$result = $conn->query("
    SELECT DISTINCT sold_to, COUNT(*) as cnt, SUM(quantity) as qty
    FROM delivery_records 
    WHERE sold_to IS NOT NULL AND sold_to != '' AND TRIM(sold_to) != ''
    AND sold_to NOT REGEXP '^[0-9]+$'
    GROUP BY sold_to
    ORDER BY qty DESC
");
while ($row = $result->fetch_assoc()) {
    echo "{$row['sold_to']} => {$row['qty']} units\n";
}
?>
