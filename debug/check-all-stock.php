<?php
require_once __DIR__ . '/../db_config.php';

// Check ALL Stock Addition records
$sql = "SELECT id, item_code, item_name, quantity, company_name, sold_to, owner_user_id 
        FROM delivery_records 
        WHERE company_name = 'Stock Addition'
        ORDER BY item_code
        LIMIT 20";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    echo "All Stock Addition records:\n";
    echo "ID | Item Code | Quantity | Sold To | Owner\n";
    echo str_repeat("-", 100) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo $row['id'] . " | " . 
             $row['item_code'] . " | " . 
             $row['quantity'] . " | " . 
             ($row['sold_to'] ?? 'NULL') . " | " . 
             $row['owner_user_id'] . "\n";
    }
} else {
    echo "No Stock Addition records found\n";
}

// Count total
$count = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE company_name = 'Stock Addition'");
if ($count) {
    $row = $count->fetch_assoc();
    echo "\nTotal Stock Addition records: " . $row['cnt'] . "\n";
}

$conn->close();
?>

