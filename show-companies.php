<?php
require_once 'db_config.php';
require_once 'helpers/client-helper.php';

$companies = getClientCompaniesWithStats($conn, ' AND dataset_name = ?', ['2024 to NOW BW Sales Record']);
echo "Total companies found: " . count($companies) . "\n\n";
echo "First 20 companies:\n";
for ($i = 0; $i < min(20, count($companies)); $i++) {
    echo ($i+1) . ". " . $companies[$i]['company_name'] . " - " . $companies[$i]['total_units'] . " units, " . $companies[$i]['total_orders'] . " orders\n";
}
?>
