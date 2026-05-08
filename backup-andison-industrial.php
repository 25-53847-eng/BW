<?php
require_once 'db_config.php';

$timestamp = date('Y-m-d_H-i-s');
$backup_file = 'backups/delivery_records_backup_before_andison_delete_' . $timestamp . '.sql';

// Backup table structure and data
$result = $conn->query('SHOW CREATE TABLE delivery_records');
$row = $result->fetch_row();
$createTableSQL = $row[1];

// Get the actual data that will be deleted
$data_result = $conn->query("SELECT * FROM delivery_records WHERE company_name = 'Andison Industrial'");
$backupContent = $createTableSQL . ";\n\n";
$backupContent .= "-- Backup of 917 Andison Industrial records that will be deleted\n";
$backupContent .= "-- Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

// Get column names
$fieldsResult = $conn->query('DESCRIBE delivery_records');
$fields = [];
while ($field = $fieldsResult->fetch_assoc()) {
    $fields[] = $field['Field'];
}

$count = 0;
while ($row = $data_result->fetch_assoc()) {
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
echo "✅ Backed up $count records\n";
?>
