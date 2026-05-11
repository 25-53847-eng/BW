<?php
session_start();
// Simulate authenticated session
$_SESSION['user_id'] = 1;

require_once 'db_config.php';

// Test the query
$sql = "
    SELECT
        id,
        invoice_no,
        item_code,
        item_name,
        quantity,
        uom,
        serial_no,
        company_name,
        transferred_to,
        sold_to,
        groupings
    FROM delivery_records
    WHERE (
        company_name = 'to Andison Manila'
        OR transferred_to = 'to Andison Manila'
        OR LOWER(TRIM(COALESCE(sold_to, ''))) IN ('andison manila', 'to andison manila', 'stock in manila', 'andison manila use')
    )
    AND quantity > 0
    AND (
        COALESCE(sold_to, '') = ''
        OR COALESCE(sold_to, '') = '0'
        OR LOWER(TRIM(COALESCE(sold_to, ''))) IN ('andison manila', 'to andison manila', 'stock in manila', 'andison manila use')
    )
    ORDER BY item_code ASC, serial_no ASC
    LIMIT 20
";

$result = $conn->query($sql);

if (!$result) {
    echo "Query Error: " . $conn->error . "\n";
    exit;
}

echo "Total rows found: " . $result->num_rows . "\n\n";

$count = 0;
while ($row = $result->fetch_assoc()) {
    $count++;
    echo "$count. {$row['item_code']} - {$row['item_name']} | Serial: {$row['serial_no']} | Qty: {$row['quantity']}\n";
    echo "   Company: {$row['company_name']} | Transferred: {$row['transferred_to']} | Sold To: {$row['sold_to']}\n\n";
}

if ($count === 0) {
    echo "No items found. Checking all Andison records...\n\n";
    
    $sql2 = "
        SELECT
            id,
            invoice_no,
            item_code,
            item_name,
            quantity,
            uom,
            serial_no,
            company_name,
            transferred_to,
            sold_to
        FROM delivery_records
        WHERE (
            company_name = 'to Andison Manila'
            OR transferred_to = 'to Andison Manila'
            OR LOWER(TRIM(COALESCE(sold_to, ''))) LIKE '%andison%'
        )
        AND quantity > 0
        ORDER BY item_code ASC
        LIMIT 20
    ";
    
    $result2 = $conn->query($sql2);
    echo "Total Andison records with qty > 0: " . $result2->num_rows . "\n\n";
    
    $count2 = 0;
    while ($row = $result2->fetch_assoc()) {
        $count2++;
        echo "$count2. {$row['item_code']} - {$row['item_name']} | Qty: {$row['quantity']}\n";
        echo "   Sold To: '{$row['sold_to']}'\n\n";
    }
}

$conn->close();
?>
