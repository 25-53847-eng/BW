<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php', true, 302);
    exit;
}

require_once __DIR__ . '/../db_config.php';

// Get year distribution across all datasets
$sql = "
    SELECT 
        delivery_year as year,
        COUNT(*) as record_count,
        COUNT(DISTINCT dataset_name) as dataset_count,
        COUNT(CASE WHEN delivery_year = 0 THEN 1 END) as zero_year_count
    FROM delivery_records
    WHERE owner_user_id = ?
    GROUP BY delivery_year
    ORDER BY year DESC
";

$stmt = $conn->prepare($sql);
$user_id = intval($_SESSION['user_id']);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$years_data = [];
$total_records = 0;
$zero_year_records = 0;

while ($row = $result->fetch_assoc()) {
    $years_data[] = $row;
    $total_records += intval($row['record_count']);
    if (intval($row['year']) === 0) {
        $zero_year_records = intval($row['record_count']);
    }
}

// Check specific dataset year distribution
$selected_dataset = isset($_GET['dataset']) ? trim(strval($_GET['dataset'])) : null;
$dataset_detail = null;

if ($selected_dataset) {
    $sql2 = "
        SELECT 
            delivery_year as year,
            COUNT(*) as count,
            GROUP_CONCAT(DISTINCT company_name SEPARATOR ', ') as companies
        FROM delivery_records
        WHERE dataset_name = ? AND owner_user_id = ?
        GROUP BY delivery_year
        ORDER BY year DESC
    ";
    
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param('si', $selected_dataset, $user_id);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    
    $dataset_detail = [];
    while ($row = $result2->fetch_assoc()) {
        $dataset_detail[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'summary' => [
        'total_records' => $total_records,
        'records_with_zero_year' => $zero_year_records,
        'years_found' => count(array_filter($years_data, function($y) { return intval($y['year']) !== 0; }))
    ],
    'year_distribution' => $years_data,
    'dataset_detail' => $dataset_detail,
    'diagnostics' => [
        'issue' => $zero_year_records > 0 ? 'Found records with year=0. Year extraction may have failed for these rows.' : 'No issues found',
        'fix_applied' => 'Year extraction now defaults to current year if not found',
        'next_step' => $zero_year_records > 0 ? 'Re-upload Excel file. New import logic will assign current year to these records.' : 'Years are being extracted correctly'
    ]
]);
$stmt->close();
?>
