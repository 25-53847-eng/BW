<?php
session_start();
if (empty($_SESSION['user_id'])) {
    die('Not logged in');
}

require_once __DIR__ . '/db_config.php';

$user_id = intval($_SESSION['user_id']);

// Get ALL the info needed
$output = "<h2>🔍 Year Loading Diagnostic</h2>";

// 1. Check indexes
$output .= "<h3>1. Database Indexes</h3>";
$indexes = $conn->query("SHOW INDEX FROM delivery_records WHERE Key_name LIKE 'idx_%year' OR Key_name LIKE 'idx_owner'");
$output .= "<ul>";
while ($idx = $indexes->fetch_assoc()) {
    $output .= "<li>✓ " . $idx['Key_name'] . " on " . $idx['Column_name'] . "</li>";
}
$output .= "</ul>";

// 2. Count records by year
$output .= "<h3>2. Your Data</h3>";
$result = $conn->query("
    SELECT 
        delivery_year,
        COUNT(*) as count
    FROM delivery_records
    WHERE owner_user_id = $user_id
    GROUP BY delivery_year
    ORDER BY delivery_year DESC
");
$output .= "<table border='1' cellpadding='8'>";
$output .= "<tr><th>Year</th><th>Records</th></tr>";
$total = 0;
while ($row = $result->fetch_assoc()) {
    $yr = $row['delivery_year'] ?: 'BLANK';
    $output .= "<tr><td>$yr</td><td>" . $row['count'] . "</td></tr>";
    if ($row['delivery_year'] > 0) $total += $row['count'];
}
$output .= "<tr><th>Total Valid</th><th>$total</th></tr>";
$output .= "</table>";

// 3. Test the API directly
$output .= "<h3>3. API Test</h3>";
ob_start();
include __DIR__ . '/api/get-available-years.php';
$api_output = ob_get_clean();
$api_data = json_decode($api_output, true);
$output .= "<pre>" . htmlspecialchars(json_encode($api_data, JSON_PRETTY_PRINT)) . "</pre>";

// 4. Check for timeout issues
$output .= "<h3>4. Performance</h3>";
$start = microtime(true);
$conn->query("SELECT DISTINCT delivery_year FROM delivery_records WHERE owner_user_id = $user_id AND delivery_year > 0 ORDER BY delivery_year DESC");
$elapsed = (microtime(true) - $start) * 1000;
$output .= "<p>Query time: " . round($elapsed, 2) . "ms (should be &lt;100ms)</p>";
if ($elapsed > 100) {
    $output .= "<p style='color:red'>⚠️ Query is slow! Need index optimization.</p>";
} else {
    $output .= "<p style='color:green'>✓ Query is fast</p>";
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnostic</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        h2, h3 { color: #333; }
        table { background: white; border-collapse: collapse; margin: 10px 0; }
        td, th { padding: 8px; text-align: left; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        .good { color: green; }
        .bad { color: red; }
    </style>
</head>
<body>
<?php echo $output; ?>
<p><a href="/">Back to Dashboard</a></p>
</body>
</html>
