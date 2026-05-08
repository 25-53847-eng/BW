<?php
/**
 * Shared helper functions for client company calculations
 * Used by dashboard, client-companies page, and employee dashboard
 */

/**
 * Get the SQL expression to resolve client company names
 * Uses company_name (excluding internal categories only, NOT to Andison Manila)
 */
function getClientCompanyExpr(): string {
    return "NULLIF(TRIM(CASE
                WHEN company_name IS NOT NULL AND company_name != '' AND company_name NOT IN ('Stock Addition', 'Orders', 'Delivery Records', 'Andison Manila') THEN company_name
                ELSE ''
            END), '')";
}

/**
 * Count unique client companies with optional dataset filter
 * @param mysqli $conn Database connection
 * @param string $datasetFilter Additional WHERE clause (e.g., " AND dataset_name = ?")
 * @param array $filterParams Bind parameters for the filter
 * @return int Total unique client companies
 */
function countClientCompanies($conn, $datasetFilter = '', $filterParams = []) {
    // Get the client company expression
    $clientCompanyExpr = getClientCompanyExpr();
    
    // Count distinct companies using the same exclusion logic as the page
    $sql = "SELECT COUNT(DISTINCT {$clientCompanyExpr}) as total 
            FROM delivery_records 
            WHERE {$clientCompanyExpr} IS NOT NULL" . $datasetFilter;
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }
    
    if (!empty($filterParams)) {
        $typeStr = str_repeat('s', count($filterParams));
        $stmt->bind_param($typeStr, ...$filterParams);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return intval($row['total'] ?? 0);
}

/**
 * Get all unique client companies with stats (for client-companies.php)
 * @param mysqli $conn Database connection
 * @param string $datasetFilter Additional WHERE clause
 * @param array $filterParams Bind parameters
 * @return array Array of company records with stats
 */
function getClientCompaniesWithStats($conn, $datasetFilter = '', $filterParams = []) {
    $companies = [];
    $clientCompanyExpr = getClientCompanyExpr();
    
    $sql = "
        SELECT 
            {$clientCompanyExpr} as company_name,
            COUNT(*) as total_orders,
            SUM(quantity) as total_units,
            COUNT(DISTINCT item_code) as unique_products,
            MAX(delivery_date) as last_delivery,
            MAX(delivery_month) as last_month
        FROM delivery_records 
        WHERE {$clientCompanyExpr} IS NOT NULL" . $datasetFilter . "
        GROUP BY {$clientCompanyExpr}
        ORDER BY total_units DESC
    ";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    
    if (!empty($filterParams)) {
        $typeStr = str_repeat('s', count($filterParams));
        $stmt->bind_param($typeStr, ...$filterParams);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $companies[] = $row;
    }
    
    $stmt->close();
    return $companies;
}
?>
