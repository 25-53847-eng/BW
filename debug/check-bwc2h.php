<?php
require '../db_config.php';

$result = $conn->query("SELECT id, item_code, company_name, sold_to, created_at FROM delivery_records WHERE item_code = 'BWC2-H' ORDER BY created_at DESC LIMIT 10");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Code: " . $row['item_code'] . " | Company: " . $row['company_name'] . " | Sold_to: " . ($row['sold_to'] ? $row['sold_to'] : 'NULL/EMPTY') . " | Created: " . $row['created_at'] . "\n";
    }
} else {
    echo "Query error: " . $conn->error;
}
?>

