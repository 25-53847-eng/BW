<?php
require_once __DIR__ . '/../db_config.php';

// Hash the password
$password_hash = password_hash('test123', PASSWORD_DEFAULT);

// Insert the admin user
$stmt = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role)');
$stmt->bind_param('ssss', $name, $email, $password_hash, $role);

$name = 'Angeli Uybomping';
$email = 'angeli.uybomping@andisonindustrial.com';
$role = 'admin';

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Admin user created successfully', 'email' => $email]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>
