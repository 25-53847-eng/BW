<?php
session_start();
require '../db_config.php';

echo "Starting consolidation of walang-sold_to records into Stock Addition...\n\n";

try {
    $conn->begin_transaction();
    
    // Step 1: Get all records without sold_to, grouped by item_code
    $query = "SELECT 
        item_code, 
        item_name,
        SUM(quantity) as total_qty,
        MAX(record_date) as latest_date,
        owner_user_id,
        dataset_name,
        MAX(uom) as uom,
        MAX(status) as status
    FROM delivery_records 
    WHERE company_name != 'Stock Addition' 
    AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')
    GROUP BY item_code, owner_user_id, dataset_name";
    
    $result = $conn->query($query);
    $consolidated_count = 0;
    $original_count = 0;
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    while ($row = $result->fetch_assoc()) {
        $item_code = $row['item_code'];
        $item_name = $row['item_name'];
        $total_qty = $row['total_qty'];
        $latest_date = $row['latest_date'];
        $owner_user_id = $row['owner_user_id'];
        $dataset_name = $row['dataset_name'];
        $uom = $row['uom'] ?? '';
        $status = $row['status'] ?? 'Delivered';
        
        // Count how many original records for this item
        $count_query = "SELECT COUNT(*) as cnt FROM delivery_records 
            WHERE item_code = ? AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '') 
            AND company_name != 'Stock Addition'";
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param('s', $item_code);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $original_count += $count_row['cnt'];
        $count_stmt->close();
        
        // Insert consolidated Stock Addition record
        $insert_query = "INSERT INTO delivery_records 
            (item_code, item_name, company_name, quantity, record_date, status, uom, owner_user_id, dataset_name, sold_to)
            VALUES (?, ?, 'Stock Addition', ?, ?, ?, ?, ?, ?, '')";
        
        $insert_stmt = $conn->prepare($insert_query);
        if (!$insert_stmt) {
            throw new Exception("Insert prepare failed: " . $conn->error);
        }
        
        $insert_stmt->bind_param(
            'ssisssis',
            $item_code,
            $item_name,
            $total_qty,
            $latest_date,
            $status,
            $uom,
            $owner_user_id,
            $dataset_name
        );
        
        if (!$insert_stmt->execute()) {
            throw new Exception("Insert failed: " . $insert_stmt->error);
        }
        
        $insert_stmt->close();
        $consolidated_count++;
        
        echo "Consolidated: $item_code ($item_name) - Total Qty: $total_qty\n";
    }
    
    // Step 2: Delete original records without sold_to (keep only Stock Addition)
    $delete_query = "DELETE FROM delivery_records 
        WHERE company_name != 'Stock Addition' 
        AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')";
    
    if (!$conn->query($delete_query)) {
        throw new Exception("Delete failed: " . $conn->error);
    }
    
    $conn->commit();
    
    echo "\n? Consolidation complete!\n";
    echo "  - Consolidated items: $consolidated_count\n";
    echo "  - Original records deleted: $original_count\n";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "? Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>


