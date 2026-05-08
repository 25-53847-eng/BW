<?php
require_once __DIR__ . '/../db_config.php';

// Check for records that might be inventory with different company_name
$sql = "SELECT DISTINCT company_name, COUNT(*) as count, SUM(quantity) as total_qty
        FROM delivery_records 
        GROUP BY company_name
        ORDER BY count DESC";

$result = $conn->query($sql);
if ($result) {
    echo "Records by company_name:\n";
    echo "Company Name | Count | Total Qty\n";
    echo str_repeat("-", 80) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo str_pad($row['company_name'] ?? 'NULL', 30) . " | " . 
             str_pad($row['count'], 10) . " | " . 
             str_pad($row['total_qty'] ?? 0, 10) . "\n";
    }
}

// Check if there's a backup or history
$tables = $conn->query("SHOW TABLES LIKE '%stock%' OR LIKE '%backup%'");
echo "\n\nTables with 'stock' or 'backup':\n";
if ($tables && $tables->num_rows > 0) {
    while ($row = $tables->fetch_row()) {
        echo "- " . $row[0] . "\n";
    }
} else {
    echo "No stock or backup tables found\n";
}

$conn->close();
?>

