<?php
require '../db_config.php';

$owner_user_id = $_SESSION['user_id'] ?? 18;

echo "Groupings in delivery_records WITH sold_to:\n\n";

$r = $conn->query("SELECT groupings, COUNT(*) as cnt 
FROM delivery_records 
WHERE owner_user_id = $owner_user_id
AND company_name NOT IN ('Orders', 'Inquiry', 'Stock Addition')
AND COALESCE(sold_to, '') != ''
GROUP BY groupings
ORDER BY cnt DESC");

while ($row = $r->fetch_assoc()) {
    $grouping_display = empty($row['groupings']) ? '(EMPTY)' : $row['groupings'];
    echo "- Grouping: [$grouping_display], Count: " . $row['cnt'] . "\n";
}

echo "\n\nGroupings in delivery_records WITHOUT sold_to (Stock Addition):\n\n";

$r2 = $conn->query("SELECT groupings, COUNT(*) as cnt 
FROM delivery_records 
WHERE owner_user_id = $owner_user_id
AND company_name = 'Stock Addition'
AND (sold_to IS NULL OR TRIM(COALESCE(sold_to, '')) = '')
GROUP BY groupings
ORDER BY cnt DESC");

while ($row = $r2->fetch_assoc()) {
    $grouping_display = empty($row['groupings']) ? '(EMPTY)' : $row['groupings'];
    echo "- Grouping: [$grouping_display], Count: " . $row['cnt'] . "\n";
}
?>

