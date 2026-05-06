<?php
require_once __DIR__ . '/../db_config.php';

// Get all unique client companies (same as in andison-manila.php)
$clientResult = $conn->query("
    SELECT DISTINCT TRIM(sold_to) as company
    FROM delivery_records
    WHERE sold_to IS NOT NULL 
    AND sold_to != ''
    AND LOWER(TRIM(sold_to)) NOT IN ('andison manila', 'to andison manila', 'stock in manila', 'andison manila use')
    AND LOWER(TRIM(sold_to)) NOT LIKE '%stock in manila%'
    ORDER BY TRIM(sold_to) ASC
");

if ($clientResult && $clientResult->num_rows > 0) {
    echo "✓ Found " . $clientResult->num_rows . " unique client companies for dropdown:\n\n";
    $count = 1;
    while ($row = $clientResult->fetch_assoc()) {
        echo "  $count. " . $row['company'] . "\n";
        $count++;
    }
} else {
    echo "ℹ No client companies yet. These will appear in dropdown once you add sales!\n";
}

$conn->close();
?>

