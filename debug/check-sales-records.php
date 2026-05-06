<?php
require_once __DIR__ . '/../db_config.php';

// Check sales records
$result = $conn->query("
    SELECT id, item_code, serial_no, quantity, sold_to, record_type, created_at
    FROM delivery_records
    WHERE record_type = 'sales'
    LIMIT 5
");

if ($result && $result->num_rows > 0) {
    echo "✓ Found " . $result->num_rows . " sales record(s):\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']} | Item: {$row['item_code']} | Serial: {$row['serial_no']} | Qty: {$row['quantity']} | Sold To: {$row['sold_to']} | Type: {$row['record_type']}\n";
    }
} else {
    echo "ℹ No sales records yet. Add a sale through the Sales tab to create one!\n";
}

// Check inventory records
$inv_result = $conn->query("
    SELECT COUNT(*) as count
    FROM delivery_records
    WHERE record_type = 'inventory' OR record_type IS NULL
");
$inv_data = $inv_result->fetch_assoc();
echo "\n✓ Inventory records: {$inv_data['count']}\n";

$conn->close();
?>

