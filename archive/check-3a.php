<?php
require_once 'db_config.php';
$result = $conn->query("SELECT DISTINCT unit_type FROM delivery_records ORDER BY unit_type ASC");
echo "All unit types in delivery_records:\n";
while ($row = $result->fetch_assoc()) {
    echo "  - " . $row['unit_type'] . "\n";
}
$count = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE unit_type = '3A'")->fetch_assoc();
echo "\n3A record count: " . $count['cnt'] . "\n";
?>
