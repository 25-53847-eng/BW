<?php
session_start();
$_SESSION['user_id'] = 1; // Simulate logged-in user

require '../db_config.php';

// Get an inventory item
$inv = $conn->query("SELECT id, item_code, quantity, invoice_no, delivery_month, delivery_day, delivery_year, item_name, uom, serial_no, company_name, transferred_to, groupings FROM delivery_records WHERE quantity > 2 LIMIT 1")->fetch_assoc();

if(!$inv) {
    echo "No inventory\n";
    exit;
}

echo "=== TESTING ADD-SALE FLOW ===\n\n";
echo "Using: {$inv['item_code']} (ID: {$inv['id']}, Qty: {$inv['quantity']})\n\n";

// Prepare data
$inventory_id = $inv['id'];
$qty_sold = 1;
$sold_to = "Direct Test Sale";
$sold_to_month = "May";
$sold_to_day = 5;
$delivery_date = "2026-05-05";
$notes = "Direct test from diagnostic";

// Step 1: Prepare escaping like add-sale.php does
$sold_to_esc = $conn->real_escape_string($sold_to);
$sold_to_month_esc = $conn->real_escape_string($sold_to_month);
$delivery_date_esc = $conn->real_escape_string($delivery_date);
$notes_esc = $conn->real_escape_string($notes);

// Copy from inventory and escape
$inv_invoice_no = $conn->real_escape_string($inv['invoice_no'] ?? '');
$inv_delivery_month = $conn->real_escape_string($inv['delivery_month'] ?? '');
$inv_delivery_day = intval($inv['delivery_day'] ?? 0);
$inv_delivery_year = intval($inv['delivery_year'] ?? 0);
$inv_item_code = $conn->real_escape_string($inv['item_code']);
$inv_item_name = $conn->real_escape_string($inv['item_name'] ?? '');
$inv_uom = $conn->real_escape_string($inv['uom'] ?? '');
$inv_serial_no = $conn->real_escape_string($inv['serial_no'] ?? '');
$inv_company_name = $conn->real_escape_string($inv['company_name'] ?? '');
$inv_transferred_to = $conn->real_escape_string($inv['transferred_to'] ?? '');
$inv_groupings = $conn->real_escape_string($inv['groupings'] ?? '');

echo "Step 1: Starting transaction\n";
$conn->begin_transaction();

try {
    // INSERT
    $insert_sql = "INSERT INTO delivery_records (
        invoice_no, delivery_date, delivery_month, delivery_day, delivery_year,
        item_code, item_name, quantity, uom, serial_no,
        company_name, transferred_to, sold_to, sold_to_month, sold_to_day,
        groupings, status, notes, created_at
    ) VALUES (
        '$inv_invoice_no',
        '$delivery_date_esc',
        '$inv_delivery_month',
        $inv_delivery_day,
        $inv_delivery_year,
        '$inv_item_code',
        '$inv_item_name',
        $qty_sold,
        '$inv_uom',
        '$inv_serial_no',
        '$inv_company_name',
        '$inv_transferred_to',
        '$sold_to_esc',
        '$sold_to_month_esc',
        $sold_to_day,
        '$inv_groupings',
        'Delivered',
        '$notes_esc',
        NOW()
    )";
    
    echo "Executing INSERT...\n";
    if(!$conn->query($insert_sql)) {
        throw new Exception("INSERT failed: " . $conn->error);
    }
    echo "✓ INSERT successful\n";

    // UPDATE inventory
    $new_qty = $inv['quantity'] - $qty_sold;
    $update_sql = "UPDATE delivery_records SET quantity = $new_qty WHERE id = $inventory_id";
    
    echo "Executing UPDATE...\n";
    if(!$conn->query($update_sql)) {
        throw new Exception("UPDATE failed: " . $conn->error);
    }
    echo "✓ UPDATE successful\n";

    $conn->commit();
    echo "\n✅ Transaction committed!\n";

} catch (Exception $e) {
    $conn->rollback();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}

// Verify
echo "\nVerifying save...\n";
$verify = $conn->query("SELECT id FROM delivery_records WHERE sold_to = 'Direct Test Sale' LIMIT 1");
if($verify->num_rows > 0) {
    echo "✅ Record found in database!\n";
    // Clean up
    $conn->query("DELETE FROM delivery_records WHERE sold_to = 'Direct Test Sale'");
    $conn->query("UPDATE delivery_records SET quantity = " . $inv['quantity'] . " WHERE id = $inventory_id");
} else {
    echo "❌ Record NOT found\n";
}

$conn->close();
?>

