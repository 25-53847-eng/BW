<?php
/**
 * DELETE all current inventory (Stock Addition + Andison Manila from old import)
 * This clears out the bad data so we can re-import correctly
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$report = [
    'before_delete' => [],
    'deleted' => [],
    'after_delete' => [],
];

if ($conn instanceof mysqli) {
    // 1. Count before delete
    $result = $conn->query("
        SELECT company_name, COUNT(*) as cnt, SUM(quantity) as qty
        FROM delivery_records
        WHERE company_name IN ('Stock Addition', 'to Andison Manila')
        GROUP BY company_name
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report['before_delete'][$row['company_name']] = [
                'records' => intval($row['cnt']),
                'qty' => intval($row['qty'] ?? 0),
            ];
        }
    }
    
    // 2. DELETE Stock Addition inventory
    $stock_deleted = 0;
    $result = $conn->query("DELETE FROM delivery_records WHERE company_name = 'Stock Addition'");
    if ($result) {
        $stock_deleted = $conn->affected_rows;
        $report['deleted']['Stock Addition'] = $stock_deleted;
    }
    
    // 3. DELETE Andison Manila inventory  
    $andison_deleted = 0;
    $result = $conn->query("DELETE FROM delivery_records WHERE company_name = 'to Andison Manila'");
    if ($result) {
        $andison_deleted = $conn->affected_rows;
        $report['deleted']['to Andison Manila'] = $andison_deleted;
    }
    
    $report['deleted']['total'] = $stock_deleted + $andison_deleted;
    
    // 4. Verify deletion
    $result = $conn->query("
        SELECT company_name, COUNT(*) as cnt
        FROM delivery_records
        GROUP BY company_name
        LIMIT 10
    ");
    
    if ($result) {
        $report['after_delete']['remaining_companies'] = [];
        while ($row = $result->fetch_assoc()) {
            $report['after_delete']['remaining_companies'][] = [
                'company' => $row['company_name'],
                'records' => intval($row['cnt']),
            ];
        }
    }
    
    $report['status'] = 'INVENTORY CLEARED - Ready for fresh import!';
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
