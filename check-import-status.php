<?php
require_once 'db_config.php';

// Count records by company
$result = $conn->query("SELECT COUNT(*) as total FROM delivery_records WHERE dataset_name = '2024 to NOW BW Sales Record'");
$row = $result->fetch_assoc();
$current_total = $row['total'];

// Count client company records
$result2 = $conn->query("SELECT COUNT(*) as total FROM delivery_records 
    WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila', '')
    AND dataset_name = '2024 to NOW BW Sales Record'");
$row2 = $result2->fetch_assoc();
$client_total = $row2['total'];

// Count unique client companies
$result3 = $conn->query("SELECT COUNT(DISTINCT company_name) as total FROM delivery_records 
    WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila', '')
    AND dataset_name = '2024 to NOW BW Sales Record'");
$row3 = $result3->fetch_assoc();
$unique_companies = $row3['total'];

echo "Current Database Status:\n";
echo "========================\n";
echo "Total records in dataset: " . $current_total . "\n";
echo "Client company records: " . $client_total . "\n";
echo "Unique client companies: " . $unique_companies . "\n";
echo "Expected companies: 196\n";
echo "Missing companies: " . (196 - $unique_companies) . "\n";

// Check if some companies have only 1 record
echo "\nCompanies with only 1 record:\n";
$result4 = $conn->query("SELECT company_name, COUNT(*) as count FROM delivery_records 
    WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila', '')
    AND dataset_name = '2024 to NOW BW Sales Record'
    GROUP BY company_name
    HAVING count = 1
    ORDER BY company_name");
$single_count = 0;
while ($row = $result4->fetch_assoc()) {
    if ($single_count < 10) {
        echo "  - " . $row['company_name'] . "\n";
    }
    $single_count++;
}
echo "  ... and " . ($single_count - 10) . " more\n";
echo "Total with only 1 record: " . $single_count . "\n";
?>
