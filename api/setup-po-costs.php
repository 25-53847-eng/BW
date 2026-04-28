<?php
header('Content-Type: application/json');
session_start();

if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../db_config.php';

$errors = [];

// Add peso_cost column if it doesn't exist
$checkPesoCost = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'delivery_records' AND COLUMN_NAME = 'peso_cost'");
if ($checkPesoCost->num_rows === 0) {
    if (!$conn->query("ALTER TABLE delivery_records ADD COLUMN peso_cost DECIMAL(15,2) DEFAULT 0 AFTER po_number")) {
        $errors[] = "Failed to add peso_cost column: " . $conn->error;
    }
}

// Add foreign_cost column if it doesn't exist
$checkForeignCost = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'delivery_records' AND COLUMN_NAME = 'foreign_cost'");
if ($checkForeignCost->num_rows === 0) {
    if (!$conn->query("ALTER TABLE delivery_records ADD COLUMN foreign_cost DECIMAL(15,2) DEFAULT 0 AFTER peso_cost")) {
        $errors[] = "Failed to add foreign_cost column: " . $conn->error;
    }
}

if (empty($errors)) {
    echo json_encode(['success' => true, 'message' => 'Database schema updated successfully']);
} else {
    echo json_encode(['success' => false, 'errors' => $errors]);
}
?>
