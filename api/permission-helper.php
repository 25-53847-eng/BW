<?php
/**
 * Permission Helper Functions
 * Used to check employee permissions set by admin
 */

/**
 * Check if a specific permission is enabled for employees
 * @param string $permission_name The permission identifier
 * @param object $conn Database connection
 * @return bool True if permission is enabled
 */
function isPermissionEnabled($permission_name, $conn) {
    // Determine if this is being called from an employee context
    // Employee pages are in /employee/ folder
    $scriptPath = $_SERVER['SCRIPT_FILENAME'] ?? '';
    $isEmployeeContext = strpos($scriptPath, '/employee/') !== false || strpos($scriptPath, '\\employee\\') !== false;
    
    // If NOT in employee context (admin pages), always allow
    if (!$isEmployeeContext) {
        return true;
    }
    
    // For employee pages, ALWAYS check the database permission
    $stmt = $conn->prepare("SELECT enabled FROM employee_permissions WHERE permission_name = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param('s', $permission_name);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return false;
    }
    
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return (bool)$row['enabled'];
}

/**
 * Get all enabled permissions for employees
 * @param object $conn Database connection
 * @return array Array of enabled permission names
 */
function getEnabledPermissions($conn) {
    $stmt = $conn->prepare("SELECT permission_name FROM employee_permissions WHERE enabled = 1");
    if (!$stmt) {
        return [];
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $permissions = [];
    while ($row = $result->fetch_assoc()) {
        $permissions[] = $row['permission_name'];
    }
    
    $stmt->close();
    return $permissions;
}
?>
