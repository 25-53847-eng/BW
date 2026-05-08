<?php
require_once '../db_config.php';

echo "<h2>Data Check After Upload</h2>";

// 1. Check total delivery_records by company_name
echo "<h3>1. All Records by Company Name:</h3>";
$result = $conn->query("
    SELECT company_name, COUNT(*) as count, SUM(quantity) as total_qty
    FROM delivery_records
    GROUP BY company_name
    ORDER BY count DESC
");
if ($result) {
    echo "<pre>";
    while ($row = $result->fetch_assoc()) {
        echo $row['company_name'] . ": " . $row['count'] . " records, " . $row['total_qty'] . " qty\n";
    }
    echo "</pre>";
}

// 2. Check Stock Addition records
echo "<h3>2. Stock Addition Records:</h3>";
$result = $conn->query("
    SELECT 
        COUNT(*) as total_records,
        COUNT(DISTINCT item_code) as unique_items,
        SUM(quantity) as total_qty,
        COUNT(CASE WHEN sold_to IS NULL THEN 1 END) as null_sold_to,
        COUNT(CASE WHEN TRIM(COALESCE(sold_to, '')) = '' THEN 1 END) as empty_sold_to
    FROM delivery_records
    WHERE company_name = 'Stock Addition'
");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<pre>";
    echo "Total records: " . $row['total_records'] . "\n";
    echo "Unique items: " . $row['unique_items'] . "\n";
    echo "Total quantity: " . $row['total_qty'] . "\n";
    echo "Records with NULL sold_to: " . $row['null_sold_to'] . "\n";
    echo "Records with empty sold_to: " . $row['empty_sold_to'] . "\n";
    echo "</pre>";
}

// 3. Show actual Stock Addition records with sold_to
echo "<h3>3. Sample Stock Addition Records (with sold_to values):</h3>";
$result = $conn->query("
    SELECT id, item_code, item_name, quantity, sold_to, company_name
    FROM delivery_records
    WHERE company_name = 'Stock Addition'
    LIMIT 20
");
if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Item Code</th><th>Item Name</th><th>Qty</th><th>Sold To</th><th>Company</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $soldTo = $row['sold_to'] === null ? 'NULL' : "'" . $row['sold_to'] . "'";
        echo "<tr>
            <td>" . $row['id'] . "</td>
            <td>" . $row['item_code'] . "</td>
            <td>" . $row['item_name'] . "</td>
            <td>" . $row['quantity'] . "</td>
            <td>" . $soldTo . "</td>
            <td>" . $row['company_name'] . "</td>
        </tr>";
    }
    echo "</table>";
}

// 4. Check what inventory.php should be showing
echo "<h3>4. What Inventory Query Returns (for inventory.php):</h3>";
$result = $conn->query("
    SELECT 
        item_code,
        item_name,
        COUNT(*) as record_count,
        SUM(quantity) as current_stock
    FROM delivery_records
    WHERE company_name = 'Stock Addition' 
        AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')
    GROUP BY item_code, item_name
    ORDER BY item_code ASC
    LIMIT 20
");
if ($result) {
    $count = 0;
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Item Code</th><th>Item Name</th><th>Records</th><th>Total Qty</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>
            <td>" . $row['item_code'] . "</td>
            <td>" . $row['item_name'] . "</td>
            <td>" . $row['record_count'] . "</td>
            <td>" . $row['current_stock'] . "</td>
        </tr>";
        $count++;
    }
    echo "</table>";
    echo "<p>Showing first 20 items</p>";
}

// 5. Check if there are any Stock in Manila records
echo "<h3>5. Stock in Manila Records:</h3>";
$result = $conn->query("
    SELECT COUNT(*) as count
    FROM delivery_records
    WHERE company_name = 'Stock Addition'
    AND sold_to LIKE '%stock in manila%'
");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Count: " . $row['count'] . "<br>";
}

?>

