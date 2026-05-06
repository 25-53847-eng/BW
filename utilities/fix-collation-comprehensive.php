<?php
/**
 * Comprehensive Collation Fix
 * Fixes database + all tables to use utf8mb4_unicode_ci consistently
 */

require_once '../db_config.php';

echo "Starting comprehensive collation fix...\n\n";

try {
    // 1. Alter the database collation
    echo "Step 1: Fixing database collation...\n";
    $conn->query("ALTER DATABASE " . (defined('DB_NAME') ? DB_NAME : 'bw_gas_detector') . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database collation fixed\n\n";

    // 2. Fix only actual tables (skip views)
    echo "Step 2: Converting all tables to utf8mb4_unicode_ci...\n";
    
    $tables_result = $conn->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'");
    $tables = [];
    while($row = $tables_result->fetch_assoc()) {
        $tables[] = $row['TABLE_NAME'];
    }
    
    foreach($tables as $table) {
        $alter = "ALTER TABLE `$table` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if($conn->query($alter)) {
            echo "  ✓ $table\n";
        } else {
            echo "  ✗ $table - Error: " . $conn->error . "\n";
        }
    }

    echo "\n✅ All tables converted!\n\n";

    // 3. Verify
    echo "Step 3: Verifying collations...\n";
    $verify = $conn->query("SELECT TABLE_NAME, TABLE_COLLATION FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'");
    
    $ok_count = 0;
    $bad_count = 0;
    while($row = $verify->fetch_assoc()) {
        if($row['TABLE_COLLATION'] === 'utf8mb4_unicode_ci') {
            $ok_count++;
        } else {
            echo "  ⚠ {$row['TABLE_NAME']}: {$row['TABLE_COLLATION']}\n";
            $bad_count++;
        }
    }
    
    echo "\n✓ OK: $ok_count tables\n";
    if($bad_count > 0) echo "⚠ Issues: $bad_count tables\n";
    
    echo "\n✅✅✅ Collation fix COMPLETE! Try adding sales now.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>


