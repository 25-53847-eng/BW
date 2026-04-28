<?php
$dbConfigPath = dirname(__DIR__) . '/db_config.php';
require_once $dbConfigPath;

// Fetch all PO records with cost data in notes
$sql = "SELECT id, notes FROM delivery_records WHERE company_name = 'Orders' AND notes LIKE '%Peso Cost%'";
$result = $conn->query($sql);

$updated = 0;
$errors = [];

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $notes = $row['notes'];
    
    // Extract Peso Cost: 123,456.00
    if (preg_match('/Peso Cost:\s*([\d,]+\.?\d*)/i', $notes, $matches)) {
        $pesoCost = (float)str_replace(',', '', $matches[1]);
    } else {
        $pesoCost = 0;
    }
    
    // Extract Foreign Cost: 1,234,567.00
    if (preg_match('/Foreign Cost:\s*([\d,]+\.?\d*)/i', $notes, $matches)) {
        $foreignCost = (float)str_replace(',', '', $matches[1]);
    } else {
        $foreignCost = 0;
    }
    
    // Clean notes - remove cost line
    $cleanNotes = preg_replace('/\[Peso Cost:.*?\]/i', '', $notes);
    $cleanNotes = trim($cleanNotes);
    
    // Update database
    $updateSql = "UPDATE delivery_records SET peso_cost = ?, foreign_cost = ?, notes = ? WHERE id = ?";
    $stmt = $conn->prepare($updateSql);
    
    if ($stmt) {
        $stmt->bind_param('ddsi', $pesoCost, $foreignCost, $cleanNotes, $id);
        if ($stmt->execute()) {
            $updated++;
            echo "✓ Record #$id: Peso Cost: $pesoCost, Foreign Cost: $foreignCost\n";
        } else {
            $errors[] = "Failed to update record #$id: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $errors[] = "Failed to prepare statement for record #$id";
    }
}

echo "\n=== Migration Complete ===\n";
echo "Updated: $updated records\n";

if (!empty($errors)) {
    echo "\nErrors:\n";
    foreach ($errors as $error) {
        echo "✗ $error\n";
    }
} else {
    echo "No errors!\n";
}
?>
