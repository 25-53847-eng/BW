<?php
require_once 'db_config.php';

$dataset = '2024 to NOW BW Sales Record';

// Back up before deleting
$timestamp = date('Y-m-d_H-i-s');
$backup_file = "backups/delivery_records_backup_before_cleanup_$timestamp.sql";

$backup_result = $conn->query("SELECT * FROM delivery_records WHERE dataset_name = '$dataset' AND company_name = 'Andison Industrial'");
$backupContent = "-- Backup of records to be deleted\n";
$backupContent .= "-- Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Get column names
$fieldsResult = $conn->query('DESCRIBE delivery_records');
$fields = [];
while ($field = $fieldsResult->fetch_assoc()) {
    $fields[] = $field['Field'];
}

$count = 0;
while ($row = $backup_result->fetch_assoc()) {
    $values = [];
    foreach ($fields as $field) {
        $val = $row[$field];
        if ($val === null) {
            $values[] = 'NULL';
        } else {
            $values[] = "'" . $conn->real_escape_string($val) . "'";
        }
    }
    $backupContent .= 'INSERT INTO delivery_records (' . implode(',', $fields) . ') VALUES (' . implode(',', $values) . ");\n";
    $count++;
}

file_put_contents($backup_file, $backupContent);
echo "✅ Backup created: $backup_file\n";
echo "✅ Records to delete: $count\n\n";

// Delete the Andison Industrial records from this dataset
$delete_query = "DELETE FROM delivery_records WHERE dataset_name = '$dataset' AND company_name = 'Andison Industrial'";
$result = $conn->query($delete_query);

if ($result) {
    $affected = $conn->affected_rows;
    echo "✅ Deleted $affected records with company_name = 'Andison Industrial'\n\n";
    
    // Check what's left
    $verify = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE dataset_name = '$dataset'");
    $row = $verify->fetch_assoc();
    echo "Remaining records in dataset: " . $row['cnt'] . "\n\n";
    
    // Show what remains
    echo "=== Remaining companies in dataset ===\n";
    $result = $conn->query("SELECT company_name, COUNT(*) as cnt FROM delivery_records WHERE dataset_name = '$dataset' GROUP BY company_name ORDER BY cnt DESC");
    while ($row = $result->fetch_assoc()) {
        echo $row['company_name'] . ": " . $row['cnt'] . "\n";
    }
} else {
    echo "❌ Error: " . $conn->error . "\n";
}
?>
