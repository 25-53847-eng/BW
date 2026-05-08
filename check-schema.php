<?php
require 'db_config.php';

echo "=== DELIVERY_RECORDS SCHEMA ===\n";
$result = $conn->query('DESCRIBE delivery_records');
while($row = $result->fetch_assoc()) {
    $null = $row['Null'] === 'YES' ? 'NULL' : 'NOT NULL';
    $default = $row['Default'] ? ' DEFAULT ' . $row['Default'] : '';
    echo $row['Field'] . " | " . $row['Type'] . " | " . $null . $default . "\n";
}

echo "\n=== KEY INFO ===\n";
$keys = $conn->query('SHOW KEYS FROM delivery_records');
while($row = $keys->fetch_assoc()) {
    echo $row['Key_name'] . ": " . $row['Column_name'] . "\n";
}
