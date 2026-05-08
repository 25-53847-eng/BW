<?php
require_once 'db_config.php';

echo "=== CHECKING FOR COMPLETE ROW DUPLICATES ===\n\n";

// Check for rows with identical key fields
$sql = "
SELECT 
    invoice_no, item_code, serial_no, quantity, company_name, sold_to,
    COUNT(*) as duplicate_count,
    GROUP_CONCAT(id ORDER BY id) as ids
FROM warranty_replacements
GROUP BY invoice_no, item_code, serial_no, quantity, company_name, sold_to
HAVING COUNT(*) > 1
ORDER BY duplicate_count DESC
LIMIT 20
";

$result = $conn->query($sql);
$total_duplicates = 0;

echo "Complete row duplicates found:\n";
$has_duplicates = false;
while ($row = $result->fetch_assoc()) {
    $has_duplicates = true;
    $count = intval($row['duplicate_count']);
    $total_duplicates += $count - 1;
    echo "  Count: " . $count . " | IDs: " . $row['ids'] . "\n";
    echo "    Invoice: " . ($row['invoice_no'] ?? 'NULL') . " | Item: " . ($row['item_code'] ?? 'NULL') . "\n";
    echo "    QTY: " . $row['quantity'] . " | Company: " . ($row['company_name'] ?? 'NULL') . "\n\n";
}

if (!$has_duplicates) {
    echo "No complete row duplicates found.\n";
} else {
    echo "\nTotal complete duplicates to remove: " . $total_duplicates . "\n";
}

echo "\n=== CHECKING IF DATA WAS IMPORTED MULTIPLE TIMES ===\n";

// Check by dataset
$sql_datasets = "
SELECT 
    dataset_name,
    COUNT(*) as count,
    COALESCE(SUM(quantity), 0) as total_qty
FROM warranty_replacements
GROUP BY dataset_name
";

$result_datasets = $conn->query($sql_datasets);
echo "\nBy Dataset:\n";
while ($row = $result_datasets->fetch_assoc()) {
    echo "  " . ($row['dataset_name'] ?? 'NULL/NO DATASET') . ": " . number_format($row['count']) . " items (" . number_format($row['total_qty']) . " units)\n";
}

$conn->close();
?>
