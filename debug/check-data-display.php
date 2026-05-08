<?php
session_start();
require_once '../db_config.php';

$user_id = intval($_SESSION['user_id'] ?? 1);

// Check actual data
$query = "
SELECT 
    id, invoice_no, serial_no, quantity, item_code, created_at
FROM delivery_records
WHERE owner_user_id = $user_id
ORDER BY created_at DESC
LIMIT 20
";

$result = $conn->query($query);

echo "<pre style='background: #222; color: #0f0; padding: 20px; font-family: monospace; font-size: 12px;'>\n";
echo "=== DELIVERY RECORDS DATA CHECK ===\n\n";

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Invoice: " . $row['invoice_no'] . " | Serial: [" . ($row['serial_no'] ?: 'EMPTY') . "] | Qty: " . $row['quantity'] . " | Item: " . $row['item_code'] . "\n";
    }
} else {
    echo "No records found.\n";
}

echo "</pre>\n";
?>

