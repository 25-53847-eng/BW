<?php
require 'db_config.php';

// Check what the actual case expression returns
$clientCompanyExpr = "NULLIF(TRIM(CASE
                WHEN company_name IS NOT NULL AND company_name != '' AND company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila') THEN company_name
                ELSE ''
            END), '')";

// Count using the exact expression
$sql = "SELECT COUNT(DISTINCT {$clientCompanyExpr}) as total 
        FROM delivery_records 
        WHERE {$clientCompanyExpr} IS NOT NULL
        AND dataset_name = '2024 to NOW BW Sales Record'";

$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "Count using expression: " . $row['total'] . "\n";

// Now check if 'to Andison Manila' is being counted
$sql = "SELECT {$clientCompanyExpr} as company_name 
        FROM delivery_records 
        WHERE company_name = 'to Andison Manila'
        AND dataset_name = '2024 to NOW BW Sales Record'
        LIMIT 1";

$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "\nWhat does the expression return for 'to Andison Manila'?\n";
echo "Result: '" . ($row['company_name'] ?? 'NULL') . "'\n";

// Check if 'to Andison Manila' appears in the distinct list
$sql = "SELECT DISTINCT {$clientCompanyExpr} as company_name 
        FROM delivery_records 
        WHERE {$clientCompanyExpr} IS NOT NULL
        AND dataset_name = '2024 to NOW BW Sales Record'
        ORDER BY company_name
        LIMIT 190";

$result = $conn->query($sql);
$companies = [];
while ($row = $result->fetch_assoc()) {
    $companies[] = $row['company_name'];
}

echo "\nChecking if 'to Andison Manila' is in the list:\n";
if (in_array('to Andison Manila', $companies)) {
    echo "✓ YES - found 'to Andison Manila'\n";
} else {
    echo "✗ NO - 'to Andison Manila' not in the list\n";
}

// Show last few companies
echo "\nLast 10 companies in list:\n";
$lastCompanies = array_slice($companies, -10);
foreach ($lastCompanies as $c) {
    echo "- " . $c . "\n";
}

echo "\nTotal companies found: " . count($companies) . "\n";
?>
