<?php
require_once __DIR__ . '/../db_config.php';

echo "=== Users Table Structure ===\n";
$result = $conn->query('DESCRIBE users');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n=== Users in Database ===\n";
$result = $conn->query('SELECT id, name, email, role FROM users');
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}, Name: {$row['name']}, Email: {$row['email']}, Role: {$row['role']}\n";
}

echo "\n=== Test Password Hash ===\n";
$test_hash = password_hash('test123', PASSWORD_DEFAULT);
echo "Hash of 'test123': $test_hash\n";

// Find Angeli user
$stmt = $conn->prepare('SELECT password FROM users WHERE email = ? LIMIT 1');
$stmt->bind_param('s', $email);
$email = 'angeli.uybomping@andisonindustrial.com';
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    echo "Found Angeli user!\n";
    echo "Hash in DB: {$row['password']}\n";
    $verified = password_verify('test123', $row['password']);
    echo "Password verify 'test123' result: " . ($verified ? "✓ TRUE" : "✗ FALSE") . "\n";
} else {
    echo "User not found in database\n";
}
?>

