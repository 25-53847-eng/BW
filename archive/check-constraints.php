<?php
// Check foreign key constraints
require_once 'db_config.php';

try {
    echo "Checking foreign key constraints...\n";
    
    // Check for constraints on warranty_replacements
    $result = $conn->query("SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
                            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                            WHERE TABLE_NAME = 'warranty_replacements' 
                            AND REFERENCED_TABLE_NAME IS NOT NULL");
    
    if ($result && $result->num_rows > 0) {
        echo "Foreign keys found:\n";
        while ($row = $result->fetch_assoc()) {
            echo "- {$row['CONSTRAINT_NAME']}: {$row['COLUMN_NAME']} -> {$row['REFERENCED_TABLE_NAME']}.{$row['REFERENCED_COLUMN_NAME']}\n";
        }
    } else {
        echo "No foreign keys found on warranty_replacements\n";
    }
    
    // Also check the table creation to see constraints
    echo "\nTable creation statement:\n";
    $result = $conn->query("SHOW CREATE TABLE warranty_replacements");
    if ($result) {
        $row = $result->fetch_row();
        echo $row[1] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
