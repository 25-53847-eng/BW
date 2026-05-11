<?php
require 'db_config.php';

$result = $conn->query('SELECT COUNT(*) as total FROM inventory');
$row = $result->fetch_assoc();
echo 'Total inventory items in database: ' . $row['total'] . "\n";

$result = $conn->query('SELECT * FROM inventory LIMIT 5');
echo "\nSample items:\n";
while ($row = $result->fetch_assoc()) {
    echo '- ' . $row['item_code'] . ': ' . $row['item_name'] . ' (Qty: ' . $row['quantity'] . ')' . "\n";
}

$conn->close();
?>
