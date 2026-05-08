<?php
/**
 * Show detailed import analysis
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../db_config.php';

$report = [
    'expectation' => [
        'stock_in_manila_qty' => 3799,
        'inventory_qty' => 21110,
        'total_qty' => 24909,
        'total_records' => 1946,
    ],
    'actual' => [],
    'missing' => [],
    'by_item_analysis' => [],
];

if ($conn instanceof mysqli) {
    // Get current inventory totals
    $result = $conn->query("
        SELECT 
            company_name,
            COUNT(*) as records,
            SUM(quantity) as total_qty,
            COUNT(DISTINCT item_code) as unique_items
        FROM delivery_records
        WHERE company_name IN ('Stock Addition', 'to Andison Manila')
        GROUP BY company_name
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $company = $row['company_name'];
            $count = intval($row['records']);
            $qty = intval($row['total_qty'] ?? 0);
            
            $report['actual'][$company] = [
                'records' => $count,
                'qty' => $qty,
                'unique_items' => intval($row['unique_items']),
            ];
            
            // Calculate what's missing
            if ($company === 'to Andison Manila') {
                $report['missing'][$company] = [
                    'expected_qty' => 3799,
                    'actual_qty' => $qty,
                    'missing_qty' => 3799 - $qty,
                    'percent_missing' => round(((3799 - $qty) / 3799) * 100, 1),
                ];
            } else {
                $report['missing'][$company] = [
                    'expected_qty' => 21110,
                    'actual_qty' => $qty,
                    'missing_qty' => 21110 - $qty,
                    'percent_missing' => round(((21110 - $qty) / 21110) * 100, 1),
                ];
            }
        }
    }
    
    // Top items by record count (might show if there are duplicates being consolidated)
    $result = $conn->query("
        SELECT 
            item_code,
            company_name,
            COUNT(*) as record_count,
            SUM(quantity) as total_qty,
            COUNT(DISTINCT quantity) as qty_variants
        FROM delivery_records
        WHERE company_name IN ('Stock Addition', 'to Andison Manila')
        GROUP BY item_code, company_name
        HAVING record_count > 5
        ORDER BY record_count DESC
        LIMIT 10
    ");
    
    if ($result) {
        $report['by_item_analysis']['high_duplicate_items'] = [];
        while ($row = $result->fetch_assoc()) {
            $report['by_item_analysis']['high_duplicate_items'][] = [
                'item' => $row['item_code'],
                'company' => $row['company_name'],
                'records' => intval($row['record_count']),
                'total_qty' => intval($row['total_qty']),
                'qty_variants' => intval($row['qty_variants']),
            ];
        }
    }
    
    // Check if rows with same item_code/company/qty exist (true duplicates)
    $result = $conn->query("
        SELECT 
            item_code,
            company_name,
            quantity,
            COUNT(*) as dup_count
        FROM delivery_records
        WHERE company_name IN ('Stock Addition', 'to Andison Manila')
        GROUP BY item_code, company_name, quantity
        HAVING dup_count > 3
        ORDER BY dup_count DESC
        LIMIT 5
    ");
    
    if ($result) {
        $report['by_item_analysis']['potential_exact_duplicates'] = [];
        while ($row = $result->fetch_assoc()) {
            $report['by_item_analysis']['potential_exact_duplicates'][] = [
                'item' => $row['item_code'],
                'company' => $row['company_name'],
                'qty' => intval($row['quantity']),
                'times_repeated' => intval($row['dup_count']),
            ];
        }
    }
}

echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
