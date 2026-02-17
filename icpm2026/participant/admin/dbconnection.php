<?php
define('DB_SERVER','localhost');
define('DB_NAME', 'regsys_participant');

// Determine credentials based on environment or fallback
$host = getenv('DB_HOST') ?: DB_SERVER;
$is_docker = getenv('DB_HOST') ? true : false;

// Default to production credentials
$user = 'regsys_part';
$pass = 'regsys@2025';

// Check if running locally (and not Docker)
$is_local = false;
if (!$is_docker) {
    if (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
        $is_local = true;
    }
    // Also check CLI
    if (php_sapi_name() === 'cli') {
        $is_local = true;
    }
}

if ($is_local) {
    // Try standard local credentials first
    $user = 'root';
    $pass = '';
}

$con = null;
try {
    $con = @mysqli_connect($host, $user, $pass, DB_NAME);
} catch (Exception $e) {
    $con = false;
}

if (!$con) {
    // If failed, try the alternative
    if ($is_local) {
        // Fallback to production credentials even on local
        $user = 'regsys_part';
        $pass = 'regsys@2025';
    } else {
        // Fallback to local credentials on non-local
        $user = 'root';
        $pass = '';
    }
    try {
        $con = @mysqli_connect($host, $user, $pass, DB_NAME);
    } catch (Exception $e) {
        $con = false;
    }
}

if (!$con) {
    // Check for AJAX request to return JSON error
    if (isset($_GET['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')) {
        // Clear any previous output
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['error' => "Database Connection Failed: " . mysqli_connect_error()]);
        exit();
    }
    die("Failed to connect to MySQL: " . mysqli_connect_error());
}

// Define constants for backward compatibility
if (!defined('DB_USER')) define('DB_USER', $user);
if (!defined('DB_PASS')) define('DB_PASS', $pass);

mysqli_set_charset($con, 'utf8mb4');

