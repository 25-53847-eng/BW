<?php
require_once 'db_config.php';

// Get ALL unique company names in database
$result = $conn->query("SELECT company_name, COUNT(*) as count FROM delivery_records 
    WHERE dataset_name = '2024 to NOW BW Sales Record'
    GROUP BY company_name
    ORDER BY count DESC");

$all_companies = [];
while ($row = $result->fetch_assoc()) {
    $all_companies[$row['company_name']] = $row['count'];
}

echo "All unique company_name values in database:\n";
echo "Total unique values: " . count($all_companies) . "\n\n";

foreach ($all_companies as $name => $count) {
    // Check if it would be filtered
    $filtered_out = in_array($name, ['Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila', '']) ? " [FILTERED]" : "";
    echo "  '$name': $count records" . $filtered_out . "\n";
}

// Count visible (not filtered)
$visible = [];
foreach ($all_companies as $name => $count) {
    if (!in_array($name, ['Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila', ''])) {
        $visible[$name] = $count;
    }
}

echo "\nVisible on page: " . count($visible) . "\n";
?>
