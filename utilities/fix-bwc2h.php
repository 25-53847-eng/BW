<?php
require '../db_config.php';

// Update recent BWC2-H records where sold_to is empty - populate it from company_name
$update = $conn->query("
    UPDATE delivery_records 
    SET sold_to = company_name 
    WHERE item_code = 'BWC2-H' 
    AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')
    AND company_name NOT IN ('Stock Addition', 'Orders', 'Inquiry')
    AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
");

if ($update) {
    $affected = $conn->affected_rows;
    echo "Updated $affected records - set sold_to from company_name\n";
    
    // Show the updated records
    $result = $conn->query("SELECT id, item_code, company_name, sold_to, created_at FROM delivery_records WHERE item_code = 'BWC2-H' AND sold_to IS NOT NULL AND TRIM(sold_to) != '' ORDER BY created_at DESC LIMIT 5");
    echo "\nUpdated records:\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | Code: " . $row['item_code'] . " | Company: " . $row['company_name'] . " | Sold_to: " . $row['sold_to'] . "\n";
    }
} else {
    echo "Error: " . $conn->error;
}
?>


