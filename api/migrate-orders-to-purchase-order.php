<?php
/**
 * Migration: Move legacy 'Orders' purchase orders to 'Purchase Order'
 * These are orders placed by Andison (not client inquiries)
 */

require_once dirname(__DIR__) . '/db_config.php';

// Migrate legacy purchase orders (Andison internal orders with PO numbers)
$update_sql = "UPDATE delivery_records 
               SET company_name = 'Purchase Order'
               WHERE company_name = 'Orders' 
               AND order_customer = 'Andison Internal Order'
               AND (po_number IS NOT NULL AND po_number != '')";

$success = false;
$message = '';
$affectedRows = 0;

if ($conn->query($update_sql)) {
    $affectedRows = $conn->affected_rows;
    $success = true;
    $message = "Successfully migrated $affectedRows purchase orders from 'Orders' to 'Purchase Order'";
} else {
    $message = "Failed to migrate records: " . $conn->error;
}

// Output results
header('Content-Type: application/json');

$response = [
    'success' => $success,
    'message' => $message,
    'affected_rows' => $affectedRows,
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
