<?php
session_start();
$_SESSION['user_id'] = 14;
$_SESSION['user_role'] = 'admin';
$_SESSION['user_email'] = 'admin@test.com';
$_SESSION['user_name'] = 'Admin';

// Include the exact admin index.php code
ob_start();
include 'index.php';
ob_end_clean();

// The $stats array should be populated by now
echo "Admin stats['total_companies']: " . $stats['total_companies'] . "\n";
?>
