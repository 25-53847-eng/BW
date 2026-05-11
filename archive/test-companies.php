<?php
require_once 'db_config.php';

// Check what companies are in delivery_records
$sql = "SELECT DISTINCT company_name FROM delivery_records 
        WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila', '') 
        ORDER BY company_name LIMIT 30";

$result = $conn->query($sql);

echo "Companies in delivery_records:\n";
while ($row = $result->fetch_assoc()) {
    echo "  - " . $row['company_name'] . "\n";
}

// Check counts
$result2 = $conn->query("SELECT COUNT(DISTINCT company_name) as count FROM delivery_records WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila', '')");
$count_row = $result2->fetch_assoc();
echo "\nTotal distinct companies: " . $count_row['count'] . "\n";

// Check what getClientCompaniesWithStats returns
require_once 'helpers/client-helper.php';
$companies = getClientCompaniesWithStats($conn, " AND dataset_name = ?", ['2024 to NOW BW Sales Record']);
echo "\nCompanies from getClientCompaniesWithStats():\n";
foreach ($companies as $c) {
    echo "  - " . $c['company_name'] . " (" . $c['total_orders'] . " orders)\n";
}
?>
