<?php
require 'db_config.php';

echo "=== COMPANY_NAME VALUES (Top 30) ===\n";
$result = $conn->query("
    SELECT DISTINCT company_name, COUNT(*) as cnt, SUM(quantity) as qty
    FROM delivery_records 
    WHERE company_name IS NOT NULL AND company_name != '' AND company_name != 'Stock Addition'
    GROUP BY company_name
    ORDER BY qty DESC
    LIMIT 30
");
while ($row = $result->fetch_assoc()) {
    echo "{$row['company_name']} => {$row['cnt']} records, {$row['qty']} units\n";
}
?>
