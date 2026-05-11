<?php
require_once 'db_config.php';

// Test the warranty detection API
$file = 'BW Gas Detector Current Sales Record.xlsx';
if (file_exists($file)) {
    // Create form data
    $file_handle = fopen($file, 'r');
    $file_content = stream_get_contents($file_handle);
    fclose($file_handle);
    
    // Try to post to detect-warranty-rows.php
    $url = 'http://localhost/BW/api/detect-warranty-rows.php';
    
    $boundary = '----WebKitFormBoundary' . bin2hex(random_bytes(16));
    $body = '';
    $body .= '--' . $boundary . "\r\n";
    $body .= 'Content-Disposition: form-data; name="file"; filename="' . $file . '"' . "\r\n";
    $body .= 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' . "\r\n\r\n";
    $body .= $file_content . "\r\n";
    $body .= '--' . $boundary . '--' . "\r\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: multipart/form-data; boundary=' . $boundary
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: " . $http_code . "\n";
    echo "Error: " . ($error ? $error : 'None') . "\n";
    echo "Response: " . substr($response, 0, 500) . "\n";
} else {
    echo "File not found: " . $file . "\n";
}
?>
