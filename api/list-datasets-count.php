<?php
require_once __DIR__ . '/../db_config.php';

// Get all datasets with their record counts
$result = $conn->query('
    SELECT dataset_name, COUNT(*) as record_count 
    FROM delivery_records 
    WHERE dataset_name IS NOT NULL AND dataset_name != "" 
    GROUP BY dataset_name 
    ORDER BY record_count DESC
');

if ($result) {
    $datasets = [];
    while ($row = $result->fetch_assoc()) {
        $datasets[] = $row;
    }
    echo json_encode([
        'success' => true,
        'datasets' => $datasets,
        'total_datasets' => count($datasets)
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $conn->error
    ]);
}
?>
