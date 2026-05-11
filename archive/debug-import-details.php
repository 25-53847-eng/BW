<?php
require_once 'db_config.php';

echo "=== Detailed import analysis ===\n\n";

// Check the company_name distribution
echo "=== Company Name Distribution ===\n";
$r = $conn->query("SELECT company_name, COUNT(*) as cnt FROM delivery_records WHERE dataset_name = '2024 to NOW BW Sales Record' GROUP BY company_name ORDER BY cnt DESC");
while ($row = $r->fetch_assoc()) {
    echo $row['company_name'] . ": " . $row['cnt'] . "\n";
}

// Check sold_to values
echo "\n=== Sold To Distribution ===\n";
$r = $conn->query("SELECT DISTINCT sold_to, COUNT(*) as cnt FROM delivery_records WHERE dataset_name = '2024 to NOW BW Sales Record' AND (sold_to IS NOT NULL AND sold_to != '') GROUP BY sold_to ORDER BY cnt DESC LIMIT 30");
$found_clients = $r->num_rows;
if ($found_clients === 0) {
    echo "No non-empty sold_to values found\n";
} else {
    while ($row = $r->fetch_assoc()) {
        echo "'" . $row['sold_to'] . "': " . $row['cnt'] . "\n";
    }
}

// Check inventory_marker
echo "\n=== Inventory Status (via inventory column) ===\n";
$r = $conn->query("SELECT DISTINCT inventory, COUNT(*) as cnt FROM delivery_records WHERE dataset_name = '2024 to NOW BW Sales Record' AND (inventory IS NOT NULL AND inventory != '') GROUP BY inventory ORDER BY cnt DESC");
while ($row = $r->fetch_assoc()) {
    echo "'" . $row['inventory'] . "': " . $row['cnt'] . "\n";
}

// Show one complete record to debug
echo "\n=== Sample Complete Record ===\n";
$r = $conn->query("SELECT * FROM delivery_records WHERE dataset_name = '2024 to NOW BW Sales Record' LIMIT 1");
$row = $r->fetch_assoc();
foreach ($row as $key => $value) {
    echo "$key: " . var_export($value, true) . "\n";
}
?>
