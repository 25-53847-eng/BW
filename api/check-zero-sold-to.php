<?php
/**
 * Check what's in the "0" sold_to records
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$report = [
    'zero_records' => [],
    'sample_zero_records' => [],
    'company_distribution' => [],
];

if ($conn instanceof mysqli) {
    // 1. Count records where sold_to = 0 or '0'
    $result = $conn->query("
        SELECT COUNT(*) as cnt, SUM(quantity) as qty
        FROM delivery_records
        WHERE CAST(sold_to AS CHAR) = '0' 
           OR sold_to = 0
           OR CAST(sold_to AS UNSIGNED) = 0
    ");
    
    if ($result) {
        $row = $result->fetch_assoc();
        $report['zero_records']['total_count'] = intval($row['cnt']);
        $report['zero_records']['total_qty'] = intval($row['qty'] ?? 0);
    }
    
    // 2. Sample records
    $result = $conn->query("
        SELECT id, invoice_no, item_code, company_name, sold_to, status
        FROM delivery_records
        WHERE CAST(sold_to AS CHAR) = '0' 
           OR sold_to = 0
           OR CAST(sold_to AS UNSIGNED) = 0
        LIMIT 10
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report['sample_zero_records'][] = [
                'id' => $row['id'],
                'invoice' => $row['invoice_no'],
                'item' => $row['item_code'],
                'company' => $row['company_name'],
                'sold_to_raw' => $row['sold_to'],
                'status' => $row['status'],
            ];
        }
    }
    
    // 3. How many of these "0" records are in each company?
    $result = $conn->query("
        SELECT company_name, COUNT(*) as cnt
        FROM delivery_records
        WHERE CAST(sold_to AS CHAR) = '0'
           OR sold_to = 0
        GROUP BY company_name
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report['company_distribution'][] = [
                'company' => $row['company_name'],
                'count' => intval($row['cnt']),
            ];
        }
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
