<?php
require '../db_config.php';

// Check total records
$result = $conn->query('SELECT COUNT(*) as cnt FROM delivery_records');
$row = $result->fetch_assoc();
echo "Total records: " . $row['cnt'] . "\n";

// Check records with owner_user_id
$result = $conn->query('SELECT COUNT(*) as cnt FROM delivery_records WHERE owner_user_id IS NOT NULL');
$row = $result->fetch_assoc();
echo "Records with owner_user_id set: " . $row['cnt'] . "\n";

// Check records with NULL owner_user_id
$result = $conn->query('SELECT COUNT(*) as cnt FROM delivery_records WHERE owner_user_id IS NULL');
$row = $result->fetch_assoc();
echo "Records with NULL owner_user_id: " . $row['cnt'] . "\n";

// Check current user ID
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user_id = $_SESSION['user_id'] ?? 'Not logged in';
echo "Current user_id: " . $user_id . "\n";

// Check records for current user
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $result = $conn->query("SELECT COUNT(*) as cnt FROM delivery_records WHERE owner_user_id = $uid");
    $row = $result->fetch_assoc();
    echo "Records for user $uid: " . $row['cnt'] . "\n";
}

// Sample data to see what's there
echo "\n=== Sample Records ===\n";
$result = $conn->query('SELECT id, company_name, quantity, status, owner_user_id FROM delivery_records LIMIT 5');
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Company: {$row['company_name']}, Qty: {$row['quantity']}, Status: {$row['status']}, Owner: {$row['owner_user_id']}\n";
}
?>

