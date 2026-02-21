<?php
// Mock session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['id'] = 1; 
$_SESSION['login'] = 'admin';

// Mock GET params
$_GET['ajax'] = 1;
$_GET['search'] = 'sara';

// Capture output
ob_start();
include 'manage-users.php';
$output = ob_get_clean();

echo "--- START OUTPUT ---\n";
echo $output;
echo "\n--- END OUTPUT ---\n";
?>