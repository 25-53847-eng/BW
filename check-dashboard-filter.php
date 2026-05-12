<?php
require 'db_config.php';

// Simulate the same logic as index.php
if (isset($_GET['dataset'])) {
    $selected_dataset = trim(strval($_GET['dataset']));
} else {
    $selected_dataset = null;
}

if ($selected_dataset === '') {
    $selected_dataset = null;
}

echo "Selected Dataset: " . ($selected_dataset === null ? "NULL/ALL DATA" : "'{$selected_dataset}'") . "\n";

// Build filter
$dataset_filter = ' AND company_name != ?';
$dataset_filter_params = ['Stock Addition'];
if (!empty($selected_dataset)) {
    $dataset_filter .= ' AND dataset_name = ?';
    $dataset_filter_params[] = $selected_dataset;
}

echo "\nDataset Filter SQL: " . $dataset_filter . "\n";
echo "Params: " . json_encode($dataset_filter_params) . "\n";

echo "\n=== TEST QUERY (Top Clients) ===\n";
$sql = "
    SELECT sold_to as company_name, COUNT(*) as delivery_count, SUM(quantity) as total_quantity
    FROM delivery_records
    WHERE sold_to IS NOT NULL AND sold_to != '' AND TRIM(sold_to) != '' 
            AND sold_to NOT REGEXP '^[0-9]+\$'
            AND (inventory_status IS NULL OR inventory_status = '')" . $dataset_filter . "
    GROUP BY sold_to
    ORDER BY total_quantity DESC
    LIMIT 15
";
echo "SQL: " . $sql . "\n";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($dataset_filter_params)) {
        $typeStr = str_repeat('s', count($dataset_filter_params));
        $stmt->bind_param($typeStr, ...$dataset_filter_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo "\n=== RESULTS ===\n";
    $count = 0;
    while ($row = $result->fetch_assoc()) {
        echo "{$row['company_name']} => {$row['total_quantity']} units\n";
        $count++;
    }
    echo "Total rows: $count\n";
    $stmt->close();
} else {
    echo "Error: " . $conn->error . "\n";
}
?>
