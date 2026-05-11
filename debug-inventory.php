<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'bw_gas_detector';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, 3306);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    echo "Connection Error: " . $conn->connect_error;
    exit;
}

echo "Connected to: " . $conn->get_server_info() . "\n";
echo "Database: " . $db_name . "\n\n";

$result = $conn->query('SHOW TABLES LIKE "inventory"');
echo "Table check:\n";
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_row()) {
        echo "- Table exists: " . $row[0] . "\n";
    }
} else {
    echo "- No inventory table found\n";
}

echo "\nData count:\n";
$result = $conn->query('SELECT COUNT(*) as cnt FROM inventory');
$row = $result->fetch_assoc();
echo "Total rows: " . $row['cnt'] . "\n";

echo "\nFirst 3 rows:\n";
$result = $conn->query('SELECT * FROM inventory LIMIT 3');
while ($row = $result->fetch_assoc()) {
    print_r($row);
}

$conn->close();
?>
