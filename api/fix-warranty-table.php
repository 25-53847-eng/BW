<?php
session_start();

// Check authentication
if (empty($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Not authenticated']));
}

require_once __DIR__ . '/../db_config.php';

try {
    // Fix warranty_replacements table
    $sql = "ALTER TABLE warranty_replacements MODIFY id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
    
    if ($conn->query($sql)) {
        echo json_encode([
            'success' => true,
            'message' => 'warranty_replacements table fixed successfully'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $conn->error
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
