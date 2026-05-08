<?php
require_once __DIR__ . '/../db_config.php';

// Delete ALL Andison Industrial records
$sql = "DELETE FROM delivery_records WHERE company_name = 'Andison Industrial' OR company_name = 'to Andison Manila'";
$result = $conn->query($sql);

if ($result) {
    echo "✅ Deleted " . $conn->affected_rows . " Andison records\n";
} else {
    echo "❌ Error: " . $conn->error;
}

// Show remaining top companies
echo "\n=== Remaining Top Companies ===\n";
$sql2 = "
    SELECT company_name, COUNT(*) as count, SUM(quantity) as total_qty
    FROM delivery_records
    WHERE company_name != 'Stock Addition'
    GROUP BY company_name
    ORDER BY total_qty DESC
    LIMIT 15
";
$result2 = $conn->query($sql2);
while ($row = $result2->fetch_assoc()) {
    echo "{$row['company_name']}: {$row['count']} records, {$row['total_qty']} units\n";
}
?>
