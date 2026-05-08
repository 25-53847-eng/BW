<?php
require_once __DIR__ . '/../db_config.php';

// Check if Andison records still exist
$sql = "SELECT COUNT(*) as count FROM delivery_records WHERE company_name LIKE '%Andison%'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();

echo "Andison records in database: " . $row['count'] . "\n";

// Show details
$sql2 = "SELECT company_name, COUNT(*) as count, SUM(quantity) as total_qty FROM delivery_records WHERE company_name LIKE '%Andison%' GROUP BY company_name";
$result2 = $conn->query($sql2);

echo "\nDetails:\n";
while ($row = $result2->fetch_assoc()) {
    echo "- {$row['company_name']}: {$row['count']} records, {$row['total_qty']} units\n";
}

// Show ALL top companies
echo "\n=== TOP 15 COMPANIES ===\n";
$sql3 = "
    SELECT company_name, COUNT(*) as count, SUM(quantity) as total_qty
    FROM delivery_records
    WHERE company_name != 'Stock Addition'
    GROUP BY company_name
    ORDER BY total_qty DESC
    LIMIT 15
";
$result3 = $conn->query($sql3);
while ($row = $result3->fetch_assoc()) {
    echo "{$row['company_name']}: {$row['total_qty']} units\n";
}
?>
