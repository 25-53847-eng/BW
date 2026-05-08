<?php
require_once __DIR__ . '/../db_config.php';

// Get all records where sold_to is NULL or empty (these should be inventory)
$sql = "SELECT DISTINCT item_code, item_name, owner_user_id, dataset_name
        FROM delivery_records 
        WHERE (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')
        AND company_name != 'Stock Addition'
        AND company_name NOT IN ('Orders', 'Inquiry', 'Andison Industrial')";

$result = $conn->query($sql);
if (!$result) {
    die("Query error: " . $conn->error . "\n");
}

$created_count = 0;
$duplicate_count = 0;

while ($row = $result->fetch_assoc()) {
    $item_code = $row['item_code'];
    $item_name = $row['item_name'];
    $owner_user_id = $row['owner_user_id'];
    $dataset_name = $row['dataset_name'];
    
    // Get total quantity for this item from all records with no sold_to
    $qty_sql = "SELECT COALESCE(SUM(quantity), 0) as total_qty
                FROM delivery_records 
                WHERE item_code = ? 
                AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')
                AND company_name != 'Stock Addition'
                AND owner_user_id = ?";
    
    $qty_stmt = $conn->prepare($qty_sql);
    $qty_stmt->bind_param('si', $item_code, $owner_user_id);
    $qty_stmt->execute();
    $qty_result = $qty_stmt->get_result();
    $qty_row = $qty_result->fetch_assoc();
    $total_qty = intval($qty_row['total_qty']);
    $qty_stmt->close();
    
    // Check if Stock Addition record already exists
    $check_sql = "SELECT id FROM delivery_records 
                  WHERE item_code = ? 
                  AND company_name = 'Stock Addition'
                  AND owner_user_id = ?
                  LIMIT 1";
    
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('si', $item_code, $owner_user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing record
        $update_sql = "UPDATE delivery_records 
                       SET quantity = ?, updated_at = CURRENT_TIMESTAMP
                       WHERE item_code = ? 
                       AND company_name = 'Stock Addition'
                       AND owner_user_id = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param('isi', $total_qty, $item_code, $owner_user_id);
        $update_stmt->execute();
        $update_stmt->close();
        $duplicate_count++;
    } else {
        // Create new Stock Addition record
        $insert_sql = "INSERT INTO delivery_records 
                       (delivery_month, delivery_day, delivery_year, item_code, item_name, quantity, 
                        company_name, status, created_at, updated_at, owner_user_id, dataset_name)
                       VALUES ('Inventory', '1', YEAR(NOW()), ?, ?, ?, 'Stock Addition', 'Inventory', NOW(), NOW(), ?, ?)";
        
        $insert_stmt = $conn->prepare($insert_sql);
        if (!$insert_stmt) {
            echo "Prepare error: " . $conn->error . "\n";
            continue;
        }
        
        $insert_stmt->bind_param('ssissi', $item_code, $item_name, $total_qty, $owner_user_id, $dataset_name);
        if ($insert_stmt->execute()) {
            $created_count++;
        } else {
            echo "Insert error for $item_code: " . $insert_stmt->error . "\n";
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}

echo "Inventory Recreation Complete!\n";
echo "? Created: $created_count new Stock Addition records\n";
echo "? Updated: $duplicate_count existing records\n";

// Verify
$verify = $conn->query("SELECT COUNT(*) as cnt, COALESCE(SUM(quantity), 0) as total 
                        FROM delivery_records 
                        WHERE company_name = 'Stock Addition'");

if ($verify && $row = $verify->fetch_assoc()) {
    echo "\n? Verification:\n";
    echo "  Total Stock Addition records: " . $row['cnt'] . "\n";
    echo "  Total inventory quantity: " . $row['total'] . "\n";
}

$conn->close();
?>


