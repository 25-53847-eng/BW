<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_config.php';

// Check if user is logged in and is admin
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $input = file_get_contents('php://input');
    error_log('Save-permissions input: ' . $input);
    
    $data = json_decode($input, true);
    
    if (!isset($data['permissions']) || !is_array($data['permissions'])) {
        throw new Exception('Invalid permissions data');
    }
    
    $updated_count = 0;
    
    // Update each permission
    foreach ($data['permissions'] as $perm) {
        if (!isset($perm['permission_name']) || !isset($perm['enabled'])) {
            continue;
        }
        
        $permission_name = $perm['permission_name'];
        $enabled = intval($perm['enabled']);
        
        error_log("Updating permission: {$permission_name} = {$enabled}");
        
        $sql = "UPDATE employee_permissions SET enabled = ? WHERE permission_name = ?";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param('is', $enabled, $permission_name);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $updated_count += $stmt->affected_rows;
        $stmt->close();
    }
    
    error_log("Updated {$updated_count} permissions");
    
    echo json_encode(['success' => true, 'message' => "Permissions updated successfully ({$updated_count} records)"]);
} catch (Exception $e) {
    error_log("Error in save-permissions: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
