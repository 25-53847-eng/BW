<?php
require '../db_config.php';

// Get an inventory item
$inv = $conn->query("SELECT id, item_code, quantity FROM delivery_records WHERE quantity > 2 LIMIT 1")->fetch_assoc();

if(!$inv) {
    echo "No inventory\n";
    exit;
}

echo "Testing add-sale.php API\n";
echo "Using: {$inv['item_code']} (ID: {$inv['id']}, Qty: {$inv['quantity']})\n\n";

// Simulate API call
$data = [
    'inventory_id' => $inv['id'],
    'quantity' => 1,
    'sold_to' => 'Test Company Direct',
    'sold_to_month' => 'May',
    'sold_to_day' => 5,
    'delivery_date' => '2026-05-05',
    'notes' => 'Direct API test'
];

// Make request
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'http://localhost/BW/api/add-sale.php',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json'
    ],
    CURLOPT_COOKIE => 'PHPSESSID=' . session_id()
]);

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

echo "HTTP CODE: $http_code\n";
echo "RESPONSE: " . $response . "\n\n";

// Check if it was saved
$check = $conn->query("SELECT * FROM delivery_records WHERE sold_to = 'Test Company Direct' AND notes = 'Direct API test'");
if($check->num_rows > 0) {
    echo "✅ Record saved successfully!\n";
    $conn->query("DELETE FROM delivery_records WHERE sold_to = 'Test Company Direct' AND notes = 'Direct API test'");
} else {
    echo "❌ Record was NOT saved\n";
}

$conn->close();
?>

