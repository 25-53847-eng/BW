<?php
/**
 * API endpoint to get all client companies
 * Returns JSON array of unique client companies from delivery_records
 */
session_start();

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../db_config.php';
require_once '../helpers/client-helper.php';

header('Content-Type: application/json');

try {
    // Get dataset filter from session
    $dataset_filter = "";
    $dataset_filter_params = [];
    
    $selected_dataset = isset($_SESSION['active_dataset']) ? trim($_SESSION['active_dataset']) : 'all';
    
    if ($selected_dataset !== 'all' && $selected_dataset !== '') {
        $dataset_filter = " AND dataset_name = ?";
        $dataset_filter_params[] = $selected_dataset;
    }
    
    // Get all unique client companies with stats
    $companies = getClientCompaniesWithStats($conn, $dataset_filter, $dataset_filter_params);
    
    // Extract just the company names and sort alphabetically
    $companyNames = array_column($companies, 'company_name');
    sort($companyNames);
    
    echo json_encode([
        'success' => true,
        'companies' => $companyNames,
        'count' => count($companyNames)
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve companies',
        'details' => $e->getMessage()
    ]);
}
?>
