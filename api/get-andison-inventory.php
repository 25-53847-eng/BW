<?php
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../db_config.php';

// Get current inventory items for Andison Manila
// These are items that were delivered TO Andison Manila and have quantity > 0
$sql = "
    SELECT
        id,
        invoice_no,
        delivery_date,
        delivery_month,
        delivery_day,
        delivery_year,
        item_code,
        item_name,
        quantity,
        uom,
        serial_no,
        company_name,
        transferred_to,
        sold_to,
        sold_to_month,
        sold_to_day,
        notes,
        groupings,
        status
    FROM delivery_records
    WHERE (
        company_name = 'to Andison Manila'
        OR transferred_to = 'to Andison Manila'
        OR LOWER(TRIM(COALESCE(sold_to, ''))) IN ('andison manila', 'to andison manila', 'stock in manila', 'andison manila use')
    )
    AND quantity > 0
    AND (
        COALESCE(sold_to, '') = ''
        OR COALESCE(sold_to, '') = '0'
        OR LOWER(TRIM(COALESCE(sold_to, ''))) IN ('andison manila', 'to andison manila', 'stock in manila', 'andison manila use')
    )
    ORDER BY item_code ASC, serial_no ASC
";

$result = $conn->query($sql);

if (!$result) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit;
}

$inventory_items = [];
while ($row = $result->fetch_assoc()) {
    $inventory_items[] = $row;
}

echo json_encode([
    'success' => true,
    'items' => $inventory_items,
    'count' => count($inventory_items)
]);

$conn->close();
?>
