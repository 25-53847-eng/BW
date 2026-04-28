<?php
/**
 * Update database views to ensure proper separation between
 * Inquiry, Purchase Orders, Inventory, and Delivery Records
 */

require_once dirname(__DIR__) . '/db_config.php';

$viewsToUpdate = [
    'vw_inquiry' => "
        SELECT *
        FROM `delivery_records`
        WHERE `company_name` = 'Inquiry'
          AND (COALESCE(`po_status`, '') = '' OR `po_status` IN ('No PO', 'Pending'))
    ",
    'vw_purchase_orders' => "
        SELECT *
        FROM `delivery_records`
        WHERE `company_name` IN ('Orders', 'Purchase Order')
    ",
    'vw_delivery' => "
        SELECT *
        FROM `delivery_records`
        WHERE `company_name` NOT IN ('Orders', 'Purchase Order', 'Inquiry', 'Stock Addition')
          AND LOWER(TRIM(COALESCE(`company_name`, ''))) NOT IN ('andison manila', 'to andison manila')
          AND LOWER(TRIM(COALESCE(`sold_to`, ''))) NOT IN ('andison manila', 'to andison manila')
    "
];

$results = [];
$errors = [];

foreach ($viewsToUpdate as $viewName => $viewDefinition) {
    $sql = "CREATE OR REPLACE VIEW `$viewName` AS " . trim($viewDefinition);
    
    if ($conn->query($sql)) {
        $results[] = "✓ Updated view: $viewName";
    } else {
        $errors[] = "✗ Failed to update view '$viewName': " . $conn->error;
    }
}

// Output results
header('Content-Type: application/json');

$response = [
    'success' => count($errors) === 0,
    'results' => $results,
    'errors' => $errors,
    'timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
