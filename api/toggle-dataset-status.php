<?php
/**
 * Toggle Dataset Status API
 * Enable or disable a dataset
 */

header('Content-Type: application/json');
session_start();

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../db_config.php';
require_once __DIR__ . '/dataset-status-helper.php';

try {
    $json = file_get_contents('php://input');
    $request = json_decode($json, true);

    $action = $request['action'] ?? '';
    $dataset_name = $request['dataset_name'] ?? '';
    $is_enabled = isset($request['is_enabled']) ? (bool) $request['is_enabled'] : null;

    if (empty($dataset_name)) {
        throw new Exception('Dataset name is required');
    }

    switch ($action) {
        case 'get_status':
            $status = getDatasetStatus($conn, $dataset_name);
            echo json_encode($status);
            break;

        case 'set_status':
            if ($is_enabled === null) {
                throw new Exception('is_enabled status is required');
            }
            
            // Initialize if not exists
            initializeDatasetMetadata($conn, $dataset_name, $is_enabled);
            $status = getDatasetStatus($conn, $dataset_name);
            
            echo json_encode([
                'success' => true,
                'message' => 'Dataset status updated',
                'status' => $status
            ]);
            break;

        case 'toggle':
            $current_status = getDatasetStatus($conn, $dataset_name);
            $new_status = !$current_status['is_enabled'];
            
            initializeDatasetMetadata($conn, $dataset_name, $new_status);
            $updated_status = getDatasetStatus($conn, $dataset_name);
            
            echo json_encode([
                'success' => true,
                'message' => 'Dataset toggled',
                'was_enabled' => $current_status['is_enabled'],
                'is_enabled' => $updated_status['is_enabled']
            ]);
            break;

        default:
            throw new Exception('Unknown action: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
