<?php
// Fix warranty_replacements table
require_once 'db_config.php';

try {
    // First check current structure
    echo "Checking warranty_replacements table structure...\n";
    $result = $conn->query("DESCRIBE warranty_replacements");
    if ($result) {
        echo "Current structure:\n";
        while ($row = $result->fetch_assoc()) {
            echo $row['Field'] . ": " . $row['Type'] . " (" . $row['Key'] . ")\n";
        }
    }
    
    // Fix: Add AUTO_INCREMENT and PRIMARY KEY to id
    echo "\nApplying fix...\n";
    $sql = "ALTER TABLE warranty_replacements MODIFY id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
    
    if ($conn->query($sql)) {
        echo "✅ warranty_replacements table fixed successfully!\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
    }
    
    // Verify the fix
    echo "\nVerifying fix...\n";
    $result = $conn->query("DESCRIBE warranty_replacements");
    if ($result) {
        $firstRow = $result->fetch_assoc();
        echo "ID column now: " . $firstRow['Field'] . " (" . $firstRow['Type'] . ") Key: " . $firstRow['Key'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
