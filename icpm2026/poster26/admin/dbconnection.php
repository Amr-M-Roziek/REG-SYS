<?php
define('DB_SERVER','localhost');
define('DB_NAME', 'regsys_poster26');

// Determine credentials based on environment or fallback
$host = getenv('DB_HOST') ?: DB_SERVER;

// Check if running in Docker (DB_HOST env var is set)
$is_docker = getenv('DB_HOST') ? true : false;

// Default to production credentials
$user = 'regsys_poster';
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

try {
    $con = @mysqli_connect($host, $user, $pass, DB_NAME);
} catch (Exception $e) {
    $con = false;
}

if (!$con) {
    // If failed, try the alternative
    if ($is_local) {
        // Fallback to production credentials even on local (maybe they configured it)
        $user = 'regsys_poster';
        $pass = 'regsys@2025';
    } else {
        // Fallback to local credentials on non-local (unlikely but safe)
        $user = 'root';
        $pass = '';
    }
    try {
        $con = mysqli_connect($host, $user, $pass, DB_NAME);
    } catch (Exception $e) {
        die("Failed to connect to MySQL: " . $e->getMessage());
    }
    
    if (!$con) {
        die("Failed to connect to MySQL: " . mysqli_connect_error());
    }
}

// Define constants for backward compatibility
if (!defined('DB_USER')) define('DB_USER', $user);
if (!defined('DB_PASS')) define('DB_PASS', $pass);

mysqli_set_charset($con, 'utf8mb4');
