<?php
require 'db_config.php';

// Check table structure
echo "=== TABLE STRUCTURE ===\n";
$result = $conn->query('DESCRIBE delivery_records');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . $row['Null'] . "\n";
}

echo "\n=== SAMPLE DATA ===\n";
$result = $conn->query('SELECT id, invoice_no, unit_type, item_code FROM delivery_records LIMIT 5');
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']} | Invoice: {$row['invoice_no']} | Unit Type: {$row['unit_type']} | Item: {$row['item_code']}\n";
}

echo "\n=== UNIT TYPE VALUES COUNT ===\n";
$result = $conn->query('SELECT unit_type, COUNT(*) as cnt FROM delivery_records WHERE unit_type IS NOT NULL AND unit_type != "" GROUP BY unit_type');
while ($row = $result->fetch_assoc()) {
    echo "'{$row['unit_type']}' => {$row['cnt']} records\n";
}

echo "\n=== NULL/EMPTY UNIT TYPE COUNT ===\n";
$result = $conn->query('SELECT COUNT(*) as cnt FROM delivery_records WHERE unit_type IS NULL OR unit_type = ""');
$row = $result->fetch_assoc();
echo "NULL or EMPTY: {$row['cnt']} records\n";
?>
