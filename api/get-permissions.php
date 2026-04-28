<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_config.php';

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    // Get all permissions (excluding upload_data)
    $result = $conn->query("SELECT id, permission_name, permission_label, enabled FROM employee_permissions WHERE permission_name != 'upload_data' ORDER BY id");
    
    if (!$result) {
        throw new Exception($conn->error);
    }
    
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        // Ensure enabled is an integer, not a string
        $row['enabled'] = intval($row['enabled']);
        $permissions[] = $row;
    }
    
    error_log('Permissions retrieved: ' . json_encode($permissions));
    
    echo json_encode([
        'success' => true,
        'permissions' => $permissions
    ]);
} catch (Exception $e) {
    error_log('Error in get-permissions: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
