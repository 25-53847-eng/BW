<?php
require '../db_config.php';

echo "=== CHECKING FOR RECENT SALES RECORDS ===\n\n";

// Check for records with actual sold_to values (not Andison Manila default)
$result = $conn->query("
    SELECT id, invoice_no, item_code, sold_to, company_name, quantity, created_at 
    FROM delivery_records 
    WHERE sold_to NOT IN ('andison manila', 'to andison manila', 'stock in manila', 'andison manila use', 'zamora use', '', NULL)
    AND sold_to IS NOT NULL
    ORDER BY created_at DESC 
    LIMIT 20
");

if($result->num_rows === 0) {
    echo "? No sales records found!\n";
} else {
    echo "? Found " . $result->num_rows . " sales records:\n\n";
    while($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}\n";
        echo "  Item: {$row['item_code']}\n";
        echo "  Sold To: {$row['sold_to']}\n";
        echo "  Company: {$row['company_name']}\n";
        echo "  Qty: {$row['quantity']}\n";
        echo "  Created: {$row['created_at']}\n";
        echo "---\n";
    }
}

$conn->close();
?>

