<?php
require 'db_config.php';

echo "═════════════════════════════════════════════════════════════════════\n";
echo "DASHBOARD COMPANY COUNT - FIXED\n";
echo "═════════════════════════════════════════════════════════════════════\n\n";

// All years
$result = $conn->query("SELECT COUNT(DISTINCT company_name) as total FROM delivery_records WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records') AND company_name IS NOT NULL AND company_name != ''");
$row = $result->fetch_assoc();
echo "All Years - Total Companies: " . $row['total'] . "\n";

// Year 2026
$result = $conn->query("SELECT COUNT(DISTINCT company_name) as total FROM delivery_records WHERE YEAR(delivery_date) = 2026 AND company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records') AND company_name IS NOT NULL AND company_name != ''");
$row = $result->fetch_assoc();
echo "Year 2026 - Total Companies: " . $row['total'] . "\n";

// Check if the entries like "Andison Manila Use", "Sales record not found" are now being counted
$result = $conn->query("SELECT company_name, COUNT(*) as cnt FROM delivery_records WHERE company_name IN ('Andison Manila Use', 'Sales record not found', 'to Andison Manila') GROUP BY company_name");
echo "\nSpecial entries now being counted:\n";
while ($row = $result->fetch_assoc()) {
    echo "  - {$row['company_name']}: {$row['cnt']} records\n";
}

echo "\n✅ Target: 173 unique companies (from your list that are in the database)\n";
echo "═════════════════════════════════════════════════════════════════════\n";
?>
