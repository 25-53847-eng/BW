<?php
require '../db_config.php';

// Set password to 'test123' for admin user
$password = 'test123';
$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = 14");
$stmt->bind_param("s", $hashed);
if ($stmt->execute()) {
    echo "Password updated for user 14 (admin)\n";
    echo "Email: angeli.uybomping@andisonindustrial.com\n";
    echo "Password: test123\n";
} else {
    echo "Error: " . $stmt->error;
}
?>


