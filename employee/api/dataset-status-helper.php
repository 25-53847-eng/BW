<?php
/**
 * Dataset Status Helper Functions
 * Check if a dataset is enabled/disabled
 */

/**
 * Check if a dataset is enabled
 * @param mysqli $conn Database connection
 * @param string $dataset_name Dataset name to check
 * @return bool True if enabled or not found (default), False if disabled
 */
function isDatasetEnabled($conn, $dataset_name) {
    if (empty($dataset_name)) {
        return true; // No dataset specified, allow actions
    }

    $dataset_name = trim($dataset_name);
    
    // Query with prepared statement for safety
    $stmt = $conn->prepare("SELECT is_enabled FROM dataset_metadata WHERE dataset_name = ?");
    if (!$stmt) {
        return true; // If table doesn't exist, allow actions (default enabled)
    }

    $stmt->bind_param('s', $dataset_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return (bool) $row['is_enabled'];
    }

    // Dataset not found in metadata, return true (default enabled)
    return true;
}

/**
 * Get dataset status
 * @param mysqli $conn Database connection
 * @param string $dataset_name Dataset name to check
 * @return array Array with 'is_enabled' and 'exists' keys
 */
function getDatasetStatus($conn, $dataset_name) {
    if (empty($dataset_name)) {
        return [
            'is_enabled' => true,
            'exists' => false,
            'dataset_name' => 'UNASSIGNED'
        ];
    }

    $dataset_name = trim($dataset_name);
    
    $stmt = $conn->prepare("SELECT is_enabled FROM dataset_metadata WHERE dataset_name = ?");
    if (!$stmt) {
        return [
            'is_enabled' => true,
            'exists' => false,
            'dataset_name' => $dataset_name
        ];
    }

    $stmt->bind_param('s', $dataset_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        return [
            'is_enabled' => (bool) $row['is_enabled'],
            'exists' => true,
            'dataset_name' => $dataset_name
        ];
    }

    return [
        'is_enabled' => true,
        'exists' => false,
        'dataset_name' => $dataset_name
    ];
}

/**
 * Enable/Disable a dataset
 * @param mysqli $conn Database connection
 * @param string $dataset_name Dataset name
 * @param bool $is_enabled Enable (true) or disable (false)
 * @return bool Success status
 */
function setDatasetStatus($conn, $dataset_name, $is_enabled = true) {
    $dataset_name = trim($dataset_name);
    $is_enabled = (int) $is_enabled;

    $stmt = $conn->prepare("UPDATE dataset_metadata SET is_enabled = ? WHERE dataset_name = ?");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('is', $is_enabled, $dataset_name);
    return $stmt->execute();
}

/**
 * Initialize a dataset in metadata (if not exists)
 * @param mysqli $conn Database connection
 * @param string $dataset_name Dataset name
 * @param bool $is_enabled Initial enabled status
 * @return bool Success status
 */
function initializeDatasetMetadata($conn, $dataset_name, $is_enabled = true) {
    $dataset_name = trim($dataset_name);
    $is_enabled = (int) $is_enabled;

    $stmt = $conn->prepare("INSERT INTO dataset_metadata (dataset_name, is_enabled) 
                           VALUES (?, ?) 
                           ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled)");
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('si', $dataset_name, $is_enabled);
    return $stmt->execute();
}
?>
