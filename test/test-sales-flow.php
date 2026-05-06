<?php
require '../db_config.php';

echo "=== TESTING SALES ADD FLOW ===\n\n";

// First, get an inventory item to use
$inventory = $conn->query("SELECT id, item_code, quantity FROM delivery_records WHERE quantity > 0 LIMIT 1")->fetch_assoc();

if(!$inventory) {
    echo "❌ No inventory items available\n";
    exit;
}

echo "Using inventory item: {$inventory['item_code']} (ID: {$inventory['id']}, Qty: {$inventory['quantity']})\n\n";

// Simulate the sales add
$sold_to = "Test Sale Company";
$qty_sold = 1;

$test_insert = "INSERT INTO delivery_records (
    invoice_no, delivery_date, delivery_month, delivery_day, delivery_year,
    item_code, item_name, quantity, uom, serial_no,
    company_name, transferred_to, sold_to, sold_to_month, sold_to_day,
    groupings, status, notes, created_at
) SELECT
    invoice_no, NOW(), delivery_month, delivery_day, delivery_year,
    item_code, item_name, {$qty_sold}, uom, serial_no,
    company_name, transferred_to, '{$sold_to}', 'May', 5,
    groupings, 'Delivered', 'Test Sale Record', NOW()
FROM delivery_records WHERE id = {$inventory['id']}";

echo "Executing INSERT...\n";
if($conn->query($test_insert)) {
    echo "✓ Sale record created!\n\n";
    
    // Update inventory
    echo "Updating inventory quantity...\n";
    $new_qty = $inventory['quantity'] - $qty_sold;
    if($conn->query("UPDATE delivery_records SET quantity = $new_qty WHERE id = {$inventory['id']}")) {
        echo "✓ Inventory updated (Qty: {$inventory['quantity']} -> $new_qty)\n\n";
        echo "✅✅✅ SALES FLOW WORKS!\n";
        
        // Cleanup
        $conn->query("DELETE FROM delivery_records WHERE sold_to = '{$sold_to}' AND notes = 'Test Sale Record'");
        $conn->query("UPDATE delivery_records SET quantity = {$inventory['quantity']} WHERE id = {$inventory['id']}");
    } else {
        echo "✗ Inventory update failed: " . $conn->error . "\n";
    }
} else {
    echo "✗ INSERT failed: " . $conn->error . "\n";
}

$conn->close();
?>

