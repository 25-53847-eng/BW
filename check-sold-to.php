<?php
require 'db_config.php';

echo "=== SOLD_TO VALUES (Non-empty) ===\n";
$result = $conn->query("
    SELECT sold_to, COUNT(*) as cnt 
    FROM delivery_records 
    WHERE sold_to IS NOT NULL AND sold_to != '' AND TRIM(sold_to) != ''
    GROUP BY sold_to
    ORDER BY cnt DESC
    LIMIT 20
");
while ($row = $result->fetch_assoc()) {
    echo "'{$row['sold_to']}' => {$row['cnt']} records\n";
}

echo "\n=== SOLD_TO VALUES (All, with length) ===\n";
$result = $conn->query("
    SELECT sold_to, LENGTH(sold_to) as len, COUNT(*) as cnt 
    FROM delivery_records 
    GROUP BY sold_to
    ORDER BY cnt DESC
    LIMIT 20
");
while ($row = $result->fetch_assoc()) {
    $show = $row['sold_to'] ?? 'NULL';
    echo "'{$show}' (len={$row['len']}) => {$row['cnt']} records\n";
}
?>
