<?php
require_once __DIR__ . '/../db_config.php';

// Find all records with "Stock in Manila"
$sql = "SELECT id, invoice_no, item_code, sold_to, company_name, owner_user_id, dataset_name 
        FROM delivery_records 
        WHERE LOWER(TRIM(COALESCE(sold_to, ''))) LIKE '%stock in manila%'
        OR LOWER(TRIM(COALESCE(company_name, ''))) LIKE '%stock in manila%'
        LIMIT 20";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    echo "Found " . $result->num_rows . " records with Stock in Manila:\n";
    echo str_pad("ID", 6) . " | " . str_pad("Invoice", 15) . " | " . str_pad("Item", 10) . " | " . 
         str_pad("Sold To", 20) . " | " . str_pad("Company", 20) . " | Owner | Dataset\n";
    echo str_repeat("-", 100) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo str_pad($row['id'], 6) . " | " . 
             str_pad($row['invoice_no'] ?? '', 15) . " | " . 
             str_pad($row['item_code'] ?? '', 10) . " | " . 
             str_pad($row['sold_to'] ?? '', 20) . " | " . 
             str_pad($row['company_name'] ?? '', 20) . " | " . 
             $row['owner_user_id'] . " | " . 
             ($row['dataset_name'] ?? 'NULL') . "\n";
    }
} else {
    echo "No records found with Stock in Manila\n";
}

// Now update all of them
$update_sql = "UPDATE delivery_records 
        SET company_name = 'Andison Manila', 
            sold_to = 'Andison Manila',
            updated_at = CURRENT_TIMESTAMP
        WHERE LOWER(TRIM(COALESCE(sold_to, ''))) LIKE '%stock in manila%'
        OR LOWER(TRIM(COALESCE(company_name, ''))) LIKE '%stock in manila%'";

if ($conn->query($update_sql)) {
    echo "\nUpdated " . $conn->affected_rows . " records to Andison Manila\n";
} else {
    echo "Error updating: " . $conn->error . "\n";
}

$conn->close();
?>

