<?php
session_start();
header('Content-Type: application/json');

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../db_config.php';

try {
    $owner_user_id = intval($_SESSION['user_id']);
    
    // Update all existing "Stock in Manila" records to be routed to Andison Manila
    $sql = "UPDATE delivery_records 
            SET company_name = 'Andison Manila', 
                sold_to = 'Andison Manila',
                updated_at = CURRENT_TIMESTAMP
            WHERE owner_user_id = ? 
            AND LOWER(TRIM(COALESCE(sold_to, ''))) LIKE '%stock in manila%'
            AND company_name != 'Andison Manila'";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param('i', $owner_user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $affected = $stmt->affected_rows;
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => "Migrated $affected records to Andison Manila",
        'affected_rows' => $affected
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
