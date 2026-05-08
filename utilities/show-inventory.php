<?php
require_once __DIR__ . '/../db_config.php';

// Show sample inventory items
$sql = "SELECT item_code, item_name, quantity, owner_user_id, dataset_name 
        FROM delivery_records 
        WHERE company_name = 'Stock Addition'
        ORDER BY quantity DESC
        LIMIT 10";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    echo "Inventory Items (Top 10 by Quantity):\n";
    echo str_pad("Item Code", 15) . " | " . str_pad("Item Name", 40) . " | " . str_pad("Qty", 8) . " | Owner\n";
    echo str_repeat("-", 85) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo str_pad($row['item_code'] ?? '', 15) . " | " . 
             str_pad(substr($row['item_name'] ?? '', 0, 38), 40) . " | " . 
             str_pad($row['quantity'], 8, ' ', STR_PAD_LEFT) . " | " . 
             $row['owner_user_id'] . "\n";
    }
} else {
    echo "No inventory items found\n";
}

$conn->close();
?>


