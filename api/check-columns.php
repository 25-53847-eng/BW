<?php
/**
 * Check what columns are in your Excel uploads
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$report = [
    'delivery_records_columns' => [],
    'sample_import_record' => [],
    'question_for_user' => 'What is the ACTUAL QUANTITY column in your Excel?',
];

if ($conn instanceof mysqli) {
    // Get table structure
    $result = $conn->query("DESCRIBE delivery_records");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report['delivery_records_columns'][] = $row['Field'];
        }
    }
    
    // Sample inventory record
    $result = $conn->query("
        SELECT * FROM delivery_records 
        WHERE company_name = 'Stock Addition'
        LIMIT 1
    ");
    
    if ($result && $row = $result->fetch_assoc()) {
        $report['sample_import_record'] = $row;
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
