<?php
session_start();
$_SESSION['user_id'] = 14; // Set admin user
require '../db_config.php';
require '../helpers/client-helper.php';

// Test 1: Dashboard query (from countClientCompanies)
$dataset_filter = ' AND company_name != ?';
$dataset_filter_params = ['Stock Addition'];

echo "=== DASHBOARD COUNT (countClientCompanies) ===\n";
$count = countClientCompanies($conn, $dataset_filter, $dataset_filter_params);
echo "Count: " . $count . "\n\n";

// Test 2: Client Companies page query (from getClientCompaniesWithStats)
echo "=== CLIENT COMPANIES PAGE COUNT (getClientCompaniesWithStats) ===\n";
$companies = getClientCompaniesWithStats($conn, $dataset_filter, $dataset_filter_params);
echo "Count: " . count($companies) . "\n\n";

// Test 3: Raw SQL to see actual distinct values
echo "=== RAW DISTINCT COUNT ===\n";
$clientCompanyExpr = getClientCompanyExpr();
$sql = "SELECT COUNT(DISTINCT {$clientCompanyExpr}) as total FROM delivery_records WHERE 1=1" . $dataset_filter;
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", ...$dataset_filter_params);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo "Raw count: " . $row['total'] . "\n";
    }
    $stmt->close();
}

// Test 4: Check what the actual distinct values are
echo "\n=== FIRST 10 UNIQUE COMPANIES ===\n";
$sql = "SELECT DISTINCT {$clientCompanyExpr} as company_name FROM delivery_records WHERE {$clientCompanyExpr} IS NOT NULL" . $dataset_filter . " LIMIT 10";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", ...$dataset_filter_params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        echo "- " . ($row['company_name'] ?? 'NULL') . "\n";
    }
    $stmt->close();
}

// Test 5: Count without the Stock Addition filter
echo "\n=== WITHOUT STOCK ADDITION FILTER ===\n";
$sql = "SELECT COUNT(DISTINCT {$clientCompanyExpr}) as total FROM delivery_records WHERE {$clientCompanyExpr} IS NOT NULL";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo "Count (no filter): " . $row['total'] . "\n";
    }
    $stmt->close();
}
?>

