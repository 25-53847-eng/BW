<?php
// Test database connection
header('Content-Type: application/json');

$host = 'localhost';
$port = 3307;
$user = 'root';
$pass = '';
$db = 'bw_gas_detector';

echo json_encode(['status' => 'testing', 'connection' => "$host:$port"]) . "\n";

try {
    // Test 1: Basic connection
    $conn = new mysqli($host, $user, $pass, $db, $port);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Test 2: Query
    $result = $conn->query("SELECT DATABASE() as db, VERSION() as version");
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    $row = $result->fetch_assoc();
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful!',
        'database' => $row['db'],
        'mysql_version' => $row['version'],
        'connection_details' => "$host:$port"
    ]) . "\n";
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'attempted_connection' => "$host:$port"
    ]) . "\n";
}
?>
