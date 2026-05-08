<?php
ob_start();
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated', 'years' => [intval(date('Y'))]]);
    exit();
}

require_once __DIR__ . '/../db_config.php';

$user_id = intval($_SESSION['user_id']);

// Get distinct years from database - show all years to all users
$sql = "SELECT DISTINCT delivery_year FROM delivery_records WHERE delivery_year > 0 AND delivery_year < 2100 ORDER BY delivery_year DESC LIMIT 50";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Query prepare error: ' . $conn->error, 'years' => [intval(date('Y'))], 'current_year' => intval(date('Y'))]);
    exit();
}

// No bind_param needed since we're not filtering by user
if (!$stmt->execute()) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Query execute error: ' . $stmt->error, 'years' => [intval(date('Y'))]]);
    $stmt->close();
    exit();
}

$result = $stmt->get_result();

$years = [];
while ($row = $result->fetch_assoc()) {
    $years[] = intval($row['delivery_year']);
}
$stmt->close();

// If no years found, add current year as fallback
if (empty($years)) {
    $years = [intval(date('Y'))];
}

// Remove duplicates and sort
$years = array_unique($years);
rsort($years);

ob_end_clean();
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'years' => $years,
    'current_year' => intval(date('Y')),
    'count' => count($years)
]);
?>
