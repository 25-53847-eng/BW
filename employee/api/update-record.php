<?php
header('Content-Type: application/json');

session_start();

// Check authentication
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - please login']);
    exit;
}

// Include database configuration and permission helper
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/permission-helper.php';

// Check permission based on record type
$company_name = trim($_POST['company_name'] ?? '') or trim($_REQUEST['company_name'] ?? '');
$json = file_get_contents('php://input');
$data = json_decode($json, true);
if ($data && isset($data['company_name'])) {
    $company_name = trim($data['company_name']);
}

// Determine which permission to check
$isAndisonRecord = stripos($company_name, 'andison') !== false;
$requiredPermission = $isAndisonRecord ? 'andison_add_records' : 'delivery_edit_records';

if (!isPermissionEnabled($requiredPermission, $conn)) {
    http_response_code(403);
    $recordType = $isAndisonRecord ? 'Andison Manila records' : 'delivery records';
    echo json_encode(['success' => false, 'message' => "Permission denied. Admin has not granted you access to edit {$recordType}."]);
    exit;
}

// Get JSON data from request
$data = json_decode($json, true);

if (!$data || !isset($data['id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data - ID required'
    ]);
    exit;
}

try {
    $id = intval($data['id']);
    
    // Extract and sanitize data
    $quantity = isset($data['quantity']) ? intval($data['quantity']) : 0;
    $unit_price = isset($data['unit_price']) ? floatval($data['unit_price']) : 0;
    $uom = trim($data['uom'] ?? '');
    $serial_no = trim($data['serial_no'] ?? '');
    $transferred_to = trim($data['transferred_to'] ?? '');
    $company_name = trim($data['company_name'] ?? '');
    $sold_to = trim($data['sold_to'] ?? '');
    $delivery_date = !empty($data['delivery_date']) ? $data['delivery_date'] : null;
    $sold_to_month = trim($data['sold_to_month'] ?? '');
    $sold_to_day = !empty($data['sold_to_day']) ? intval($data['sold_to_day']) : null;
    $notes = trim($data['notes'] ?? '');
    $groupings = trim($data['groupings'] ?? '');
    $allowedGroupings = ['1A', '1B', '2A', '2B', '3A', '4A'];
    if ($groupings !== '' && !in_array($groupings, $allowedGroupings, true)) {
        $groupings = '';
    }
    $highlight_color = strtoupper(trim($data['highlight_color'] ?? ''));
    if ($highlight_color !== '' && !preg_match('/^#?[0-9A-F]{6}$/', $highlight_color)) {
        $highlight_color = '';
    }
    if ($highlight_color !== '' && $highlight_color[0] !== '#') {
        $highlight_color = '#' . $highlight_color;
    }
    
    // Main fields from form
    $invoice_no = trim($data['invoice_no'] ?? '');
    $item_code = trim($data['item_code'] ?? '');
    $item_name = trim($data['item_name'] ?? '');
    $status = trim($data['status'] ?? 'Delivered');
    
    // Direct input for delivery month, day, year from form
    $delivery_month = trim($data['delivery_month'] ?? '');
    $delivery_day = !empty($data['delivery_day']) ? intval($data['delivery_day']) : 0;
    $delivery_year = !empty($data['year']) ? intval($data['year']) : 0;
    
    // If date field is provided, parse it to get delivery_date
    if (!empty($data['date'])) {
        $delivery_date = $data['date'];
    }
    
    // If delivery_date is provided but month/day not set, extract from date
    if ($delivery_date && (empty($delivery_month) || $delivery_day == 0)) {
        $timestamp = strtotime($delivery_date);
        if (empty($delivery_month)) $delivery_month = date('F', $timestamp);
        if ($delivery_day == 0) $delivery_day = intval(date('j', $timestamp));
        if ($delivery_year == 0) $delivery_year = intval(date('Y', $timestamp));
    }
    
    // Build delivery_date if we have month, day, year but no date
    if (empty($delivery_date) && !empty($delivery_month) && $delivery_day > 0 && $delivery_year > 0) {
        $month_num = date('n', strtotime($delivery_month . ' 1'));
        if ($month_num) {
            $delivery_date = sprintf('%04d-%02d-%02d', $delivery_year, $month_num, $delivery_day);
        }
    }
    
    // Update database
    $sql = "UPDATE delivery_records SET 
            invoice_no = ?, 
            serial_no = ?, 
            delivery_month = ?, 
            delivery_day = ?, 
            delivery_year = ?, 
            delivery_date = ?, 
            item_code = ?, 
            item_name = ?, 
            company_name = ?, 
            transferred_to = ?, 
            sold_to = ?,
            quantity = ?, 
            unit_price = ?,
            status = ?, 
            highlight_color = ?,
            notes = ?, 
            uom = ?, 
            sold_to_month = ?, 
            sold_to_day = ?, 
            groupings = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . ($conn->error ?? 'Unknown error'));
    }

    $stmt->bind_param(
        'sssiissssssidsssssisi',
        $invoice_no,
        $serial_no,
        $delivery_month,
        $delivery_day,
        $delivery_year,
        $delivery_date,
        $item_code,
        $item_name,
        $company_name,
        $transferred_to,
        $sold_to,
        $quantity,
        $unit_price,
        $status,
        $highlight_color,
        $notes,
        $uom,
        $sold_to_month,
        $sold_to_day,
        $groupings,
        $id
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Record updated successfully',
        'id' => $id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
