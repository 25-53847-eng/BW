<?php
require 'db_config.php';

echo "=== Checking dataset ===\n";
$dataset = '2024 to NOW BW Sales Record';
$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE dataset_name = '$dataset'");
$row = $result->fetch_assoc();
echo "Records in '$dataset': " . $row['cnt'] . "\n\n";

echo "=== Checking for UNIQUE constraints ===\n";
$keys = $conn->query('SHOW KEYS FROM delivery_records WHERE Key_name != "PRIMARY"');
while($row = $keys->fetch_assoc()) {
    if ($row['Non_unique'] == 0) {
        echo "UNIQUE: " . $row['Column_name'] . " (Seq: " . $row['Seq_in_index'] . ")\n";
    }
}

echo "\n=== Sample error test: Insert duplicate with same values ===\n";
$test_result = $conn->query("SELECT * FROM delivery_records WHERE dataset_name = '$dataset' LIMIT 1");
if ($row = $test_result->fetch_assoc()) {
    echo "Found sample row:\n";
    echo "  invoice_no: " . $row['invoice_no'] . "\n";
    echo "  item_code: " . $row['item_code'] . "\n";
    echo "  delivery_month: " . $row['delivery_month'] . "\n";
    echo "  delivery_day: " . $row['delivery_day'] . "\n";
    echo "  company_name: " . $row['company_name'] . "\n";
}
