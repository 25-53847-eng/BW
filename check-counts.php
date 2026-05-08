<?php
require 'db_config.php';

if ($conn) {
    $dr = $conn->query('SELECT COUNT(*) as cnt FROM delivery_records');
    $dr_row = $dr->fetch_assoc();
    $delivery_count = intval($dr_row['cnt']);
    
    $wr = $conn->query('SELECT COUNT(*) as cnt FROM warranty_replacements');
    $wr_row = $wr->fetch_assoc();
    $warranty_count = intval($wr_row['cnt']);
    
    $total = $delivery_count + $warranty_count;
    
    echo "Delivery Records: $delivery_count\n";
    echo "Warranty Replacements: $warranty_count\n";
    echo "TOTAL: $total\n";
    echo "Missing: " . (1944 - $total) . "\n";
}
?>