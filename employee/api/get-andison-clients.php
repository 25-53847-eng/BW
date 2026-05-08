<?php
session_start();
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../db_config.php';

// Get all distinct company/sold_to values for Andison clients
$owner_user_id = intval($_SESSION['user_id'] ?? 0);

$sql = "
    SELECT DISTINCT TRIM(COALESCE(company_name, sold_to)) as client_name
    FROM delivery_records 
    WHERE owner_user_id = ?
    AND (
        LOWER(TRIM(COALESCE(company_name, ''))) LIKE '%andison%'
        OR LOWER(TRIM(COALESCE(sold_to, ''))) LIKE '%andison%'
    )
    AND TRIM(COALESCE(company_name, sold_to)) != ''
    ORDER BY client_name ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $conn->error]);
    exit;
}

$stmt->bind_param('i', $owner_user_id);
$stmt->execute();
$result = $stmt->get_result();

$clients = [];
while ($row = $result->fetch_assoc()) {
    $clients[] = $row['client_name'];
}

$stmt->close();

// Return unique, sorted list
$clients = array_unique($clients);
sort($clients);

header('Content-Type: application/json');
echo json_encode(['clients' => array_values($clients)]);
?>
