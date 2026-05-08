<?php
require_once __DIR__ . '/../db_config.php';

echo "=== ANDISON MANILA INVENTORY ANALYSIS ===\n\n";

// Total Andison Manila records in database
$sql1 = "SELECT COUNT(*) as total FROM delivery_records WHERE sold_to LIKE '%andison%' OR company_name LIKE '%andison%'";
$result1 = $conn->query($sql1);
$row1 = $result1->fetch_assoc();
echo "1. Total Andison Manila records in DB: " . $row1['total'] . "\n";

// Andison Manila with inventory_status
$sql2 = "SELECT COUNT(*) as total FROM delivery_records WHERE (sold_to LIKE '%andison%' OR company_name LIKE '%andison%') AND inventory_status = 'Stock in Manila'";
$result2 = $conn->query($sql2);
$row2 = $result2->fetch_assoc();
echo "2. Andison Manila with inventory_status='Stock in Manila': " . $row2['total'] . "\n";

// Check what andison-manila.php actually queries
echo "\n3. Detailed breakdown:\n";
$sql3 = "
    SELECT 
        sold_to,
        company_name,
        inventory_status,
        COUNT(*) as count,
        SUM(quantity) as total_qty
    FROM delivery_records
    WHERE (sold_to LIKE '%andison%' OR company_name LIKE '%andison%')
    GROUP BY sold_to, company_name, inventory_status
    ORDER BY count DESC
";
$result3 = $conn->query($sql3);
while ($row = $result3->fetch_assoc()) {
    echo "- Sold To: '{$row['sold_to']}', Company: '{$row['company_name']}', Status: '{$row['inventory_status']}' = {$row['count']} records ({$row['total_qty']} qty)\n";
}

// Check what the andison-manila.php filter is using
echo "\n4. Check current andison-manila.php logic:\n";
$companyName = 'to Andison Manila';
$sql4 = "
    SELECT COUNT(*) as count, COALESCE(SUM(quantity), 0) as total_qty
    FROM delivery_records
    WHERE company_name = ?
";
$stmt = $conn->prepare($sql4);
$stmt->bind_param('s', $companyName);
$stmt->execute();
$result4 = $stmt->get_result();
$row4 = $result4->fetch_assoc();
echo "- Using company_name = 'to Andison Manila': {$row4['count']} records ({$row4['total_qty']} qty)\n";
$stmt->close();
?>
