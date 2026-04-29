<?php
require_once __DIR__ . '/../db_config.php';

// Ensure users table exists
$conn->query("CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  email VARCHAR(255) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role VARCHAR(50) DEFAULT 'admin' NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Ensure role column exists (for existing databases)
$conn->query("ALTER TABLE users ADD COLUMN role VARCHAR(50) DEFAULT 'admin' NOT NULL");

$name     = 'Angeli Uybomping';
$email    = 'angeli.uybomping@andisonindustrial.com';
$password = password_hash('test123', PASSWORD_DEFAULT);
$role     = 'admin';

$stmt = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role)');
$stmt->bind_param('ssss', $name, $email, $password, $role);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Admin user created.', 'email' => $email, 'password' => 'test123']);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>
