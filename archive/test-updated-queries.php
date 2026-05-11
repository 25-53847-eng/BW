<?php
session_start();
$_SESSION['user_id'] = 1;

require_once 'db_config.php';

echo "=== TESTING UPDATED ANALYTICS QUERIES ===\n\n";

// Test 1: Total Andison
$result = $conn->query("
    SELECT COUNT(*) as total_orders, COALESCE(SUM(quantity), 0) as total_units 
    FROM delivery_records 
    WHERE (
        company_name = 'to Andison Manila' 
        OR transferred_to = 'to Andison Manila'
        OR LOWER(TRIM(COALESCE(sold_to, ''))) IN ('andison manila', 'to andison manila', 'stock in manila', 'andison manila use')
    )
    AND quantity > 0
");
if ($result && $row = $result->fetch_assoc()) {
    echo "1. Total Andison Units: " . $row['total_units'] . "\n";
} else {
    echo "1. Query failed!\n";
}

// Test 2: Monthly data
echo "\n2. Monthly Breakdown:\n";
$result = $conn->query("
    SELECT delivery_month, COUNT(*) as order_count, COALESCE(SUM(quantity), 0) as total_qty
    FROM delivery_records 
    WHERE (
        company_name = 'to Andison Manila' 
        OR transferred_to = 'to Andison Manila'
        OR LOWER(TRIM(COALESCE(sold_to, ''))) IN ('andison manila', 'to andison manila', 'stock in manila', 'andison manila use')
    )
    AND delivery_month IS NOT NULL 
    AND delivery_month != ''
    AND quantity > 0
    GROUP BY delivery_month 
    ORDER BY delivery_month
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "   " . $row['delivery_month'] . ": " . $row['total_qty'] . " units\n";
    }
} else {
    echo "   Query failed!\n";
}

// Test 3: Products
echo "\n3. Product Count:\n";
$result = $conn->query("
    SELECT COUNT(DISTINCT item_code) as item_count, COALESCE(SUM(quantity), 0) as total_qty
    FROM delivery_records 
    WHERE (
        company_name = 'to Andison Manila' 
        OR transferred_to = 'to Andison Manila'
        OR LOWER(TRIM(COALESCE(sold_to, ''))) IN ('andison manila', 'to andison manila', 'stock in manila', 'andison manila use')
    )
    AND item_name IS NOT NULL
    AND quantity > 0
");
if ($result && $row = $result->fetch_assoc()) {
    echo "   Item Types: " . $row['item_count'] . "\n";
    echo "   Total Units: " . $row['total_qty'] . "\n";
} else {
    echo "   Query failed!\n";
}

$conn->close();
?>
