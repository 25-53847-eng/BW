<?php
ob_start();
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once __DIR__ . '/../db_config.php';

$user_id = intval($_SESSION['user_id']);
$user_role = $_SESSION['user_role'] ?? 'admin';
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$dataset = isset($_GET['dataset']) ? trim($_GET['dataset']) : '';

header('Content-Type: application/json');

try {
    $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    $monthly_sales = array_fill_keys($months, 0);
    
    // Build where clause based on role
    if ($user_role === 'employee') {
        $where = "WHERE YEAR(delivery_date) = ? AND delivery_month IS NOT NULL AND delivery_month != '' AND unit_type IN ('1a', '2a', '4a')";
        $params = [$year];
    } else {
        $where = "WHERE owner_user_id = ? AND YEAR(delivery_date) = ? AND delivery_month IS NOT NULL AND delivery_month != '' AND unit_type IN ('1a', '2a', '4a')";
        $params = [$user_id, $year];
    }
    
    // Add dataset filter if provided
    if (!empty($dataset)) {
        $where .= " AND dataset_name = ?";
        $params[] = $dataset;
    }
    
    $sql = "SELECT delivery_month, COALESCE(SUM(quantity),0) AS total FROM delivery_records $where GROUP BY delivery_month";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Query prepare error']);
        exit();
    }
    
    if (!empty($params)) {
        $typeStr = str_repeat('i', min(2, count($params))) . (count($params) > 2 ? 's' : '');
        $stmt->bind_param($typeStr, ...$params);
    }
    
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'error' => 'Query execute error']);
        $stmt->close();
        exit();
    }
    
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (array_key_exists($row['delivery_month'], $monthly_sales)) {
            $monthly_sales[$row['delivery_month']] = intval($row['total']);
        }
    }
    $stmt->close();
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'year' => $year,
        'monthly_sales' => $monthly_sales
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
