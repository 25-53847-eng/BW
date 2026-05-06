<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

require_once __DIR__ . '/../db_config.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid JSON payload'
    ]);
    exit;
}

$delivery_record_id = intval($data['delivery_record_id'] ?? 0);
$remove_from_delivery = isset($data['remove_from_delivery']) ? (bool)$data['remove_from_delivery'] : true;

if ($delivery_record_id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid delivery record ID'
    ]);
    exit;
}

try {
    // Get the delivery record
    $stmt = $conn->prepare('SELECT * FROM delivery_records WHERE id = ? AND owner_user_id = ?');
    if (!$stmt) {
        throw new Exception('Failed to prepare select query');
    }

    $user_id = intval($_SESSION['user_id']);
    $stmt->bind_param('ii', $delivery_record_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $stmt->close();

    if (!$record) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Delivery record not found'
        ]);
        exit;
    }

    // Check if already in warranty
    $check_stmt = $conn->prepare('SELECT id FROM warranty_replacements WHERE delivery_record_id = ? LIMIT 1');
    if ($check_stmt) {
        $check_stmt->bind_param('i', $delivery_record_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        if ($check_result->num_rows > 0) {
            $check_stmt->close();
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Record already in warranty'
            ]);
            exit;
        }
        $check_stmt->close();
    }

    // Insert into warranty_replacements
    $insert_sql = "INSERT INTO warranty_replacements (
        delivery_record_id, invoice_no, delivery_month, delivery_day, delivery_year,
        record_date, delivery_date, item_code, item_name, company_name, sold_to,
        quantity, status, uom, serial_no, transferred_to, notes, warranty_flag,
        warranty_date, red_text_detected, dataset_name, highlight_color, cell_styles,
        created_at, updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";

    $insert_stmt = $conn->prepare($insert_sql);
    if (!$insert_stmt) {
        throw new Exception('Failed to prepare insert query');
    }

    $warranty_date = date('Y-m-d');
    $status = 'Warranty Pending';
    $warranty_flag = 1;
    $red_text_detected = 1;
    
    // Extract variables for bind_param
    $invoice_no = $record['invoice_no'];
    $delivery_month = $record['delivery_month'];
    $delivery_day = $record['delivery_day'];
    $delivery_year = $record['delivery_year'];
    $record_date = $record['record_date'];
    $delivery_date = $record['delivery_date'];
    $item_code = $record['item_code'];
    $item_name = $record['item_name'];
    $company_name = $record['company_name'];
    $sold_to = $record['sold_to'];
    $quantity = $record['quantity'];
    $uom = $record['uom'];
    $serial_no = $record['serial_no'];
    $transferred_to = $record['transferred_to'];
    $notes = $record['notes'];
    $dataset_name = $record['dataset_name'];
    $highlight_color = $record['highlight_color'];
    $cell_styles = $record['cell_styles'];
    
    $insert_stmt->bind_param(
        'issssissssisssssisssss',
        $delivery_record_id,
        $invoice_no,
        $delivery_month,
        $delivery_day,
        $delivery_year,
        $record_date,
        $delivery_date,
        $item_code,
        $item_name,
        $company_name,
        $sold_to,
        $quantity,
        $status,
        $uom,
        $serial_no,
        $transferred_to,
        $notes,
        $warranty_flag,
        $warranty_date,
        $red_text_detected,
        $dataset_name,
        $highlight_color,
        $cell_styles
    );

    if (!$insert_stmt->execute()) {
        throw new Exception('Failed to insert warranty record');
    }
    $insert_stmt->close();

    // Optionally remove from delivery_records
    if ($remove_from_delivery) {
        $delete_stmt = $conn->prepare('DELETE FROM delivery_records WHERE id = ? AND owner_user_id = ?');
        if (!$delete_stmt) {
            throw new Exception('Failed to prepare delete query');
        }

        $delete_stmt->bind_param('ii', $delivery_record_id, $user_id);
        if (!$delete_stmt->execute()) {
            throw new Exception('Failed to remove from delivery records');
        }
        $delete_stmt->close();
    }

    echo json_encode([
        'success' => true,
        'message' => 'Record moved to warranty successfully',
        'delivery_record_id' => $delivery_record_id
    ]);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to move record to warranty. ' . $e->getMessage()
    ]);
}
?>
