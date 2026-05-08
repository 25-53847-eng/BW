<?php
require_once 'db_config.php';

// Delete all Andison Industrial records from delivery_records
$delete_query = "DELETE FROM delivery_records WHERE company_name = 'Andison Industrial'";
$result = $conn->query($delete_query);

if ($result) {
    $affected_rows = $conn->affected_rows;
    echo "✅ Successfully deleted $affected_rows records with company_name = 'Andison Industrial'\n\n";
    
    // Verify deletion
    $verify_result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE company_name = 'Andison Industrial'");
    $verify_row = $verify_result->fetch_assoc();
    echo "✅ Verification: Remaining Andison Industrial records: " . $verify_row['cnt'] . "\n";
    
    // Get new totals
    $total_result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records");
    $total_row = $total_result->fetch_assoc();
    echo "✅ Total delivery_records remaining: " . $total_row['cnt'] . "\n";
} else {
    echo "❌ Error deleting records: " . $conn->error . "\n";
}
?>
