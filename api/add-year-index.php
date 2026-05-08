<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: ../login.php', true, 302);
    exit;
}

require_once __DIR__ . '/../db_config.php';

// Check which indexes already exist
$existing_indexes = [];
$indexCheck = $conn->query("SHOW INDEX FROM delivery_records WHERE Key_name IN ('idx_dataset_year', 'idx_dataset_created', 'idx_owner_year', 'idx_owner_dataset')");
if ($indexCheck) {
    while ($row = $indexCheck->fetch_assoc()) {
        $existing_indexes[] = $row['Key_name'];
    }
}

$results = [];

// Add owner_year index (critical for user-specific queries)
if (!in_array('idx_owner_year', $existing_indexes)) {
    $result = $conn->query("ALTER TABLE delivery_records ADD INDEX idx_owner_year (owner_user_id, delivery_year)");
    $results[] = [
        'index' => 'idx_owner_year',
        'success' => $result === true,
        'message' => $result === true ? 'Index created' : 'Error: ' . $conn->error
    ];
} else {
    $results[] = [
        'index' => 'idx_owner_year',
        'success' => true,
        'message' => 'Index already exists'
    ];
}

// Add owner_dataset index
if (!in_array('idx_owner_dataset', $existing_indexes)) {
    $result = $conn->query("ALTER TABLE delivery_records ADD INDEX idx_owner_dataset (owner_user_id, dataset_name, delivery_year)");
    $results[] = [
        'index' => 'idx_owner_dataset',
        'success' => $result === true,
        'message' => $result === true ? 'Index created' : 'Error: ' . $conn->error
    ];
} else {
    $results[] = [
        'index' => 'idx_owner_dataset',
        'success' => true,
        'message' => 'Index already exists'
    ];
}

// Only add index if it doesn't exist
if (!in_array('idx_dataset_year', $existing_indexes)) {
    $result = $conn->query("ALTER TABLE delivery_records ADD INDEX idx_dataset_year (dataset_name, delivery_year)");
    $results[] = [
        'index' => 'idx_dataset_year',
        'success' => $result === true,
        'message' => $result === true ? 'Index created' : 'Error: ' . $conn->error
    ];
} else {
    $results[] = [
        'index' => 'idx_dataset_year',
        'success' => true,
        'message' => 'Index already exists'
    ];
}

if (!in_array('idx_dataset_created', $existing_indexes)) {
    $result = $conn->query("ALTER TABLE delivery_records ADD INDEX idx_dataset_created (dataset_name, created_at)");
    $results[] = [
        'index' => 'idx_dataset_created',
        'success' => $result === true,
        'message' => $result === true ? 'Index created' : 'Error: ' . $conn->error
    ];
} else {
    $results[] = [
        'index' => 'idx_dataset_created',
        'success' => true,
        'message' => 'Index already exists'
    ];
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'indexes' => $results
], JSON_PRETTY_PRINT);
?>
