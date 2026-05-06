<?php
session_start();
require_once '../db_config.php';

// Sample check: Find invoices with multiple items
$user_id = intval($_SESSION['user_id'] ?? 1);

echo "=== CHECKING INVOICE GROUPING ISSUE ===\n\n";

// Get invoices that have multiple rows
$query = "
SELECT invoice_no, COUNT(*) as row_count, GROUP_CONCAT(DISTINCT serial_no) as serials, GROUP_CONCAT(quantity) as quantities
FROM delivery_records
WHERE owner_user_id = $user_id
GROUP BY invoice_no
HAVING row_count > 1
LIMIT 5
";

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    echo "Invoices with MULTIPLE rows:\n";
    while ($row = $result->fetch_assoc()) {
        echo "\nInvoice: " . $row['invoice_no'] . "\n";
        echo "  Row count: " . $row['row_count'] . "\n";
        echo "  Serials: " . ($row['serials'] ?: 'ALL EMPTY') . "\n";
        echo "  Quantities: " . $row['quantities'] . "\n";
    }
} else {
    echo "No invoices with multiple rows found (or all single-row invoices)\n";
}

// Check a specific invoice to see all its rows
echo "\n\n=== DETAIL: First Multi-Row Invoice ===\n";
$detailQuery = "
SELECT invoice_no, serial_no, quantity, item_code, item_name
FROM delivery_records
WHERE owner_user_id = $user_id
GROUP BY invoice_no
HAVING COUNT(*) > 1
LIMIT 1
";

$detailResult = $conn->query($detailQuery);
if ($detailResult && $detailResult->num_rows > 0) {
    $firstInv = $detailResult->fetch_assoc();
    $invNo = $firstInv['invoice_no'];
    
    echo "Showing all rows for invoice: $invNo\n\n";
    
    $rowQuery = "
    SELECT id, serial_no, quantity, item_code, item_name, created_at
    FROM delivery_records
    WHERE owner_user_id = $user_id AND invoice_no = '" . $conn->real_escape_string($invNo) . "'
    ORDER BY id
    ";
    
    $rowResult = $conn->query($rowQuery);
    $idx = 1;
    while ($row = $rowResult->fetch_assoc()) {
        echo "$idx. Serial: [" . ($row['serial_no'] ?: 'EMPTY') . "] Qty: " . $row['quantity'] . " Item: " . $row['item_code'] . " Created: " . $row['created_at'] . "\n";
        $idx++;
    }
}

?>

