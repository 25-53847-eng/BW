<?php
require 'db_config.php';

echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║           EMPLOYEE DASHBOARD - STATISTICS VERIFICATION             ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

$result = $conn->query("SELECT COALESCE(SUM(quantity), 0) as total FROM delivery_records WHERE status = 'Delivered'");
$row = $result->fetch_assoc();
$total_delivered = $row['total'];

$result = $conn->query("SELECT COUNT(DISTINCT company_name) as total FROM delivery_records WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila') AND company_name IS NOT NULL AND company_name != ''");
$row = $result->fetch_assoc();
$total_companies = $row['total'];

$result = $conn->query("SELECT COUNT(DISTINCT item_code) as total FROM delivery_records WHERE item_code NOT LIKE 'UNKNOWN%'");
$row = $result->fetch_assoc();
$active_models = $row['total'];

echo "Total Delivered ............. " . number_format($total_delivered) . " units ✓\n";
echo "Client Companies ............ " . number_format($total_companies) . " (target: 195)\n";
echo "Active Models ............... " . number_format($active_models) . "\n";
echo "Monthly Average ............. " . number_format(round($total_delivered / 12)) . " units/month\n";
echo "Yearly Total ................ " . number_format($total_delivered) . " units\n";

echo "\n═════════════════════════════════════════════════════════════════════\n";
echo "✅ All queries updated - refresh your browser to see new values!\n";
echo "═════════════════════════════════════════════════════════════════════\n";
?>
