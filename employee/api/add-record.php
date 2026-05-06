<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);

// Start session and check authentication
session_start();
if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'employee';
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized - please login'
    ]);
    exit;
}

// Include database configuration and permission helper
require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/permission-helper.php';

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request data'
    ]);
    exit;
}

// Check company_name to determine which permission to check
$company_name = trim($data['company_name'] ?? '');
$isAndisonRecord = stripos($company_name, 'andison') !== false;

// Check appropriate permission based on record type
$requiredPermission = $isAndisonRecord ? 'andison_add_records' : 'delivery_add_records';
if (!isPermissionEnabled($requiredPermission, $conn)) {
    http_response_code(403);
    $recordType = $isAndisonRecord ? 'Andison Manila records' : 'delivery records';
    echo json_encode([
        'success' => false,
        'message' => "Permission denied. Admin has not granted you access to add {$recordType}."
    ]);
    exit;
}

// No required fields - just need at least some data

try {
    // Extract and sanitize data
    $quantity = isset($data['quantity']) ? intval($data['quantity']) : 0;
    $unit_price = isset($data['unit_price']) ? floatval($data['unit_price']) : 0;
    $uom = trim($data['uom'] ?? '');
    $serial_no = trim($data['serial_no'] ?? '');
    $company_name = trim($data['company_name'] ?? '');
    $transferred_to = trim($data['transferred_to'] ?? '');
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
    $dataset_name = trim($data['dataset_name'] ?? '');
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
        if ($delivery_year == intval(date('Y'))) $delivery_year = intval(date('Y', $timestamp));
    }
    
    // Build delivery_date if we have month, day, year but no date
    if (empty($delivery_date) && !empty($delivery_month) && $delivery_day > 0 && $delivery_year > 0) {
        $month_num = date('n', strtotime($delivery_month . ' 1'));
        if ($month_num) {
            $delivery_date = sprintf('%04d-%02d-%02d', $delivery_year, $month_num, $delivery_day);
        }
    }
    
    // Auto-route to Andison Manila if sold_to is "Stock in Manila"
    if (strtolower(trim($sold_to)) === 'stock in manila') {
        // Update company_name and sold_to to route to Andison Manila view
        $company_name = 'Andison Manila';
        $sold_to = 'Andison Manila';
    }
    
    // Insert into database
    $sql = "INSERT INTO delivery_records 
            (invoice_no, serial_no, delivery_month, delivery_day, delivery_year, delivery_date, item_code, item_name, company_name, transferred_to, sold_to, quantity, unit_price, status, highlight_color, notes, uom, sold_to_month, sold_to_day, groupings, dataset_name, owner_user_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . ($conn->error ?? 'Unknown error'));
    }

    $stmt->bind_param(
        'ssissssssssiidssssissi',
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
        $dataset_name,
        $_SESSION['user_id']
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $new_id = 0;
    if ($conn instanceof mysqli) {
        $new_id = intval($conn->insert_id);
    } else {
        $idResult = @$conn->query("SELECT last_insert_rowid() AS id");
        if ($idResult) {
            $idRow = $idResult->fetch_assoc();
            $new_id = intval($idRow['id'] ?? 0);
        }
    }
    $stmt->close();

    // Deduct from inventory if this is a delivery (has sold_to or transferred_to)
    if (($quantity > 0) && (!empty($sold_to) || !empty($transferred_to))) {
        // Find the Stock Addition record for this item
        $stock_check = "SELECT id, quantity FROM delivery_records 
                        WHERE item_code = ? 
                        AND company_name = 'Stock Addition' 
                        AND (COALESCE(sold_to, '') = '' AND COALESCE(transferred_to, '') = '')
                        AND owner_user_id = ?
                        LIMIT 1";
        
        $stock_stmt = $conn->prepare($stock_check);
        if ($stock_stmt) {
            $stock_stmt->bind_param('si', $item_code, $_SESSION['user_id']);
            $stock_stmt->execute();
            $stock_result = $stock_stmt->get_result();
            
            if ($stock_result && $stock_row = $stock_result->fetch_assoc()) {
                $current_stock_qty = intval($stock_row['quantity']);
                
                // Check if there's enough stock to deliver
                if ($quantity > $current_stock_qty) {
                    throw new Exception("Insufficient stock for item '{$item_code}'. Available: {$current_stock_qty} units, Requested: {$quantity} units");
                }
                
                // Deduct from inventory
                $new_stock_qty = $current_stock_qty - $quantity;
                
                $stock_update = "UPDATE delivery_records 
                                SET quantity = ?, updated_at = CURRENT_TIMESTAMP 
                                WHERE id = ? AND owner_user_id = ?";
                
                $update_stmt = $conn->prepare($stock_update);
                if ($update_stmt) {
                    $update_stmt->bind_param('iii', $new_stock_qty, $stock_row['id'], $_SESSION['user_id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }
            $stock_stmt->close();
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Record added successfully',
        'id' => $new_id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
