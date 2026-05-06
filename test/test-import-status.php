<?php
require '../db_config.php';

$r = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records");
$row = $r->fetch_assoc();
echo "Total delivery_records: " . $row['cnt'] . "\n";

$r2 = $conn->query("SELECT DISTINCT company_name FROM delivery_records LIMIT 10");
echo "Company names:\n";
while ($row2 = $r2->fetch_assoc()) {
    echo "  - " . $row2['company_name'] . "\n";
}

$r3 = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')");
$row3 = $r3->fetch_assoc();
echo "Records walang sold_to: " . $row3['cnt'] . "\n";

$r4 = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE sold_to IS NOT NULL AND TRIM(COALESCE(sold_to, '')) != ''");
$row4 = $r4->fetch_assoc();
echo "Records WITH sold_to: " . $row4['cnt'] . "\n";
?>

