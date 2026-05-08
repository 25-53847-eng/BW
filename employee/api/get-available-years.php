<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

require_once __DIR__ . '/../db_config.php';

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

// Get distinct years from delivery_records
$sql = "
    SELECT DISTINCT delivery_year as year
    FROM delivery_records
    WHERE delivery_year IS NOT NULL AND delivery_year > 0" . $dataset_filter . "
    ORDER BY year DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error', 'years' => [intval(date('Y'))], 'current_year' => intval(date('Y'))]);
    exit();
}

$typeStr = str_repeat('s', count($dataset_filter_params));
if (!empty($dataset_filter_params)) {
    $stmt->bind_param($typeStr, ...$dataset_filter_params);
}
$stmt->execute();
$result = $stmt->get_result();

$years = [];
while ($row = $result->fetch_assoc()) {
    if ($row['year']) {
        $years[] = intval($row['year']);
    }
}
$stmt->close();

// If still no years, try extracting from created_at timestamp
if (empty($years)) {
    $sql2 = "
        SELECT DISTINCT YEAR(created_at) as year
        FROM delivery_records
        WHERE created_at IS NOT NULL" . $dataset_filter . "
        ORDER BY year DESC
    ";
    
    $stmt2 = $conn->prepare($sql2);
    if ($stmt2) {
        $typeStr = str_repeat('s', count($dataset_filter_params));
        if (!empty($dataset_filter_params)) {
            $stmt2->bind_param($typeStr, ...$dataset_filter_params);
        }
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        while ($row = $result2->fetch_assoc()) {
            if ($row['year']) {
                $years[] = intval($row['year']);
            }
        }
        $stmt2->close();
    }
}

// If still no years found, use current year
if (empty($years)) {
    $years = [intval(date('Y'))];
}

// Remove duplicates and sort descending
$years = array_unique($years);
rsort($years);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'years' => $years,
    'current_year' => intval(date('Y'))
]);
?>
