<?php
require_once __DIR__ . '/../db_config.php';

// Find the specific records from the screenshot
$sql = "SELECT id, invoice_no, item_code, sold_to, company_name, owner_user_id, dataset_name, created_at 
        FROM delivery_records 
        WHERE invoice_no = '5263943152'
        ORDER BY id DESC";

$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    echo "Found records with invoice 5263943152:\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . "\n";
        echo "  sold_to: '" . $row['sold_to'] . "'\n";
        echo "  company_name: '" . $row['company_name'] . "'\n";
        echo "  owner_user_id: " . $row['owner_user_id'] . "\n";
        echo "  dataset: " . ($row['dataset_name'] ?? 'NULL') . "\n";
        echo "  created: " . $row['created_at'] . "\n\n";
    }
} else {
    echo "No records found with that invoice\n";
}

$conn->close();
?>

