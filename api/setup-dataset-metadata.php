<?php
/**
 * Setup Dataset Metadata Table
 * Creates or updates the dataset_metadata table to track enabled/disabled status
 * Run this once to initialize the dataset management feature
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../db_config.php';

try {
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $isMysql = ($conn instanceof mysqli);

    // Create dataset_metadata table
    if ($isMysql) {
        $sql = "CREATE TABLE IF NOT EXISTS dataset_metadata (
            id INT AUTO_INCREMENT PRIMARY KEY,
            dataset_name VARCHAR(255) NOT NULL UNIQUE,
            is_enabled BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_is_enabled (is_enabled)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    } else {
        $sql = "CREATE TABLE IF NOT EXISTS dataset_metadata (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            dataset_name VARCHAR(255) NOT NULL UNIQUE,
            is_enabled BOOLEAN DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
    }

    $conn->query($sql);

    // Get all distinct datasets from delivery_records
    $query = "SELECT DISTINCT COALESCE(NULLIF(TRIM(dataset_name), ''), 'UNASSIGNED') as dataset_name 
              FROM delivery_records 
              WHERE dataset_name IS NOT NULL AND TRIM(dataset_name) <> ''
              UNION ALL
              SELECT 'UNASSIGNED' as dataset_name";

    $result = $conn->query($query);
    $datasets = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $datasets[] = $row['dataset_name'];
        }
    }

    // Insert datasets that don't exist yet (all enabled by default)
    $inserted = 0;
    foreach ($datasets as $dataset_name) {
        if ($isMysql) {
            $stmt = $conn->prepare("INSERT IGNORE INTO dataset_metadata (dataset_name, is_enabled) VALUES (?, 1)");
        } else {
            $stmt = $conn->prepare("INSERT OR IGNORE INTO dataset_metadata (dataset_name, is_enabled) VALUES (?, 1)");
        }
        $stmt->bind_param('s', $dataset_name);
        if ($stmt->execute() && $conn->affected_rows > 0) {
            $inserted++;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Dataset metadata table setup complete',
        'datasets_initialized' => $inserted,
        'total_datasets' => count($datasets)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
