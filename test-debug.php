<?php
/**
 * Diagnostic Test for Upload Issues
 * Open in browser: localhost/BW/test-debug.php
 */

// Test 1: PHP Configuration
echo "<h2>1. PHP Configuration</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Max Upload Size: " . ini_get('upload_max_filesize') . "<br>";
echo "Post Max Size: " . ini_get('post_max_size') . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";

// Test 2: File Permissions
echo "<h2>2. File Permissions</h2>";
$dirs_to_check = [
    dirname(__FILE__) . '/uploads' => 'uploads/',
    dirname(__FILE__) . '/backups' => 'backups/',
    sys_get_temp_dir() => 'System temp dir'
];

foreach ($dirs_to_check as $path => $label) {
    if (is_dir($path)) {
        $writable = is_writable($path) ? '✓ WRITABLE' : '✗ NOT WRITABLE';
        echo "$label: $writable<br>";
    } else {
        echo "$label: ✗ DOES NOT EXIST<br>";
    }
}

// Test 3: Database Connection
echo "<h2>3. Database Connection</h2>";
try {
    require_once 'db_config.php';
    if ($conn && !$conn->connect_error) {
        echo "✓ Database Connected<br>";
        echo "Server Version: " . $conn->server_version . "<br>";
        
        // Check table
        $result = $conn->query("SHOW TABLES LIKE 'delivery_records'");
        if ($result && $result->num_rows > 0) {
            echo "✓ delivery_records table exists<br>";
        } else {
            echo "✗ delivery_records table NOT found<br>";
        }
    } else {
        echo "✗ Database Connection Failed: " . $conn->connect_error . "<br>";
    }
} catch (Exception $e) {
    echo "✗ Database Error: " . $e->getMessage() . "<br>";
}

// Test 4: JSON Functions
echo "<h2>4. JSON Support</h2>";
$test_array = ['success' => true, 'test' => 'data'];
$json_encoded = json_encode($test_array);
$json_decoded = json_decode($json_encoded, true);

if ($json_encoded && $json_decoded) {
    echo "✓ JSON encoding/decoding works<br>";
} else {
    echo "✗ JSON encoding/decoding failed<br>";
}

// Test 5: Session
echo "<h2>5. Session</h2>";
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "✓ Sessions work<br>";
} else {
    echo "✗ Sessions not working<br>";
}

// Test 6: Error Handling
echo "<h2>6. Error Handling</h2>";
$errors = error_reporting();
echo "Error Reporting Level: $errors<br>";
$display = ini_get('display_errors') ? 'ON' : 'OFF';
echo "Display Errors: $display<br>";
$log_file = ini_get('error_log');
echo "Error Log Location: " . ($log_file ? $log_file : 'Not configured') . "<br>";

// Test 7: Import API Simulation
echo "<h2>7. Import API Test (Simulated)</h2>";
try {
    $test_payload = json_encode([
        'data' => [
            ['col1' => 'val1', 'col2' => 'val2']
        ]
    ]);
    
    $ch = curl_init('http://localhost/BW/api/import-data.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $test_payload,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        echo "✗ API Request Error: $curl_error<br>";
    } else {
        echo "HTTP Status: $http_code<br>";
        $response_data = json_decode($response, true);
        if ($response_data !== null) {
            echo "✓ API Response is valid JSON<br>";
            echo "Response: " . json_encode($response_data, JSON_PRETTY_PRINT) . "<br>";
        } else {
            echo "✗ API Response is NOT valid JSON<br>";
            echo "Raw Response: " . htmlspecialchars(substr($response, 0, 200)) . "<br>";
        }
    }
} catch (Exception $e) {
    echo "✗ API Test Error: " . $e->getMessage() . "<br>";
}

echo "<br><hr>";
echo "<p style='font-size:12px; color:#666;'>If you see ✗ errors above, that's the issue! Share this info with support.</p>";
?>
