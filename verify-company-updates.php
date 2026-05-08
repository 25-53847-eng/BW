<?php
require 'db_config.php';

// Check if Andison Manila Use already exists
$sql = "SELECT COUNT(*) as cnt FROM delivery_records WHERE TRIM(company_name) = 'Andison Manila Use'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

if ($row['cnt'] > 0) {
    echo "✓ Andison Manila Use already exists in database (" . $row['cnt'] . " records)\n";
} else {
    echo "✗ Andison Manila Use NOT found in database - will need to be manually added\n";
    echo "\nTo add it, you need:\n";
    echo "1. Find records in the Excel that belong to 'Andison Manila Use'\n";
    echo "2. Import them to delivery_records\n";
    echo "3. Or create a placeholder entry with 0 quantity to represent it\n";
}

// Show current count
$sql = "SELECT COUNT(DISTINCT company_name) as cnt FROM delivery_records 
        WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila')
        AND company_name IS NOT NULL AND company_name != ''";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo "\n\nCurrent unique client companies (after filter): " . $row['cnt'] . "\n";

// Show companies that include "Andison"
echo "\n\nCompanies with 'Andison' in name:\n";
$sql = "SELECT DISTINCT company_name, COUNT(*) as cnt FROM delivery_records 
        WHERE company_name LIKE '%Andison%'
        GROUP BY company_name";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['company_name'] . " (" . $row['cnt'] . " records)\n";
}

// Show to Andison Manila count
echo "\n\nCurrent 'to Andison Manila' count:\n";
$sql = "SELECT COUNT(*) as cnt FROM delivery_records WHERE company_name = 'to Andison Manila'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "- 'to Andison Manila': " . $row['cnt'] . " records\n";

if ($row['cnt'] > 0) {
    echo "\n✓ Including 'to Andison Manila' will add " . $row['cnt'] . " more unique company entries\n";
}
?>
