<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php', true, 302);
    exit;
}

require_once 'db_config.php';

$owner_user_id = intval($_SESSION['user_id'] ?? 0);

// Get unit types
$allUnitTypes = [];
$unitTypeResult = $conn->query("
    SELECT DISTINCT unit_type
    FROM delivery_records
    WHERE owner_user_id = {$owner_user_id}
        AND unit_type IS NOT NULL 
        AND unit_type != ''
    ORDER BY unit_type ASC
");

if ($unitTypeResult) {
    while ($row = $unitTypeResult->fetch_assoc()) {
        $unitType = trim($row['unit_type']);
        if (!empty($unitType)) {
            $allUnitTypes[] = $unitType;
        }
    }
}

echo "Fetched Unit Types:\n";
echo json_encode($allUnitTypes) . "\n";
echo "Count: " . count($allUnitTypes) . "\n";
?>
