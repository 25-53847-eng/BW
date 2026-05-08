<?php
require_once __DIR__ . '/../db_config.php';

$owner_user_id = intval($_SESSION['user_id'] ?? 0);
if ($owner_user_id == 0) {
    $owner_user_id = 18; // Default for testing
}

echo "Current user_id: " . intval($_SESSION['user_id'] ?? 'NONE') . "\n";
echo "Testing with owner_user_id: $owner_user_id\n\n";

// Check Stock Addition records by owner
$sql = "SELECT owner_user_id, COUNT(*) as cnt, COALESCE(SUM(quantity), 0) as total
        FROM delivery_records 
        WHERE company_name = 'Stock Addition'
        GROUP BY owner_user_id";

$result = $conn->query($sql);
if ($result) {
    echo "Stock Addition records by owner_user_id:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  owner_user_id=" . $row['owner_user_id'] . ": " . $row['cnt'] . " records, " . $row['total'] . " total qty\n";
    }
}

// Check what the current query would return
$search_filter = "";
$owner_filter = " AND owner_user_id = {$owner_user_id}";

$test_sql = "SELECT COUNT(DISTINCT item_code) as item_count, COALESCE(SUM(quantity), 0) as total_qty
             FROM delivery_records
             WHERE company_name = 'Stock Addition' 
             AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = ''){$owner_filter}";

$test_result = $conn->query($test_sql);
if ($test_result && $row = $test_result->fetch_assoc()) {
    echo "\nQuery result for owner_user_id=$owner_user_id:\n";
    echo "  Items found: " . $row['item_count'] . "\n";
    echo "  Total qty: " . $row['total_qty'] . "\n";
}

$conn->close();
?>

