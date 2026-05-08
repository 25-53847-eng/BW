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
$user_role = $_SESSION['user_role'] ?? 'admin';
$year = isset($_GET['year']) ? intval($_GET['year']) : null;
$dataset = isset($_GET['dataset']) ? trim($_GET['dataset']) : null;
$api_role = isset($_GET['role']) ? trim($_GET['role']) : $user_role;

header('Content-Type: application/json');

try {
    // Determine where clause based on user role
    // Admin: filter by owner_user_id (their own deliveries)
    // Employee: no owner_user_id filter (see all company data)
    // Both: filter by unit_type IN ('1a', '2a', '4a')
    if ($user_role === 'employee' || $api_role === 'employee') {
        $where_base = "WHERE delivery_date IS NOT NULL AND delivery_date != '' AND sold_to IS NOT NULL AND sold_to != '' AND unit_type IN ('1a', '2a', '4a')";
    } else {
        $where_base = "WHERE owner_user_id = ? AND delivery_date IS NOT NULL AND delivery_date != '' AND sold_to IS NOT NULL AND sold_to != '' AND unit_type IN ('1a', '2a', '4a')";
    }
    
    // If year is not specified or is 0, get available years from delivery_date
    if ($year === null) {
        // Return available years list
        $where = $where_base;
        $params = [];
        if ($user_role !== 'employee' && $api_role !== 'employee') {
            $params[] = $user_id;
        }
        
        if (!empty($dataset)) {
            $where .= " AND dataset_name = ?";
            $params[] = $dataset;
        }
        
        $sql = "SELECT DISTINCT YEAR(delivery_date) as year FROM delivery_records $where ORDER BY year DESC LIMIT 50";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'error' => 'Query prepare error', 'years' => [intval(date('Y'))]]);
            exit();
        }
        
        if (!empty($params)) {
            $typeStr = str_repeat('s', count($params));
            $stmt->bind_param($typeStr, ...$params);
        }
        
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'error' => 'Query execute error', 'years' => [intval(date('Y'))]]);
            $stmt->close();
            exit();
        }
        
        $result = $stmt->get_result();
        $years = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['year']) {
                $years[] = intval($row['year']);
            }
        }
        $stmt->close();
        
        // If no years found, add current year as fallback
        if (empty($years)) {
            $years = [intval(date('Y'))];
        }
        
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'years' => $years,
            'current_year' => intval(date('Y'))
        ]);
        exit();
    }
    
    // Get monthly sales data - if year is 0, aggregate ALL years; otherwise get specific year
    $months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    $monthly_sales = array_fill_keys($months, 0);
    
    if ($year === 0) {
        // Aggregate data from ALL years
        $where = $where_base;
    } else {
        // Get data for specific year
        if ($user_role === 'employee' || $api_role === 'employee') {
            $where = "WHERE YEAR(delivery_date) = ? AND delivery_date IS NOT NULL AND delivery_date != '' AND sold_to IS NOT NULL AND sold_to != '' AND unit_type IN ('1a', '2a', '4a')";
        } else {
            $where = "WHERE owner_user_id = ? AND YEAR(delivery_date) = ? AND delivery_date IS NOT NULL AND delivery_date != '' AND sold_to IS NOT NULL AND sold_to != '' AND unit_type IN ('1a', '2a', '4a')";
        }
    }
    
    $params = [];
    if ($user_role !== 'employee' && $api_role !== 'employee') {
        $params[] = $user_id;
    }
    if ($year !== 0) {
        $params[] = $year;
    }
    
    if (!empty($dataset)) {
        $where .= " AND dataset_name = ?";
        $params[] = $dataset;
    }
    
    $sql = "SELECT delivery_month, COALESCE(SUM(quantity), 0) AS total FROM delivery_records $where GROUP BY delivery_month";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Query prepare error']);
        exit();
    }
    
    if (!empty($params)) {
        $typeStr = str_repeat('s', count($params));
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
        'year' => $year === 0 ? 'all' : $year,
        'monthly_sales' => $monthly_sales
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
