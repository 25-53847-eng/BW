<?php
require_once 'db_config.php';

echo "=== After Excel Import ===\n\n";

$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records");
$row = $result->fetch_assoc();
echo "Total records in delivery_records: " . $row['cnt'] . "\n\n";

// Check the dataset we just imported
echo "=== By Dataset ===\n";
$result = $conn->query("SELECT dataset_name, COUNT(*) as cnt FROM delivery_records GROUP BY dataset_name ORDER BY cnt DESC");
while ($row = $result->fetch_assoc()) {
    echo ($row['dataset_name'] ?? '(null)') . ": " . $row['cnt'] . "\n";
}

echo "\n=== Checking for Andison Industrial in the new dataset ===\n";
$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE dataset_name = '2024 to NOW BW Sales Record' AND company_name = 'Andison Industrial'");
$row = $result->fetch_assoc();
echo "Andison Industrial (company_name): " . $row['cnt'] . "\n";

$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE dataset_name = '2024 to NOW BW Sales Record' AND sold_to = 'Andison Industrial'");
$row = $result->fetch_assoc();
echo "Andison Industrial (sold_to): " . $row['cnt'] . "\n";

// Show unique sold_to values in the new dataset
echo "\n=== Unique 'Sold To' values in the new dataset (first 20) ===\n";
$result = $conn->query("SELECT DISTINCT sold_to, COUNT(*) as cnt FROM delivery_records WHERE dataset_name = '2024 to NOW BW Sales Record' GROUP BY sold_to ORDER BY cnt DESC LIMIT 20");
$count = 0;
while ($row = $result->fetch_assoc()) {
    echo "'" . $row['sold_to'] . "': " . $row['cnt'] . " records\n";
    $count++;
}

// Show unique company_name values
echo "\n=== Unique 'Company Name' values in the new dataset (first 20) ===\n";
$result = $conn->query("SELECT DISTINCT company_name, COUNT(*) as cnt FROM delivery_records WHERE dataset_name = '2024 to NOW BW Sales Record' GROUP BY company_name ORDER BY cnt DESC LIMIT 20");
$count = 0;
while ($row = $result->fetch_assoc()) {
    echo "'" . $row['company_name'] . "': " . $row['cnt'] . " records\n";
    $count++;
}
?>
