<?php
require_once __DIR__ . '/../db_config.php';

// Check what the delivery_records filter would actually return
$owner_user_id = 1; // Adjust based on actual user

$delivery_where = "owner_user_id = {$owner_user_id} AND company_name NOT IN ('Orders', 'Inquiry', 'Stock Addition', 'Andison Manila')
    AND COALESCE(sold_to, '') != ''
    AND NOT (
    LOWER(TRIM(COALESCE(company_name, ''))) IN ('andison manila', 'to andison manila')
    OR LOWER(TRIM(COALESCE(transferred_to, ''))) IN ('andison manila', 'to andison manila')
    OR LOWER(TRIM(COALESCE(sold_to, ''))) IN ('andison manila', 'to andison manila')
)
    AND LOWER(TRIM(COALESCE(sold_to, ''))) NOT LIKE '%stock in manila%'";

$sql = "SELECT id, invoice_no, sold_to, company_name FROM delivery_records WHERE " . $delivery_where . " LIMIT 10";
echo "Testing filter query:\n";
echo "SQL: " . $sql . "\n\n";

$result = $conn->query($sql);
if ($result) {
    echo "Records returned: " . $result->num_rows . "\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . ", Invoice: " . $row['invoice_no'] . ", Sold To: " . $row['sold_to'] . ", Company: " . $row['company_name'] . "\n";
    }
} else {
    echo "Error: " . $conn->error . "\n";
}

// Also check total counts
echo "\n=== SUMMARY ===\n";
$counts = $conn->query("SELECT 
    (SELECT COUNT(*) FROM delivery_records WHERE sold_to LIKE '%Stock in Manila%') as stock_in_manila,
    (SELECT COUNT(*) FROM delivery_records WHERE company_name = 'Andison Manila') as andison_manila_company,
    (SELECT COUNT(*) FROM delivery_records WHERE LOWER(sold_to) LIKE '%andison manila%') as andison_manila_sold");
    
if ($counts) {
    $row = $counts->fetch_assoc();
    echo "Records with sold_to='Stock in Manila': " . $row['stock_in_manila'] . "\n";
    echo "Records with company_name='Andison Manila': " . $row['andison_manila_company'] . "\n";
    echo "Records with sold_to containing 'andison manila': " . $row['andison_manila_sold'] . "\n";
}

$conn->close();
?>

