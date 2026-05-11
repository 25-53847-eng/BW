<?php
require_once 'db_config.php';

// Get more details about the delivery_records structure
echo "=== Delivery Records Table Structure ===\n";
$result = $conn->query("DESCRIBE delivery_records");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}

echo "\n=== All unique sold_to values (raw) ===\n";
$result = $conn->query("SELECT DISTINCT sold_to, COUNT(*) as cnt FROM delivery_records GROUP BY sold_to ORDER BY cnt DESC");
while ($row = $result->fetch_assoc()) {
    echo "Value: '" . var_export($row['sold_to'], true) . "' | Count: " . $row['cnt'] . "\n";
}

echo "\n=== Sample records from delivery_records ===\n";
$result = $conn->query("SELECT id, sold_to, company_name, transferred_to FROM delivery_records LIMIT 5");
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . " | sold_to: '" . $row['sold_to'] . "' | company_name: '" . $row['company_name'] . "' | transferred_to: '" . $row['transferred_to'] . "'\n";
}
?>
