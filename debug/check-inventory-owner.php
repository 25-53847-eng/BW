<?php
require_once '../db_config.php';

$sql = "SELECT owner_user_id, COUNT(*) as cnt FROM delivery_records WHERE company_name = 'Stock Addition' GROUP BY owner_user_id";
$result = $conn->query($sql);
echo "Inventory by owner_user_id:\n";
while ($row = $result->fetch_assoc()) {
    echo "owner_user_id=" . $row['owner_user_id'] . ": " . $row['cnt'] . "\n";
}
$conn->close();
?>

