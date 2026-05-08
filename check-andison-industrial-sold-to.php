<?php
require_once 'db_config.php';

echo "=== Current Sold_To Values in Database ===\n\n";
$result = $conn->query("SELECT DISTINCT sold_to FROM delivery_records ORDER BY sold_to ASC");
while ($row = $result->fetch_assoc()) {
    echo "- " . ($row['sold_to'] ?? 'NULL') . "\n";
}

$count_result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE LOWER(TRIM(COALESCE(sold_to, ''))) = 'andison industrial'");
$cnt = $count_result->fetch_assoc();
echo "\n\n=== Records with 'Andison Industrial' in sold_to ===\n";
echo "Count: " . $cnt['cnt'] . "\n";

// Show sample records
echo "\n=== Sample Records with Andison Industrial ===\n";
$result = $conn->query("SELECT id, invoice_no, item_code, item_name, sold_to FROM delivery_records WHERE LOWER(TRIM(COALESCE(sold_to, ''))) = 'andison industrial' LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | Invoice: " . $row['invoice_no'] . " | Item: " . $row['item_code'] . " | Sold To: " . $row['sold_to'] . "\n";
}
?>
