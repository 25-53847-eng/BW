<?php
require_once '../db_config.php';

// Check all records without user filter
$query = "
SELECT 
    id, owner_user_id, invoice_no, serial_no, quantity, item_code, created_at
FROM delivery_records
ORDER BY created_at DESC
LIMIT 20
";

$result = $conn->query($query);

echo "<pre style='background: #222; color: #0f0; padding: 20px; font-family: monospace; font-size: 12px;'>\n";
echo "=== ALL DELIVERY RECORDS (No User Filter) ===\n\n";

if ($result && $result->num_rows > 0) {
    printf("%-6s | %-12s | %-15s | %-20s | %-5s | %-10s\n", "ID", "Owner", "Invoice", "Serial", "Qty", "Item");
    echo str_repeat("-", 85) . "\n";
    while ($row = $result->fetch_assoc()) {
        printf("%-6d | %-12d | %-15s | %-20s | %-5d | %-10s\n", 
            $row['id'], 
            $row['owner_user_id'],
            substr($row['invoice_no'], 0, 15),
            ($row['serial_no'] ?: 'EMPTY'),
            $row['quantity'],
            $row['item_code']
        );
    }
} else {
    echo "No records found in delivery_records table.\n";
}

echo "\n\nTotal records in table: ";
$countResult = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records");
if ($countResult) {
    $countRow = $countResult->fetch_assoc();
    echo $countRow['cnt'] . "\n";
}

echo "</pre>\n";
?>

