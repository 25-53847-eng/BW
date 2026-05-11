<?php
require_once 'db_config.php';

$result = $conn->query('DESCRIBE delivery_records');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' (' . $row['Type'] . ')\n';
}
