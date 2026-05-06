<?php
require_once __DIR__ . '/../db_config.php';

// Delete all records from the specified dataset
$dataset_name = '2024 TO NOW BW SALES RECORD';

$stmt = $conn->prepare('DELETE FROM delivery_records WHERE dataset_name = ?');
$stmt->bind_param('s', $dataset_name);

if ($stmt->execute()) {
    $affected_rows = $stmt->affected_rows;
    echo json_encode([
        'success' => true,
        'message' => "Dataset '$dataset_name' deleted successfully",
        'deleted_records' => $affected_rows
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting dataset: ' . $conn->error
    ]);
}
?>
