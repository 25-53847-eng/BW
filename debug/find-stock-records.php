<?php
require_once __DIR__ . '/../db_config.php';

// Check for records with empty/NULL company_name or partial matches
$sql = "SELECT company_name, COUNT(*) as count, AVG(quantity) as avg_qty
        FROM delivery_records 
        WHERE company_name LIKE '%Stock%' OR company_name LIKE '%stock%' OR company_name IS NULL
        GROUP BY company_name";

$result = $conn->query($sql);
echo "Records with 'Stock' in company_name or NULL:\n";
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "'" . ($row['company_name'] ?? 'NULL') . "' | Count: " . $row['count'] . " | Avg Qty: " . round($row['avg_qty'], 2) . "\n";
    }
} else {
    echo "No matching records found\n";
}

// Check total records in database
$total = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records");
if ($total) {
    $row = $total->fetch_assoc();
    echo "\n\nTotal records in database: " . $row['cnt'] . "\n";
}

$conn->close();
?>

