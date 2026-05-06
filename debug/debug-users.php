<?php
require '../db_config.php';

echo "=== Users in Database ===\n";
$result = $conn->query('SELECT id, email, name, role FROM users');
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Email: {$row['email']}, Name: {$row['name']}, Role: {$row['role']}\n";
}
?>

