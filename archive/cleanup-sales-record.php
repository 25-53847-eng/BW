<?php
require_once 'db_config.php';

// Delete records with "Sales record not found"
$stmt = $conn->prepare("DELETE FROM delivery_records WHERE company_name = 'Sales record not found'");
$stmt->execute();
$deleted = $stmt->affected_rows;
$stmt->close();

echo "Deleted $deleted records with 'Sales record not found'\n";

// Verify deletion
$result = $conn->query("SELECT COUNT(*) as count FROM delivery_records WHERE company_name = 'Sales record not found'");
$row = $result->fetch_assoc();
echo "Remaining 'Sales record not found' records: " . $row['count'] . "\n";

// Show new company count
$result2 = $conn->query("SELECT COUNT(DISTINCT company_name) as count FROM delivery_records 
    WHERE company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila', 'to Andison Manila', '')");
$row2 = $result2->fetch_assoc();
echo "Total unique client companies: " . $row2['count'] . "\n";
?>
