<?php
/**
 * Migration Script: Add missing inquiry columns to delivery_records table
 * 
 * This script adds the following columns to the delivery_records table:
 * - order_customer
 * - order_date
 * - po_number
 * - po_status
 * - unit_price
 * - total_amount
 * - groupings
 * - dataset_name
 * 
 * These columns are required for the inquiry workflow to function properly.
 */

require_once dirname(__DIR__) . '/db_config.php';

$columns = [
    'order_customer' => "VARCHAR(255) DEFAULT NULL COMMENT 'Customer/Client for inquiry orders'",
    'order_date' => "DATE DEFAULT NULL COMMENT 'Order date for inquiry items'",
    'po_number' => "VARCHAR(50) DEFAULT NULL COMMENT 'Purchase Order number'",
    'po_status' => "VARCHAR(50) DEFAULT NULL COMMENT 'PO Status (No PO, Pending, Received)'",
    'unit_price' => "DECIMAL(15,2) DEFAULT 0 COMMENT 'Unit price for inquiry items'",
    'total_amount' => "DECIMAL(15,2) DEFAULT 0 COMMENT 'Total amount (quantity * unit_price)'",
    'groupings' => "VARCHAR(10) DEFAULT NULL COMMENT 'Product groupings (1A, 1B, 2A, 2B, 3A, 4A)'",
    'dataset_name' => "VARCHAR(255) DEFAULT NULL COMMENT 'Dataset name for categorization'"
];

$errors = [];
$added = [];

foreach ($columns as $columnName => $definition) {
    // Check if column already exists
    $checkSql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_NAME = 'delivery_records' 
                 AND COLUMN_NAME = '$columnName' 
                 AND TABLE_SCHEMA = DATABASE()";
    
    $result = $conn->query($checkSql);
    
    if ($result && $result->num_rows > 0) {
        $added[] = "Column '$columnName' already exists - skipping";
        continue;
    }
    
    // Add the column
    $alterSql = "ALTER TABLE `delivery_records` ADD COLUMN `$columnName` $definition";
    
    if ($conn->query($alterSql)) {
        $added[] = "✓ Added column: $columnName";
    } else {
        $errors[] = "✗ Failed to add column '$columnName': " . $conn->error;
    }
}

// Add index for po_status if it doesn't exist
$indexCheckSql = "SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS 
                  WHERE TABLE_NAME = 'delivery_records' 
                  AND INDEX_NAME = 'idx_po_status' 
                  AND TABLE_SCHEMA = DATABASE()";

$indexResult = $conn->query($indexCheckSql);

if ($indexResult && $indexResult->num_rows === 0) {
    $indexSql = "ALTER TABLE `delivery_records` ADD INDEX `idx_po_status` (`po_status`)";
    if ($conn->query($indexSql)) {
        $added[] = "✓ Added index: idx_po_status";
    } else {
        $errors[] = "✗ Failed to add index: " . $conn->error;
    }
} else {
    $added[] = "Index 'idx_po_status' already exists - skipping";
}

// Output results
header('Content-Type: application/json');

$response = [
    'success' => count($errors) === 0,
    'added' => $added,
    'errors' => $errors,
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
