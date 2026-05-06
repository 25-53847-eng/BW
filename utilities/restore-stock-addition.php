<?php
require_once __DIR__ . '/../db_config.php';

// Extract Stock Addition records from backup and restore them
$backup_file = __DIR__ . '/backups/backup_2026-03-23_06-47-42.sql';

if (!file_exists($backup_file)) {
    die("Backup file not found: $backup_file\n");
}

// Read backup file and extract Stock Addition INSERT statements
$backup_content = file_get_contents($backup_file);
$lines = explode("\n", $backup_content);

$insert_count = 0;
$skip_count = 0;

foreach ($lines as $line) {
    // Only look for Stock Addition records
    if (strpos($line, "Stock Addition") !== false && strpos($line, 'INSERT INTO') !== false) {
        // Execute the INSERT statement
        if ($conn->query(trim($line))) {
            $insert_count++;
        } else {
            // If it's a duplicate, that's OK - skip it
            if (strpos($conn->error, 'Duplicate') !== false) {
                $skip_count++;
            } else {
                echo "Error: " . $conn->error . "\n";
                echo "Line: $line\n\n";
            }
        }
    }
}

echo "Restoration complete!\n";
echo "Inserted: $insert_count Stock Addition records\n";
echo "Skipped (duplicates): $skip_count records\n";

// Verify
$check = $conn->query("SELECT COUNT(*) as cnt, SUM(quantity) as total_qty FROM delivery_records WHERE company_name = 'Stock Addition'");
if ($check) {
    $row = $check->fetch_assoc();
    echo "\nVerification:\n";
    echo "Total Stock Addition records: " . $row['cnt'] . "\n";
    echo "Total inventory quantity: " . $row['total_qty'] . "\n";
}

$conn->close();
?>


