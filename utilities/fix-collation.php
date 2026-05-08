<?php
/**
 * Fix Collation Mismatch
 * Converts all delivery_records table columns to use utf8mb4_unicode_ci
 * Run this once to resolve: "Illegal mix of collations"
 */

require_once '../db_config.php';

echo "Starting collation fix...\n";

try {
    // 1. Fix the table-level collation
    $conn->query("ALTER TABLE delivery_records CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "? Table collation fixed\n";

    // 2. Fix each VARCHAR/TEXT column explicitly
    $columns_to_fix = [
        'delivery_month', 'item_code', 'item_name', 'company_name', 'sold_to',
        'status', 'highlight_color', 'cell_styles', 'notes', 'order_customer',
        'po_number', 'po_status', 'groupings', 'dataset_name'
    ];

    foreach ($columns_to_fix as $col) {
        $conn->query("ALTER TABLE delivery_records MODIFY $col VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }
    echo "? All columns collation fixed\n";

    // 3. Fix TEXT columns
    $conn->query("ALTER TABLE delivery_records MODIFY notes TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->query("ALTER TABLE delivery_records MODIFY cell_styles LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "? TEXT columns fixed\n";

    echo "\n? Collation fix complete! Sales should now save correctly.\n";

} catch (Exception $e) {
    echo "? Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>


