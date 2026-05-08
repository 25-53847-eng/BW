<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once __DIR__ . '/../db_config.php';

// Get year from request
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$selected_dataset = isset($_GET['dataset']) ? trim(strval($_GET['dataset'])) : null;

// Convert empty string to null for cleaner logic
if ($selected_dataset === '') {
    $selected_dataset = null;
}

// Build dataset filter for queries
$dataset_filter = ' AND company_name != ?';
$dataset_filter_params = ['Stock Addition'];
if (!empty($selected_dataset)) {
    $dataset_filter .= ' AND dataset_name = ?';
    $dataset_filter_params[] = $selected_dataset;
}

// Get monthly data for the specified year
$months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
$monthly_sales = array_fill_keys($months, 0);

// Get monthly data for specified year
$sql = "
    SELECT delivery_month, COALESCE(SUM(quantity), 0) AS total
    FROM delivery_records
    WHERE delivery_year = ? " . $dataset_filter . "
    GROUP BY delivery_month
";

$params = [$year];
$params = array_merge($params, $dataset_filter_params);

$stmt = $conn->prepare($sql);
if ($stmt) {
    $typeStr = str_repeat('i', 1) . str_repeat('s', count($dataset_filter_params));
    $stmt->bind_param($typeStr, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (array_key_exists($row['delivery_month'], $monthly_sales)) {
            $monthly_sales[$row['delivery_month']] = intval($row['total']);
        }
    }
    $stmt->close();
}

// Convert to array values for chart
$data = array_values($monthly_sales);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'year' => $year,
    'monthly_sales' => $data,
    'total' => array_sum($data)
]);
?>
