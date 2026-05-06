<?php
session_start();

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo "Not logged in\n";
    exit();
}

echo "Logged in as User ID: " . $_SESSION['user_id'] . "\n";
echo "Role: " . ($_SESSION['user_role'] ?? 'Not set') . "\n";

// Try to access the employee dashboard query
require '../db_config.php';

// Same filter as employee/index.php
$selected_dataset = null;
$dataset_filter = ' AND company_name != ?';
$dataset_filter_params = ['Stock Addition'];

// Test query
$sql = "SELECT COALESCE(SUM(quantity), 0) as total FROM delivery_records WHERE status = 'Delivered'" . $dataset_filter;
$stmt = $conn->prepare($sql);
if ($stmt) {
    $typeStr = 's';
    $stmt->bind_param($typeStr, ...$dataset_filter_params);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        echo "Total Delivered: " . $row['total'] . "\n";
    }
    $stmt->close();
} else {
    echo "Error: " . $conn->error . "\n";
}
?>

