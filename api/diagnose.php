<?php
/**
 * Comprehensive Database Diagnostics & Connection Verification
 * Visit: http://localhost/BW/api/diagnose.php
 */

ob_start();
header('Content-Type: application/json; charset=utf-8');

$response = [
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'server_port' => $_SERVER['SERVER_PORT'] ?? 'unknown',
    'mysqli_support' => extension_loaded('mysqli') ? 'Yes' : 'No',
    'tests' => []
];

// Test 1: Connection to port 3307
$test1 = [
    'name' => '🔌 Connection to localhost:3307',
    'port' => 3307,
    'status' => 'UNKNOWN'
];

$conn1 = @new mysqli('localhost', 'root', '', '', 3307);
if ($conn1->connect_error) {
    $test1['status'] = '❌ FAILED';
    $test1['error'] = $conn1->connect_error;
    $test1['solution'] = 'MySQL is not running on port 3307. Check XAMPP Control Panel and ensure MySQL module is started with port 3307.';
} else {
    $test1['status'] = '✅ SUCCESS';
    $test1['message'] = 'Can connect to MySQL server on port 3307';
    $conn1->close();
}
$response['tests'][] = $test1;

// Test 2: Connection to default port 3306 (fallback)
$test2 = [
    'name' => '🔌 Connection to localhost:3306 (default MySQL)',
    'port' => 3306,
    'status' => 'UNKNOWN'
];

$conn2 = @new mysqli('localhost', 'root', '', '', 3306);
if ($conn2->connect_error) {
    $test2['status'] = '❌ FAILED';
    $test2['error'] = $conn2->connect_error;
} else {
    $test2['status'] = '✅ SUCCESS';
    $test2['message'] = 'Can connect to MySQL on default port (3306)';
    $conn2->close();
}
$response['tests'][] = $test2;

// Test 3: Database availability on port 3307
$test3 = [
    'name' => '📦 bw_gas_detector database on port 3307',
    'status' => 'UNKNOWN'
];

$conn3 = @new mysqli('localhost', 'root', '', '', 3307);
if ($conn3->connect_error) {
    $test3['status'] = '❌ SKIPPED (cannot connect to port 3307)';
    $test3['note'] = 'Fix connection to port 3307 first';
} else {
    $dbResult = $conn3->query("SHOW DATABASES LIKE 'bw_gas_detector'");
    if (!$dbResult || $dbResult->num_rows === 0) {
        $test3['status'] = '❌ DATABASE NOT FOUND';
        $test3['solution'] = 'Database does not exist. The setup-db.php API should create it automatically on next import attempt.';
        $test3['manual_solution'] = 'Or manually create in phpMyAdmin: CREATE DATABASE bw_gas_detector;';
    } else {
        $test3['status'] = '✅ DATABASE EXISTS';
        // Try to connect to the database
        $dbConn = @new mysqli('localhost', 'root', '', 'bw_gas_detector', 3307);
        if ($dbConn->connect_error) {
            $test3['status'] = '⚠️  DATABASE EXISTS but cannot connect';
            $test3['error'] = $dbConn->connect_error;
        } else {
            $test3['status'] = '✅ FULLY ACCESSIBLE';
            $test3['message'] = 'Database exists and is accessible';
            $testQuery = $dbConn->query('SELECT DATABASE() as db, COUNT(*) as table_count FROM information_schema.TABLES WHERE TABLE_SCHEMA = "bw_gas_detector"');
            if ($testQuery) {
                $row = $testQuery->fetch_assoc();
                $test3['tables_exist'] = (int)$row['table_count'] > 0 ? 'Yes' : 'No';
            }
            $dbConn->close();
        }
    }
    $conn3->close();
}
$response['tests'][] = $test3;

// Test 4: Try actual application connection
$test4 = [
    'name' => '🏗️  Application db_config.php',
    'status' => 'UNKNOWN'
];

try {
    ob_start();
    require_once __DIR__ . '/../db_config.php';
    $output = ob_get_clean();
    
    if ($conn && $conn instanceof mysqli && !$conn->connect_error) {
        $test4['status'] = '✅ SUCCESS';
        $test4['message'] = 'Application database connection works';
        $dbCheck = $conn->query('SELECT DATABASE() as db');
        if ($dbCheck) {
            $row = $dbCheck->fetch_assoc();
            $test4['database'] = $row['db'];
        }
        $conn->close();
    } else {
        $test4['status'] = '❌ FAILED';
        $test4['error'] = 'Connection not established';
        if ($output) {
            $test4['output'] = substr($output, 0, 200);
        }
    }
} catch (Throwable $e) {
    $test4['status'] = '❌ EXCEPTION';
    $test4['error'] = $e->getMessage();
}
$response['tests'][] = $test4;

// Summary and recommendations
$response['summary'] = [];

$connectionTests = array_filter($response['tests'], fn($t) => strpos($t['name'], '🔌') !== false);
$hasWorkingConnection = array_filter($connectionTests, fn($t) => strpos($t['status'], '✅') !== false);

if (empty($hasWorkingConnection)) {
    $response['summary'][] = '❌ MySQL is not accessible on any port. Start MySQL in XAMPP Control Panel.';
} else {
    $response['summary'][] = '✅ MySQL server is accessible.';
}

$dbTest = array_filter($response['tests'], fn($t) => strpos($t['name'], '📦') !== false)[0] ?? null;
if ($dbTest) {
    if (strpos($dbTest['status'], '✅') !== false) {
        $response['summary'][] = '✅ Database exists and is accessible. You can now import data.';
    } elseif (strpos($dbTest['status'], 'NOT FOUND') !== false) {
        $response['summary'][] = '⚠️  Database will be created automatically on first import.';
    }
}

$appTest = array_filter($response['tests'], fn($t) => strpos($t['name'], '🏗️') !== false)[0] ?? null;
if ($appTest && strpos($appTest['status'], '✅') !== false) {
    $response['summary'][] = '✅ Application is ready to import data!';
} else {
    $response['summary'][] = '❌ Application cannot connect. Check MySQL status.';
}

ob_end_clean();
http_response_code(200);
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>

