<?php
require 'db_config.php';

// Get the first admin user (user_id = 1)
$admin_id = 1;

// Update all warranty_replacements without owner_user_id
$update_sql = "UPDATE warranty_replacements SET owner_user_id = ? WHERE owner_user_id IS NULL";
$stmt = $conn->prepare($update_sql);
if ($stmt) {
    $stmt->bind_param('i', $admin_id);
    if ($stmt->execute()) {
        echo "✅ Updated " . $stmt->affected_rows . " warranty records with owner_user_id = $admin_id\n";
    } else {
        echo "❌ Error: " . $stmt->error . "\n";
    }
    $stmt->close();
}
?>