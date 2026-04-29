<?php
ob_start();
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../db_config.php';

// Only admins can manage employees
if (empty($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Only admins can manage employees']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    createEmployee();
} elseif ($action === 'update') {
    updateEmployee();
} elseif ($action === 'delete') {
    deleteEmployee();
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function respond($success, $message, $status = 200) {
    http_response_code($status);
    ob_clean();
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

function createEmployee() {
    global $conn;

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (!$name || !$email || !$password) {
        respond(false, 'All fields are required', 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Invalid email address', 400);
    }

    if (strlen($password) < 8) {
        respond(false, 'Password must be at least 8 characters long', 400);
    }

    // Check if email already exists
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        respond(false, 'Email address already exists', 400);
    }
    $stmt->close();

    // Hash password and create employee
    $password_hashed = password_hash($password, PASSWORD_BCRYPT);
    $role = 'employee';

    $stmt = $conn->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
    if (!$stmt) {
        respond(false, 'Database error: ' . $conn->error, 500);
    }

    $stmt->bind_param('ssss', $name, $email, $password_hashed, $role);
    if ($stmt->execute()) {
        $employee_id = $conn->insert_id;
        $stmt->close();
        respond(true, 'Employee account created successfully');
    } else {
        respond(false, 'Failed to create employee: ' . $conn->error, 500);
    }
}

function updateEmployee() {
    global $conn;

    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (!$id || !$name || !$email) {
        respond(false, 'All fields are required', 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(false, 'Invalid email address', 400);
    }

    // Check if employee exists
    $stmt = $conn->prepare('SELECT id, email FROM users WHERE id = ? AND role = ?');
    $role_check = 'employee';
    $stmt->bind_param('is', $id, $role_check);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        respond(false, 'Employee not found', 404);
    }
    $employee = $result->fetch_assoc();
    $stmt->close();

    // Check if new email is already used by someone else
    if ($email !== $employee['email']) {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->bind_param('si', $email, $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            respond(false, 'Email address already in use', 400);
        }
        $stmt->close();
    }

    // Update employee
    if ($password) {
        // Update with new password
        if (strlen($password) < 8) {
            respond(false, 'Password must be at least 8 characters long', 400);
        }
        $password_hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare('UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?');
        $stmt->bind_param('sssi', $name, $email, $password_hashed, $id);
    } else {
        // Update without changing password
        $stmt = $conn->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
        $stmt->bind_param('ssi', $name, $email, $id);
    }

    if ($stmt->execute()) {
        $stmt->close();
        respond(true, 'Employee account updated successfully');
    } else {
        respond(false, 'Failed to update employee: ' . $conn->error, 500);
    }
}

function deleteEmployee() {
    global $conn;

    $id = intval($_POST['id'] ?? 0);

    if (!$id) {
        respond(false, 'Employee ID is required', 400);
    }

    // Check if employee exists
    $stmt = $conn->prepare('SELECT id, email FROM users WHERE id = ? AND role = ?');
    $role_check = 'employee';
    $stmt->bind_param('is', $id, $role_check);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        respond(false, 'Employee not found', 404);
    }
    $stmt->close();

    // Delete employee
    $stmt = $conn->prepare('DELETE FROM users WHERE id = ? AND role = ?');
    $stmt->bind_param('is', $id, $role_check);
    if ($stmt->execute()) {
        $stmt->close();
        respond(true, 'Employee account deleted successfully');
    } else {
        respond(false, 'Failed to delete employee: ' . $conn->error, 500);
    }
}
?>
