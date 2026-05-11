<?php
/**
 * Query script to find XT-PC1 inventory items
 * Usage: Run from command line: php query-xt-pc1-items.php
 */

require_once 'db_config.php';

echo "\n" . str_repeat("=", 80) . "\n";
echo "XT-PC1 INVENTORY ITEM QUERY\n";
echo str_repeat("=", 80) . "\n\n";

// 1. Count all XT-PC1 items (where company_name = 'Stock Addition')
echo "1. TOTAL COUNT OF XT-PC1 INVENTORY ITEMS:\n";
echo str_repeat("-", 80) . "\n";

$countQuery = "
    SELECT COUNT(*) as total_count, SUM(quantity) as total_quantity
    FROM delivery_records
    WHERE item_code = 'XT-PC1'
    AND company_name = 'Stock Addition'
";

$countResult = $conn->query($countQuery);
if ($countResult) {
    $countRow = $countResult->fetch_assoc();
    echo "Total Records: " . ($countRow['total_count'] ?? 0) . "\n";
    echo "Total Quantity: " . ($countRow['total_quantity'] ?? 0) . "\n\n";
} else {
    echo "Error: " . $conn->error . "\n\n";
}

// 2. Show details of each XT-PC1 item
echo "2. DETAILED LIST OF ALL XT-PC1 INVENTORY ITEMS:\n";
echo str_repeat("-", 80) . "\n";

$detailsQuery = "
    SELECT 
        id,
        item_code,
        item_name,
        quantity,
        status,
        inventory_status,
        box_code,
        model_no,
        groupings,
        uom,
        delivery_year,
        delivery_month,
        company_name,
        created_at,
        updated_at,
        owner_user_id,
        notes
    FROM delivery_records
    WHERE item_code = 'XT-PC1'
    AND company_name = 'Stock Addition'
    ORDER BY created_at DESC
";

$detailsResult = $conn->query($detailsQuery);
if ($detailsResult && $detailsResult->num_rows > 0) {
    $rowNum = 1;
    while ($row = $detailsResult->fetch_assoc()) {
        echo "\n▼ Item #" . $rowNum . ":\n";
        echo "  ID:                " . $row['id'] . "\n";
        echo "  Item Code:         " . $row['item_code'] . "\n";
        echo "  Item Name:         " . $row['item_name'] . "\n";
        echo "  Quantity:          " . $row['quantity'] . "\n";
        echo "  Status:            " . $row['status'] . "\n";
        echo "  Inventory Status:  " . ($row['inventory_status'] ?? 'NULL') . "\n";
        echo "  Box Code:          " . ($row['box_code'] ?? 'NULL') . "\n";
        echo "  Model No:          " . ($row['model_no'] ?? 'NULL') . "\n";
        echo "  Groupings:         " . ($row['groupings'] ?? 'NULL') . "\n";
        echo "  UOM:               " . ($row['uom'] ?? 'NULL') . "\n";
        echo "  Delivery Year:     " . ($row['delivery_year'] ?? 'NULL') . "\n";
        echo "  Delivery Month:    " . ($row['delivery_month'] ?? 'NULL') . "\n";
        echo "  Company:           " . $row['company_name'] . "\n";
        echo "  Owner User ID:     " . $row['owner_user_id'] . "\n";
        echo "  Created:           " . $row['created_at'] . "\n";
        echo "  Updated:           " . $row['updated_at'] . "\n";
        echo "  Notes:             " . ($row['notes'] ?? 'NULL') . "\n";
        $rowNum++;
    }
    echo "\n";
} else {
    echo "No active XT-PC1 items found.\n\n";
}

// 3. Check for XT-PC1 items with different statuses
echo "\n3. XT-PC1 ITEMS BY STATUS:\n";
echo str_repeat("-", 80) . "\n";

$statusQuery = "
    SELECT 
        status,
        COUNT(*) as count,
        SUM(quantity) as total_qty
    FROM delivery_records
    WHERE item_code = 'XT-PC1'
    AND company_name = 'Stock Addition'
    GROUP BY status
    ORDER BY count DESC
";

$statusResult = $conn->query($statusQuery);
if ($statusResult && $statusResult->num_rows > 0) {
    echo "Status Breakdown:\n";
    while ($row = $statusResult->fetch_assoc()) {
        echo "  • " . $row['status'] . ": " . $row['count'] . " record(s), Qty: " . $row['total_qty'] . "\n";
    }
} else {
    echo "No status data found.\n";
}

// 4. Summary Report
echo "\n4. SUMMARY REPORT:\n";
echo str_repeat("-", 80) . "\n";

$summaryQuery = "
    SELECT 
        COUNT(*) as total_records,
        SUM(quantity) as total_quantity,
        COUNT(DISTINCT owner_user_id) as owner_count,
        MIN(created_at) as first_added,
        MAX(updated_at) as last_updated
    FROM delivery_records
    WHERE item_code = 'XT-PC1'
    AND company_name = 'Stock Addition'
";

$summaryResult = $conn->query($summaryQuery);
if ($summaryResult) {
    $summary = $summaryResult->fetch_assoc();
    echo "✓ Total XT-PC1 Records:    " . ($summary['total_records'] ?? 0) . "\n";
    echo "✓ Total Quantity:          " . ($summary['total_quantity'] ?? 0) . "\n";
    echo "✓ Number of Owners:        " . ($summary['owner_count'] ?? 0) . "\n";
    echo "✓ First Added:             " . ($summary['first_added'] ?? 'N/A') . "\n";
    echo "✓ Last Updated:            " . ($summary['last_updated'] ?? 'N/A') . "\n";
    echo "✓ Database:                bw_gas_detector\n";
    echo "✓ Table:                   delivery_records\n";
    echo "✓ Query Date:              " . date('Y-m-d H:i:s') . "\n";
}

echo "\n" . str_repeat("=", 80) . "\n\n";

$conn->close();
?>
