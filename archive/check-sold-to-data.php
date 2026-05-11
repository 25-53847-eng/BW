<?php
require_once 'db_config.php';

$dataset = '2024 to NOW BW Sales Record';

echo "=== Checking for actual client company names ===\n\n";

// Check if any records have the Seatrium company
$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE dataset_name = '$dataset' AND sold_to LIKE '%Seatrium%'");
$row = $result->fetch_assoc();
echo "Records with 'Seatrium' in sold_to: " . $row['cnt'] . "\n";

// Check unique sold_to values (limit to non-empty, non-default ones)
echo "\n=== Unique sold_to values ===\n";
$result = $conn->query("SELECT DISTINCT sold_to, COUNT(*) as cnt FROM delivery_records WHERE dataset_name = '$dataset' AND COALESCE(sold_to, '') NOT IN ('', '0', '5') GROUP BY sold_to ORDER BY cnt DESC LIMIT 20");
$count = $result->num_rows;
if ($count === 0) {
    echo "(no valid sold_to values found)\n";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "'" . $row['sold_to'] . "': " . $row['cnt'] . "\n";
    }
}

// Check if the dataset populated the sold_to correctly
echo "\n=== Checking sold_to field population ===\n";
$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE dataset_name = '$dataset'");
$total = $result->fetch_assoc()['cnt'];

$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE dataset_name = '$dataset' AND (sold_to IS NULL OR sold_to = '' OR sold_to = '0' OR sold_to = '5')");
$empty = $result->fetch_assoc()['cnt'];

echo "Total records: " . $total . "\n";
echo "Empty sold_to: " . $empty . "\n";
echo "Non-empty sold_to: " . ($total - $empty) . "\n";

// Sample some records to see what data was imported
echo "\n=== Sample records with non-empty sold_to ===\n";
$result = $conn->query("SELECT invoice_no, item_code, sold_to, company_name FROM delivery_records WHERE dataset_name = '$dataset' AND COALESCE(sold_to, '') NOT IN ('', '0', '5') LIMIT 5");
if ($result->num_rows === 0) {
    echo "(no records)\n";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "Invoice: " . $row['invoice_no'] . " | Item: " . $row['item_code'] . " | Sold To: " . $row['sold_to'] . " | Company: " . $row['company_name'] . "\n";
    }
}
?>
