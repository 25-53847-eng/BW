<?php
session_start();
$_SESSION['user_id'] = 14;
require '../db_config.php';
require '../helpers/client-helper.php';

// Exact same params as index.php
$dataset_filter = ' AND company_name != ?';
$dataset_filter_params = ['Stock Addition'];

// Test countClientCompanies function
$count = countClientCompanies($conn, $dataset_filter, $dataset_filter_params);
echo "Dashboard (countClientCompanies): " . $count . "\n";

// Get all companies to count them
$companies = getClientCompaniesWithStats($conn, $dataset_filter, $dataset_filter_params);
echo "Client Companies page (count): " . count($companies) . "\n";

// Raw SQL count
$clientCompanyExpr = getClientCompanyExpr();
$sql = "SELECT COUNT(DISTINCT {$clientCompanyExpr}) as total 
        FROM delivery_records 
        WHERE 1=1" . $dataset_filter;
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $dataset_filter_params[0]);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo "Raw SQL distinct count: " . $row['total'] . "\n";
    }
    $stmt->close();
}

// Check if there's a NULL company
$sql = "SELECT COUNT(DISTINCT {$clientCompanyExpr}) as total 
        FROM delivery_records 
        WHERE {$clientCompanyExpr} IS NULL" . $dataset_filter;
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $dataset_filter_params[0]);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo "NULL company rows: " . $row['total'] . "\n";
    }
    $stmt->close();
}
?>

