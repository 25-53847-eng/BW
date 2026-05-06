<?php
require '../db_config.php';

$r = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE company_name = 'Stock Addition' AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')");
$row = $r->fetch_assoc();
echo "Stock Addition walang sold_to: " . $row['cnt'] . "\n";

$r2 = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE company_name = 'Stock Addition' AND sold_to IS NOT NULL AND TRIM(COALESCE(sold_to, '')) != ''");
$row2 = $r2->fetch_assoc();
echo "Stock Addition WITH sold_to: " . $row2['cnt'] . "\n";

$r3 = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE company_name = 'Stock Addition'");
$row3 = $r3->fetch_assoc();
echo "Total Stock Addition: " . $row3['cnt'] . "\n";
?>

