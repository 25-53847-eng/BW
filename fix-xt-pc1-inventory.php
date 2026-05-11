<?php
include 'db_config.php';

echo "=== FIXING XT-PC1 INVENTORY ===\n\n";

// Check current status
$result = $conn->query("SELECT id, quantity, company_name FROM delivery_records WHERE id=278162");
$row = $result->fetch_assoc();
echo "Before: ID 278162 | Qty=" . $row['quantity'] . " | Company='" . $row['company_name'] . "'\n";

// Update the orphaned record to be Stock Addition inventory
$updateQuery = "UPDATE delivery_records SET company_name = 'Stock Addition', status = 'Delivered' WHERE id = 278162";
if ($conn->query($updateQuery)) {
    echo "✓ Updated successfully!\n\n";
    
    // Verify the update
    $result = $conn->query("SELECT id, quantity, company_name FROM delivery_records WHERE id=278162");
    $row = $result->fetch_assoc();
    echo "After: ID 278162 | Qty=" . $row['quantity'] . " | Company='" . $row['company_name'] . "'\n\n";
    
    // Show all XT-PC1 inventory items now
    echo "=== ALL XT-PC1 INVENTORY ITEMS NOW ===\n";
    $result = $conn->query("SELECT id, quantity, company_name FROM delivery_records WHERE item_code='XT-PC1' AND company_name = 'Stock Addition' ORDER BY id");
    $count = 0;
    while($row = $result->fetch_assoc()) {
        $count++;
        echo "Item $count: ID=" . $row['id'] . " | Qty=" . $row['quantity'] . "\n";
    }
    echo "\nTotal inventory items: $count\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
}
?>
