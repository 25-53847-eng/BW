<?php
/**
 * Analyze Excel import by "Sold To" column values
 * Matching against what user's screenshot shows
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$report = [
    'excel_distribution' => [
        'blank_or_empty' => 1213,
        'stock_in_manila' => 63,
        'inventory' => 670,
        'total' => 1946,
    ],
    'database_actual' => [],
    'analysis' => [],
];

if ($conn instanceof mysqli) {
    // 1. Count records by Sold To value in database
    $result = $conn->query("
        SELECT 
            CASE 
                WHEN TRIM(COALESCE(sold_to, '')) = '' THEN '[BLANKS/EMPTY]'
                WHEN LOWER(TRIM(sold_to)) LIKE '%stock in manila%' THEN 'Stock in Manila'
                WHEN LOWER(TRIM(sold_to)) = 'inventory' THEN 'INVENTORY'
                ELSE 'OTHER: ' || CONCAT(SUBSTRING(sold_to, 1, 50))
            END as sold_to_category,
            COUNT(*) as cnt,
            SUM(quantity) as qty
        FROM delivery_records
        WHERE 1=1
        GROUP BY 
            CASE 
                WHEN TRIM(COALESCE(sold_to, '')) = '' THEN '[BLANKS/EMPTY]'
                WHEN LOWER(TRIM(sold_to)) LIKE '%stock in manila%' THEN 'Stock in Manila'
                WHEN LOWER(TRIM(sold_to)) = 'inventory' THEN 'INVENTORY'
                ELSE 'OTHER'
            END
        ORDER BY cnt DESC
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $report['database_actual'][] = [
                'category' => $row['sold_to_category'],
                'records' => intval($row['cnt']),
                'qty' => intval($row['qty'] ?? 0),
            ];
        }
    }
    
    // 2. Check what happened to each category
    $report['analysis']['blanks_in_db'] = [
        'excel_count' => 1213,
        'expected_destination' => 'Stock Addition (inventory)',
        'actual_in_db' => 'See database counts above'
    ];
    
    $report['analysis']['stock_in_manila_in_db'] = [
        'excel_count' => 63,
        'expected_destination' => 'to Andison Manila',
        'actual_in_db' => '12 found in "to Andison Manila"',
        'missing_count' => 51,
        'issue' => 'Only 12 of 63 imported correctly'
    ];
    
    $report['analysis']['inventory_in_db'] = [
        'excel_count' => 670,
        'expected_destination' => 'Stock Addition?',
        'question' => 'Is INVENTORY a separate indicator or same as blank?'
    ];
    
    // 3. Check what company_name values contain "andison" or similar
    $result = $conn->query("
        SELECT company_name, COUNT(*) as cnt
        FROM delivery_records
        WHERE LOWER(TRIM(COALESCE(company_name, ''))) LIKE '%andison%'
           OR LOWER(TRIM(COALESCE(company_name, ''))) LIKE '%manila%'
        GROUP BY company_name
    ");
    
    if ($result) {
        $report['analysis']['andison_variants_in_db'] = [];
        while ($row = $result->fetch_assoc()) {
            $report['analysis']['andison_variants_in_db'][] = [
                'company_name' => $row['company_name'],
                'records' => intval($row['cnt']),
            ];
        }
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
