<?php
/**
 * Migration: Clean up existing data to ensure proper company_name separation
 * 
 * This script helps identify and optionally fix records with incorrect company_name values
 */

require_once dirname(__DIR__) . '/db_config.php';

// First, let's count records by company_name to understand current state
$sql = "SELECT company_name, COUNT(*) as count, 
               SUM(CASE WHEN po_status IN ('No PO', 'Pending') THEN 1 ELSE 0 END) as pending_count,
               SUM(CASE WHEN po_status = 'Received' THEN 1 ELSE 0 END) as received_count
        FROM delivery_records
        WHERE company_name IN ('Orders', 'Inquiry', 'Purchase Order', 'Stock Addition')
        GROUP BY company_name
        ORDER BY company_name";

$result = $conn->query($sql);
$stats = [];
while ($row = $result->fetch_assoc()) {
    $stats[] = $row;
}

// Now let's identify which records might need adjustment
$orphan_sql = "SELECT id, company_name, po_status, po_number, order_customer, created_at
               FROM delivery_records 
               WHERE company_name = 'Orders'
               LIMIT 20";  // Show first 20

$orphan_result = $conn->query($orphan_sql);
$orphans = [];
while ($row = $orphan_result->fetch_assoc()) {
    $orphans[] = $row;
}

// Output results
header('Content-Type: application/json');

$response = [
    'stats' => $stats,
    'orphan_records' => $orphans,
    'note' => 'Records with company_name = "Orders" should be reviewed',
    'recommendations' => [
        'If po_status is "Received": Move to "Delivery Records"',
        'If po_status is "No PO"/"Pending": These could be legacy inquiry items - should be "Inquiry"',
        'If empty po_status and po_number exists: Likely "Purchase Order"'
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
