<?php
require_once __DIR__ . '/../db_config.php';

// Migrate existing sales records (those with real customer in sold_to and created before record_type column)
// These are records in delivery_records with "to Andison Manila" company_name/transferred_to AND a real customer in sold_to field

$update_sql = "UPDATE delivery_records 
SET record_type = 'sales'
WHERE (company_name = 'to Andison Manila' OR transferred_to = 'to Andison Manila')
AND sold_to IS NOT NULL 
AND sold_to != '' 
AND LOWER(TRIM(sold_to)) NOT IN ('andison manila', 'to andison manila', 'stock in manila', 'andison manila use')
AND LOWER(TRIM(sold_to)) NOT LIKE '%stock in manila%'
AND record_type = 'inventory'";

if ($conn->query($update_sql)) {
    $rows = $conn->affected_rows;
    echo "✓ Updated $rows existing sales records with record_type = 'sales'\n";
} else {
    echo "✗ Error: " . $conn->error . "\n";
}

$conn->close();
?>
