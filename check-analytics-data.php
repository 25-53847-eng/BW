<?php
session_start();
$_SESSION['user_id'] = 1;

require_once 'db_config.php';

echo "=== CHECKING ANALYTICS DATA ===\n\n";

// Check years
$r = $conn->query('SELECT DISTINCT delivery_year FROM delivery_records WHERE delivery_year > 0 ORDER BY delivery_year DESC LIMIT 10');
echo "Available years:\n";
while ($row = $r->fetch_assoc()) {
    echo "  - " . $row['delivery_year'] . "\n";
}

// Check Andison-related sold_to values
echo "\nAndison-related sold_to values:\n";
$r2 = $conn->query("SELECT DISTINCT sold_to, COUNT(*) as cnt FROM delivery_records WHERE (company_name LIKE '%andison%' OR transferred_to LIKE '%andison%' OR sold_to LIKE '%andison%') AND sold_to IS NOT NULL GROUP BY sold_to");
while ($row = $r2->fetch_assoc()) {
    echo "  - '" . $row['sold_to'] . "' (count: " . $row['cnt'] . ")\n";
}

// Check company_name values for Andison
echo "\nAndison company_name values:\n";
$r3 = $conn->query("SELECT DISTINCT company_name, COUNT(*) as cnt FROM delivery_records WHERE company_name LIKE '%andison%' GROUP BY company_name");
while ($row = $r3->fetch_assoc()) {
    echo "  - '" . $row['company_name'] . "' (count: " . $row['cnt'] . ")\n";
}

// Check what's being queried in analytics (sold_to = 'Andison Industrial')
echo "\nRecords where sold_to = 'Andison Industrial':\n";
$r4 = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE sold_to = 'Andison Industrial'");
$row = $r4->fetch_assoc();
echo "  Count: " . $row['cnt'] . "\n";

// Check 2024 data for Andison
echo "\nAndison records in 2024:\n";
$r5 = $conn->query("SELECT COUNT(*) as cnt, SUM(quantity) as total_qty FROM delivery_records WHERE (company_name = 'to Andison Manila' OR transferred_to = 'to Andison Manila')");
$row = $r5->fetch_assoc();
echo "  Count: " . $row['cnt'] . ", Total Qty: " . $row['total_qty'] . "\n";

// Check monthly breakdown
echo "\nAndison monthly breakdown:\n";
$r6 = $conn->query("SELECT delivery_month, COUNT(*) as cnt, SUM(quantity) as total_qty FROM delivery_records WHERE company_name = 'to Andison Manila' GROUP BY delivery_month ORDER BY delivery_month");
while ($row = $r6->fetch_assoc()) {
    echo "  " . $row['delivery_month'] . ": count=" . $row['cnt'] . ", qty=" . $row['total_qty'] . "\n";
}
?>
