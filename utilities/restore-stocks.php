<?php
require_once __DIR__ . '/../db_config.php';

$backup_file = __DIR__ . '/backups/backup_2026-03-23_06-47-42.sql';

if (!file_exists($backup_file)) {
    die("Backup file not found\n");
}

$handle = fopen($backup_file, 'r');
if (!$handle) {
    die("Cannot open backup file\n");
}

$current_stmt = '';
$restored = 0;
$skipped = 0;
$errors = [];

while (($line = fgets($handle)) !== false) {
    $current_stmt .= $line;
    
    // Check if statement is complete (ends with semicolon)
    if (preg_match('/;\s*$/', trim($current_stmt))) {
        // Only process Stock Addition records
        if (strpos($current_stmt, "'Stock Addition'") !== false || strpos($current_stmt, '"Stock Addition"') !== false) {
            if (@$conn->query($current_stmt)) {
                $restored++;
            } else {
                // Ignore duplicate errors
                if (strpos($conn->error, 'Duplicate') === false) {
                    $errors[] = $conn->error;
                } else {
                    $skipped++;
                }
            }
        }
        $current_stmt = '';
    }
}

fclose($handle);

echo "Restoration complete!\n";
echo "✓ Restored: $restored records\n";
echo "⊘ Skipped (duplicates): $skipped records\n";

if (count($errors) > 0) {
    echo "✗ Errors: " . count(array_unique($errors)) . "\n";
    foreach (array_unique($errors) as $err) {
        echo "  - $err\n";
    }
}

// Verify
$check = $conn->query("SELECT COUNT(*) as cnt, COALESCE(SUM(quantity), 0) as total FROM delivery_records WHERE company_name = 'Stock Addition'");
if ($check && $row = $check->fetch_assoc()) {
    echo "\n✓ Verification:\n";
    echo "  Stock Addition records: " . $row['cnt'] . "\n";
    echo "  Total quantity: " . $row['total'] . "\n";
}

$conn->close();
?>


