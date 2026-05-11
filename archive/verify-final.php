<?php
require_once 'db_config.php';

// These are internal routing, not client companies
$internal_keywords = [
    'Andison Manila Use',
    'Warranty Replacement',
    'replaced to',
    'to Andison Manila'
];

echo "Checking remaining items...\n";
foreach ($internal_keywords as $keyword) {
    $result = $conn->query("SELECT COUNT(*) as count FROM delivery_records 
        WHERE sold_to LIKE '%$keyword%'");
    $row = $result->fetch_assoc();
    echo "$keyword: " . $row['count'] . " records\n";
}

echo "\n" . str_repeat("=", 50) . "\n";

// Real client companies count
$result = $conn->query("SELECT COUNT(DISTINCT company_name) as count FROM delivery_records 
    WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila', '')
    AND dataset_name = '2024 to NOW BW Sales Record'");
$row = $result->fetch_assoc();
echo "Real client companies: " . $row['count'] . "\n";

// Total unique sold_to values
$result = $conn->query("SELECT COUNT(DISTINCT sold_to) as count FROM delivery_records 
    WHERE dataset_name = '2024 to NOW BW Sales Record'");
$row = $result->fetch_assoc();
echo "Unique sold_to values: " . $row['count'] . "\n";

// Total unique company_name values
$result = $conn->query("SELECT COUNT(DISTINCT company_name) as count FROM delivery_records 
    WHERE dataset_name = '2024 to NOW BW Sales Record'");
$row = $result->fetch_assoc();
echo "Unique company_name values: " . $row['count'] . "\n";

// Total records
$result = $conn->query("SELECT COUNT(*) as count FROM delivery_records 
    WHERE dataset_name = '2024 to NOW BW Sales Record'");
$row = $result->fetch_assoc();
echo "Total records: " . $row['count'] . "\n";
?>
