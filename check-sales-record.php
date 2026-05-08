<?php
require_once 'db_config.php';

// Check what's in company_name with "Sales record not found"
$sql = "SELECT COUNT(*) as count, company_name FROM delivery_records 
        WHERE company_name = 'Sales record not found' 
        GROUP BY company_name";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    echo "Found " . $row['count'] . " records with company_name = '" . $row['company_name'] . "'\n";
}

// Show some examples of these records
$sql2 = "SELECT id, company_name, sold_to, invoice_no, item_code FROM delivery_records 
        WHERE company_name = 'Sales record not found' 
        LIMIT 5";
$result2 = $conn->query($sql2);
echo "\nExample records:\n";
while ($row = $result2->fetch_assoc()) {
    echo "  ID:" . $row['id'] . " | company_name: '" . $row['company_name'] . "' | sold_to: '" . $row['sold_to'] . "' | invoice_no: '" . $row['invoice_no'] . "'\n";
}
?>
