<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_config.php';

// For CLI execution, bypass authentication
$isCLI = php_sapi_name() === 'cli';

try {
    // Create permissions table
    $sql = "CREATE TABLE IF NOT EXISTS employee_permissions (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        permission_name VARCHAR(100) NOT NULL UNIQUE,
        permission_label VARCHAR(255) NOT NULL,
        enabled TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    $conn->query($sql);
    
    // Define all available permissions
    $permissions = [
        ['inquiry_add_records', 'Inquiry (add records)'],
        ['inquiry_edit_records', 'Inquiry (edit records)'],
        ['inquiry_delete_records', 'Inquiry (delete records)'],
        ['delivery_add_records', 'Delivery Records (add records)'],
        ['delivery_edit_records', 'Delivery Records (edit records)'],
        ['delivery_delete_records', 'Delivery Records (delete records)'],
        ['inventory_add_item', 'Inventory (add new item)'],
        ['inventory_edit_item', 'Inventory (edit item)'],
        ['inventory_delete_item', 'Inventory (delete item)'],
        ['inventory_create_po', 'Purchase Orders (create/edit/delete purchase orders)'],
        ['andison_add_inventory', 'Andison Manila (add inventory)'],
        ['andison_add_records', 'Andison Manila (add records)'],
        ['sales_add_records', 'Sales (add records)'],
        ['warranty_manage_records', 'Warranty Items (add/edit/delete records)'],
        ['upload_data', 'Upload Data']
    ];
    
    // Insert default permissions if they don't exist
    $stmt = $conn->prepare("INSERT IGNORE INTO employee_permissions (permission_name, permission_label, enabled) VALUES (?, ?, ?)");
    
    foreach ($permissions as $perm) {
        $stmt->bind_param('ssi', $perm[0], $perm[1], $enabled);
        $enabled = 0;
        $stmt->execute();
    }
    
    $stmt->close();
    
    echo json_encode(['success' => true, 'message' => 'Permissions table created and initialized']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
