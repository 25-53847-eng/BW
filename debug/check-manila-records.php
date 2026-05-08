<?php
require_once __DIR__ . '/../db_config.php';

// Check ALL records regardless of filters
$sql = "SELECT id, invoice_no, item_code, sold_to, company_name, owner_user_id, dataset_name 
        FROM delivery_records 
        WHERE sold_to IS NOT NULL 
        AND (sold_to LIKE '%Manila%' OR sold_to LIKE '%manila%')
        ORDER BY id DESC
        LIMIT 20";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    echo "Found " . $result->num_rows . " records with 'Manila' in sold_to:\n";
    echo "ID | Invoice | sold_to | company_name | owner | dataset\n";
    echo str_repeat("-", 120) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo $row['id'] . " | " . 
             $row['invoice_no'] . " | " . 
             '"' . $row['sold_to'] . '"' . " | " . 
             '"' . $row['company_name'] . '"' . " | " . 
             $row['owner_user_id'] . " | " . 
             ($row['dataset_name'] ?? 'NULL') . "\n";
    }
} else {
    echo "No records found\n";
}

$conn->close();
?>

