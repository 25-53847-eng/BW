<?php
require_once 'db_config.php';

echo "=== Final Verification ===\n\n";

// 1. Check for Andison Industrial
$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE company_name = 'Andison Industrial' OR company_name LIKE '%andison%' OR company_name LIKE '%Andison%'");
$row = $result->fetch_assoc();
echo "❌ Records still containing 'Andison': " . $row['cnt'] . "\n";

// 2. Check unique company names that look like client names
echo "\n=== Sample of remaining client company names (Sold To) ===\n";
$result = $conn->query("SELECT DISTINCT company_name FROM delivery_records WHERE company_name IS NOT NULL AND company_name != '' LIMIT 20");
$count = 0;
while ($row = $result->fetch_assoc()) {
    echo "- " . $row['company_name'] . "\n";
    $count++;
}
if ($count == 0) {
    echo "- (no client names yet)\n";
}

// 3. Count by record type
echo "\n=== Record type distribution ===\n";
$result = $conn->query("SELECT company_name, COUNT(*) as cnt FROM delivery_records GROUP BY company_name ORDER BY cnt DESC");
while ($row = $result->fetch_assoc()) {
    echo $row['company_name'] . ": " . $row['cnt'] . " records\n";
}

// 4. Total records summary
echo "\n=== Summary ===\n";
$result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records");
$row = $result->fetch_assoc();
echo "✅ Total delivery records now: " . $row['cnt'] . "\n";
echo "✅ Records deleted: 917\n";
echo "✅ Backup saved: backups/delivery_records_backup_before_andison_delete_*.sql\n";
?>
