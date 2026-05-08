<?php
require '../db_config.php';

echo "=== TABLE COLLATIONS ===\n";
$result = $conn->query("SELECT TABLE_NAME, TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE()");
while($row = $result->fetch_assoc()) {
    echo $row['TABLE_NAME'] . ': ' . $row['TABLE_COLLATION'] . "\n";
}

echo "\n=== COLUMN COLLATIONS (delivery_records) ===\n";
$cols = $conn->query("SELECT COLUMN_NAME, COLLATION_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'delivery_records' AND TABLE_SCHEMA = DATABASE() ORDER BY ORDINAL_POSITION");
while($col = $cols->fetch_assoc()) {
    if($col['COLLATION_NAME']) {
        echo $col['COLUMN_NAME'] . ': ' . $col['COLLATION_NAME'] . "\n";
    }
}

$conn->close();
?>

