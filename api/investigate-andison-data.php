<?php
/**
 * Investigate what's in sold_to field for Andison Industrial records
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$report = [
    'andison_industrial_analysis' => [],
    'sample_records' => [],
    'sold_to_distribution' => [],
];

if ($conn instanceof mysqli) {
    // 1. What are the sold_to values in Andison Industrial?
    $result = $conn->query("
        SELECT 
            COALESCE(NULLIF(TRIM(sold_to), ''), '[EMPTY]') as sold_to_value,
            COUNT(*) as cnt,
            SUM(quantity) as qty
        FROM delivery_records
        WHERE company_name = 'Andison Industrial'
        GROUP BY sold_to_value
        ORDER BY cnt DESC
        LIMIT 20
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report['sold_to_distribution'][] = [
                'sold_to' => $row['sold_to_value'],
                'records' => intval($row['cnt']),
                'qty' => intval($row['qty'] ?? 0),
            ];
        }
    }
    
    // 2. Sample records
    $result = $conn->query("
        SELECT id, invoice_no, item_code, sold_to, company_name, status
        FROM delivery_records
        WHERE company_name = 'Andison Industrial'
        LIMIT 15
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report['sample_records'][] = [
                'invoice' => $row['invoice_no'],
                'item' => $row['item_code'],
                'sold_to' => $row['sold_to'] ?: '[EMPTY]',
                'company' => $row['company_name'],
                'status' => $row['status'],
            ];
        }
    }
    
    // 3. Check the original andison-manila.php query to understand how it identifies Andison Manila
    $result = $conn->query("
        SELECT COUNT(*) as cnt, SUM(quantity) as qty
        FROM delivery_records
        WHERE (
            company_name = 'to Andison Manila'
            OR transferred_to = 'to Andison Manila'
            OR LOWER(TRIM(COALESCE(sold_to, ''))) IN ('andison manila', 'to andison manila', 'stock in manila', 'andison manila use')
            OR LOWER(TRIM(COALESCE(sold_to, ''))) LIKE '%stock in manila%'
            OR LOWER(TRIM(COALESCE(company_name, ''))) LIKE '%stock in manila%'
        )
    ");
    
    if ($result) {
        $row = $result->fetch_assoc();
        $report['andison_industrial_analysis']['total_by_andison_query'] = [
            'records' => intval($row['cnt']),
            'qty' => intval($row['qty'] ?? 0),
        ];
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
