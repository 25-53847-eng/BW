<?php
// Test DateTime usage
$today = new DateTime();
echo "Today: " . $today->format('Y-m-d') . "\n";
echo "Month: " . $today->format('F') . "\n";
echo "Day: " . $today->format('d') . "\n";
echo "Year: " . $today->format('Y') . "\n";
