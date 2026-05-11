<?php
require_once 'db_config.php';

echo "=== All unique company_name values ===\n";
$result = $conn->query("SELECT DISTINCT company_name, COUNT(*) as cnt FROM delivery_records GROUP BY company_name ORDER BY cnt DESC");
$count = 0;
while ($row = $result->fetch_assoc() && $count < 30) {
    echo "'" . $row['company_name'] . "' | Count: " . $row['cnt'] . "\n";
    $count++;
}

// Check for Andison variations
echo "\n=== Looking for Andison variations ===\n";
$result = $conn->query("SELECT DISTINCT company_name FROM delivery_records WHERE company_name LIKE '%andison%' OR company_name LIKE '%Andison%' LIMIT 20");
while ($row = $result->fetch_assoc()) {
    echo "- '" . $row['company_name'] . "'\n";
}

$result = $conn->query("SELECT DISTINCT sold_to FROM delivery_records WHERE sold_to LIKE '%andison%' OR sold_to LIKE '%Andison%' LIMIT 20");
while ($row = $result->fetch_assoc()) {
    echo "- (sold_to) '" . $row['sold_to'] . "'\n";
}

// Count records
echo "\n=== Record counts ===\n";
$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records");
$row = $result->fetch_assoc();
echo "Total records: " . $row['cnt'] . "\n";
?>
