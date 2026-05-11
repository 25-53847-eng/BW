<?php
require_once 'db_config.php';

// Delete Sales record not found
$stmt = $conn->prepare("DELETE FROM delivery_records WHERE company_name = ?");
$company = "Sales record not found";
$stmt->bind_param('s', $company);
$stmt->execute();
$deleted = $stmt->affected_rows;
$stmt->close();

echo "Deleted $deleted records with 'Sales record not found'\n";

// Verify final count
$result = $conn->query("SELECT COUNT(DISTINCT company_name) as count FROM delivery_records 
    WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila', '')
    AND dataset_name = '2024 to NOW BW Sales Record'");
$row = $result->fetch_assoc();
echo "Final client companies: " . $row['count'] . "\n";

// Total records
$result2 = $conn->query("SELECT COUNT(*) as count FROM delivery_records WHERE dataset_name = '2024 to NOW BW Sales Record'");
$row2 = $result2->fetch_assoc();
echo "Total records: " . $row2['count'] . "\n";
?>
