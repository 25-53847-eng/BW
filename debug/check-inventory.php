<?php
require_once __DIR__ . '/../db_config.php';

// Check inventory (Stock Addition records)
$sql = "SELECT item_code, item_name, quantity, company_name, sold_to, owner_user_id 
        FROM delivery_records 
        WHERE company_name = 'Stock Addition'
        AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')
        ORDER BY item_code
        LIMIT 20";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    echo "Current Inventory (Stock Addition records):\n";
    echo "Item Code | Item Name | Quantity | Owner\n";
    echo str_repeat("-", 100) . "\n";
    
    $total_qty = 0;
    while ($row = $result->fetch_assoc()) {
        echo $row['item_code'] . " | " . 
             substr($row['item_name'], 0, 30) . " | " . 
             $row['quantity'] . " | " . 
             $row['owner_user_id'] . "\n";
        $total_qty += intval($row['quantity']);
    }
    echo str_repeat("-", 100) . "\n";
    echo "Total inventory quantity: " . $total_qty . "\n";
} else {
    echo "No inventory records found\n";
}

$conn->close();
?>

