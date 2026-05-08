<?php
require_once __DIR__ . '/../db_config.php';

// Check what values are in different fields that might contain "INVENTORY"
$fields = ['delivery_month', 'groupings', 'status', 'company_name'];

foreach ($fields as $field) {
    echo "=== Checking '$field' column ===\n";
    $sql = "SELECT DISTINCT $field FROM delivery_records WHERE $field LIKE '%INVENTORY%' OR $field LIKE '%Stock%' LIMIT 10";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "- " . $row[$field] . "\n";
        }
    } else {
        echo "No 'INVENTORY' or 'Stock' values found\n";
    }
}

// Also check for "Inventory" values
echo "\n=== Checking for 'Inventory' (capitalized) ===\n";
$sql = "SELECT DISTINCT delivery_month FROM delivery_records WHERE delivery_month LIKE '%nventory%' LIMIT 10";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    echo "Found in delivery_month:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['delivery_month'] . "\n";
    }
}
?>
