<?php
require_once __DIR__ . '/../db_config.php';

// DELETE all Andison records permanently
$queries = [
    "DELETE FROM delivery_records WHERE company_name = 'Andison Industrial'",
    "DELETE FROM delivery_records WHERE company_name = 'to Andison Manila'",
    "DELETE FROM delivery_records WHERE company_name LIKE '%Andison%'"
];

foreach ($queries as $sql) {
    $result = $conn->query($sql);
    if ($result) {
        echo "Deleted: " . $conn->affected_rows . " records\n";
    } else {
        echo "Error: " . $conn->error . "\n";
    }
}

// Verify they're gone
echo "\n✅ Verification:\n";
$verify = $conn->query("SELECT COUNT(*) as count FROM delivery_records WHERE company_name LIKE '%Andison%'");
$row = $verify->fetch_assoc();
echo "Andison records remaining: " . $row['count'] . "\n";

// Show new top companies
echo "\n=== TOP 15 COMPANIES NOW ===\n";
$sql = "
    SELECT company_name, COUNT(*) as count, SUM(quantity) as total_qty
    FROM delivery_records
    WHERE company_name != 'Stock Addition'
    GROUP BY company_name
    ORDER BY total_qty DESC
    LIMIT 15
";
$result = $conn->query($sql);
$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo "$count. {$row['company_name']}: {$row['total_qty']} units\n";
}
?>
