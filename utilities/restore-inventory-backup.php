<?php
require_once __DIR__ . '/../db_config.php';

$backup_file = __DIR__ . '/backups/backup_2026-03-23_06-47-42.sql';

if (!file_exists($backup_file)) {
    die("Backup file not found!\n");
}

// Read and temporarily execute only Stock Addition related statements
$sql = file_get_contents($backup_file);

// Split by semicolon but preserve statements
$statements = array_filter(array_map('trim', preg_split('/;(?=\s*$|INSERT|UPDATE|DELETE)/m', $sql)));

$restored_count = 0;
$errors = [];

foreach ($statements as $stmt) {
    // Only process Stock Addition related statements
    if (strpos($stmt, 'Stock Addition') !== false) {
        if ($conn->multi_query($stmt . ";")) {
            // Clear any result sets
            while ($conn->more_results()) {
                $conn->next_result();
            }
            $restored_count++;
        } else {
            // Ignore duplicate key errors
            if (strpos($conn->error, 'Duplicate') === false && strpos($conn->error, 'foreign') === false) {
                $errors[] = $conn->error;
            }
        }
    }
}

echo "Restoration attempt complete!\n";
echo "Processed statements: $restored_count\n";
if (count($errors) > 0) {
    echo "Errors encountered: " . count($errors) . "\n";
    foreach (array_unique($errors) as $err) {
        echo "  - $err\n";
    }
}

// Verify restoration
$check = $conn->query("SELECT COUNT(*) as cnt, SUM(quantity) as total_qty FROM delivery_records WHERE company_name = 'Stock Addition'");
if ($check && $row = $check->fetch_assoc()) {
    echo "\n✓ Verification:\n";
    echo "  Stock Addition records: " . $row['cnt'] . "\n";
    echo "  Total inventory quantity: " . ($row['total_qty'] ?? 0) . "\n";
} else {
    echo "\n✗ Verification failed\n";
}

$conn->close();
?>


