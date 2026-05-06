<?php
require_once __DIR__ . '/../db_config.php';

// Get all unique datasets
$result = $conn->query('SELECT DISTINCT dataset_name FROM delivery_records WHERE dataset_name IS NOT NULL AND dataset_name != "" ORDER BY dataset_name');

if ($result) {
    $datasets = [];
    while ($row = $result->fetch_assoc()) {
        $datasets[] = $row['dataset_name'];
    }
    echo json_encode([
        'success' => true,
        'datasets' => $datasets,
        'total' => count($datasets)
    ], JSON_PRETTY_PRINT);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $conn->error
    ]);
}
?>
