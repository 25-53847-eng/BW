<?php
require 'db_config.php';
require 'helpers/client-helper.php';

$dataset_filter = ' AND dataset_name = ?';
$dataset_filter_params = ['2024 to NOW BW Sales Record'];

$count = countClientCompanies($conn, $dataset_filter, $dataset_filter_params);
echo "Client companies count (with 'to Andison Manila'): $count\n";

// Also show count without the dataset filter
$all_count = countClientCompanies($conn);
echo "All client companies: $all_count\n";

// Show the difference
echo "\nBreakdown:\n";
$sql = "SELECT COUNT(*) as cnt FROM delivery_records 
        WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila')
        AND company_name IS NOT NULL AND company_name != ''
        AND dataset_name = '2024 to NOW BW Sales Record'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "Valid client companies (this dataset): " . $row['cnt'] . "\n";
?>
