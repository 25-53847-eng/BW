<?php
/**
 * Migration: Move legacy 'Orders' records with po_status 'No PO'/'Pending' to 'Inquiry'
 * These are inquiry items that were stored with the wrong company_name
 */

require_once dirname(__DIR__) . '/db_config.php';

// Migrate legacy inquiry items from 'Orders' to 'Inquiry'
$update_sql = "UPDATE delivery_records 
               SET company_name = 'Inquiry'
               WHERE company_name = 'Orders' 
               AND po_status IN ('No PO', 'Pending')
               AND order_customer IS NOT NULL
               AND order_customer != ''";

$success = false;
$message = '';
$affectedRows = 0;

if ($conn->query($update_sql)) {
    $affectedRows = $conn->affected_rows;
    $success = true;
    $message = "Successfully migrated $affectedRows inquiry items from 'Orders' to 'Inquiry'";
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
