<?php
require 'db_config.php';

// Get all companies currently in database (with new filter)
$result = $conn->query("SELECT DISTINCT company_name FROM delivery_records WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records') AND company_name IS NOT NULL AND company_name != '' ORDER BY company_name");

$db_companies = [];
while ($row = $result->fetch_assoc()) {
    $db_companies[] = $row['company_name'];
}

echo "Companies in database (" . count($db_companies) . "):\n";
foreach ($db_companies as $company) {
    echo "  - $company\n";
}
?>
