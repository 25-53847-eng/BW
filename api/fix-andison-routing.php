<?php
/**
 * Fix Andison Manila routing - move misplaced records to correct location
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$report = [
    'before' => [],
    'action_taken' => [],
    'after' => [],
    'status' => 'pending'
];

if ($conn instanceof mysqli) {
    // 1. Check current state BEFORE
    $result = $conn->query("
        SELECT company_name, COUNT(*) as cnt, SUM(quantity) as qty
        FROM delivery_records
        WHERE company_name IN ('Andison Industrial', 'to Andison Manila')
        GROUP BY company_name
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report['before'][$row['company_name']] = [
                'records' => intval($row['cnt']),
                'qty' => intval($row['qty'] ?? 0),
            ];
        }
    }
    
    // 2. Check how many Andison Industrial records have Andison Manila indicators
    $result = $conn->query("
        SELECT COUNT(*) as cnt, SUM(quantity) as qty
        FROM delivery_records
        WHERE company_name = 'Andison Industrial'
        AND (
            LOWER(TRIM(COALESCE(sold_to, ''))) IN ('andison manila', 'to andison manila', 'stock in manila', 'andison manila use')
            OR LOWER(TRIM(COALESCE(sold_to, ''))) LIKE '%stock in manila%'
            OR LOWER(TRIM(COALESCE(company_name, ''))) LIKE '%stock in manila%'
        )
    ");
    
    if ($result) {
        $row = $result->fetch_assoc();
        $to_move = intval($row['cnt']);
        $move_qty = intval($row['qty'] ?? 0);
        
        $report['action_taken']['records_to_move'] = $to_move;
        $report['action_taken']['qty_to_move'] = $move_qty;
        $report['action_taken']['reason'] = 'Andison Industrial records with Andison Manila indicators in sold_to field';
    }
    
    // 3. Actually move them
    if ($to_move > 0) {
        $update_sql = "UPDATE delivery_records 
                      SET company_name = 'to Andison Manila'
                      WHERE company_name = 'Andison Industrial'
                      AND (
                        LOWER(TRIM(COALESCE(sold_to, ''))) IN ('andison manila', 'to andison manila', 'stock in manila', 'andison manila use')
                        OR LOWER(TRIM(COALESCE(sold_to, ''))) LIKE '%stock in manila%'
                      )";
        
        if ($conn->query($update_sql)) {
            $report['action_taken']['status'] = 'SUCCESS - Records moved!';
        } else {
            $report['action_taken']['status'] = 'FAILED - ' . $conn->error;
        }
    }
    
    // 4. Check state AFTER
    $result = $conn->query("
        SELECT company_name, COUNT(*) as cnt, SUM(quantity) as qty
        FROM delivery_records
        WHERE company_name IN ('Andison Industrial', 'to Andison Manila', 'Stock Addition')
        GROUP BY company_name
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report['after'][$row['company_name']] = [
                'records' => intval($row['cnt']),
                'qty' => intval($row['qty'] ?? 0),
            ];
        }
        $report['status'] = 'completed';
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
