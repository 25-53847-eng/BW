<?php
require 'db_config.php';

// Check for NULL or empty company_name
$sql = "SELECT COUNT(*) as cnt FROM delivery_records 
        WHERE company_name IS NULL OR company_name = '' OR company_name = ' '";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "Records with NULL/empty company_name: " . $row['cnt'] . "\n";

// Check total records
$sql = "SELECT COUNT(*) as cnt FROM delivery_records";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "Total records in delivery_records: " . $row['cnt'] . "\n";

// Count unique company names
$sql = "SELECT COUNT(DISTINCT company_name) as cnt FROM delivery_records 
        WHERE company_name IS NOT NULL AND company_name != ''";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
echo "Unique non-empty company names: " . $row['cnt'] . "\n";

// Show sample of records with empty company_name
echo "\n\nSample records with missing company_name:\n";
$sql = "SELECT id, item_code, item_name, quantity, delivery_date, sold_to, invoice_no 
        FROM delivery_records 
        WHERE company_name IS NULL OR company_name = '' OR company_name = ' '
        LIMIT 10";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "\nID: " . $row['id'] . "\n";
        echo "  Item: " . $row['item_code'] . " - " . $row['item_name'] . "\n";
        echo "  Invoice: " . $row['invoice_no'] . "\n";
        echo "  Sold To: " . $row['sold_to'] . "\n";
        echo "  Date: " . $row['delivery_date'] . "\n";
    }
} else {
    echo "No records found with empty company_name\n";
}
?>
