<?php
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../db_config.php';

$data = json_decode(file_get_contents('php://input'), true);

// DEBUG: Log incoming data
error_log("ADD-SALE DEBUG - Incoming data: " . json_encode($data));

if (!$data) {
    error_log("ADD-SALE DEBUG - Invalid JSON received");
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

if (!isset($data['inventory_id']) || !isset($data['quantity']) || !isset($data['sold_to'])) {
    echo json_encode(['success' => false, 'message' => 'Missing: inventory_id, quantity, or sold_to']);
    exit;
}

$inventory_id = intval($data['inventory_id']);
$qty_sold = intval($data['quantity']);

if ($inventory_id <= 0 || $qty_sold <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid inventory_id or quantity']);
    exit;
}

// Get the inventory item
$result = $conn->query("SELECT * FROM delivery_records WHERE id = $inventory_id");

if (!$result || $result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Inventory item not found']);
    exit;
}

$inventory = $result->fetch_assoc();
$current_qty = intval($inventory['quantity']);

if ($qty_sold > $current_qty) {
    echo json_encode(['success' => false, 'message' => "Not enough stock. Available: $current_qty, Requested: $qty_sold"]);
    exit;
}

// Prepare all values - escape strings
$sold_to = $conn->real_escape_string(trim($data['sold_to']));
$sold_to_month = $conn->real_escape_string(trim($data['sold_to_month'] ?? ''));
$sold_to_day = intval($data['sold_to_day'] ?? 0);
$delivery_date = $conn->real_escape_string(trim($data['delivery_date'] ?? ''));
$notes = $conn->real_escape_string(trim($data['notes'] ?? ''));

// Copy from inventory and escape
$inv_invoice_no = $conn->real_escape_string($inventory['invoice_no']);
$inv_delivery_month = $conn->real_escape_string($inventory['delivery_month']);
$inv_delivery_day = intval($inventory['delivery_day']);
$inv_delivery_year = intval($inventory['delivery_year']);
$inv_item_code = $conn->real_escape_string($inventory['item_code']);
$inv_item_name = $conn->real_escape_string($inventory['item_name']);
$inv_uom = $conn->real_escape_string($inventory['uom']);
$inv_serial_no = $conn->real_escape_string($inventory['serial_no']);
// For new sales, ALWAYS mark as 'to Andison Manila' so WHERE clause matches
$inv_company_name = 'to Andison Manila';
$inv_transferred_to = 'to Andison Manila';
$inv_groupings = $conn->real_escape_string($inventory['groupings']);
$inv_dataset_name = $conn->real_escape_string($inventory['dataset_name'] ?? '');

// START TRANSACTION
$conn->begin_transaction();

try {
    // 1. INSERT new sales record - MARK AS SALES TRANSACTION
    $insert_sql = "INSERT INTO delivery_records (
        invoice_no, delivery_date, delivery_month, delivery_day, delivery_year,
        item_code, item_name, quantity, uom, serial_no,
        company_name, transferred_to, sold_to, sold_to_month, sold_to_day,
        groupings, status, notes, dataset_name, record_type, created_at
    ) VALUES (
        '$inv_invoice_no',
        '$delivery_date',
        '$inv_delivery_month',
        $inv_delivery_day,
        $inv_delivery_year,
        '$inv_item_code',
        '$inv_item_name',
        $qty_sold,
        '$inv_uom',
        '$inv_serial_no',
        '$inv_company_name',
        '$inv_transferred_to',
        '$sold_to',
        '$sold_to_month',
        $sold_to_day,
        '$inv_groupings',
        'Delivered',
        '$notes',
        '$inv_dataset_name',
        'sales',
        NOW()
    )";
    
    if (!$conn->query($insert_sql)) {
        throw new Exception("INSERT failed: " . $conn->error);
    }

    // 2. UPDATE inventory - REDUCE quantity
    $new_qty = $current_qty - $qty_sold;
    $update_sql = "UPDATE delivery_records SET quantity = $new_qty WHERE id = $inventory_id";
    
    if (!$conn->query($update_sql)) {
        throw new Exception("UPDATE failed: " . $conn->error);
    }

    // COMMIT if both successful
    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Sale recorded! Inventory reduced from $current_qty to $new_qty"]);

} catch (Exception $e) {
    // ROLLBACK on error
    $conn->rollback();
    error_log("Add Sale Transaction Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
