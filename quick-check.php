<?php
require_once 'db_config.php';

$r = $conn->query('SELECT COUNT(*) as cnt FROM delivery_records');
$row = $r->fetch_assoc();
echo "Total delivery_records: " . $row['cnt'] . "\n\n";

$r2 = $conn->query('SELECT DISTINCT dataset_name, COUNT(*) as cnt FROM delivery_records GROUP BY dataset_name');
if ($r2->num_rows > 0) {
    echo "By dataset:\n";
    while ($d = $r2->fetch_assoc()) {
        echo "  " . $d['dataset_name'] . ": " . $d['cnt'] . "\n";
    }
}

// Check for any Andison Industrial records
$r3 = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE company_name = 'Andison Industrial'");
$row3 = $r3->fetch_assoc();
echo "\nAndison Industrial count: " . $row3['cnt'] . "\n";

// Show sample client records
echo "\n=== Sample imported records ===\n";
$r4 = $conn->query("SELECT invoice_no, item_code, sold_to, company_name FROM delivery_records ORDER BY id DESC LIMIT 10");
while ($row4 = $r4->fetch_assoc()) {
    echo "Invoice: " . $row4['invoice_no'] . " | Item: " . $row4['item_code'] . " | Company: " . $row4['company_name'] . "\n";
}
?>
