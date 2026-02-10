<?php
mysqli_report(MYSQLI_REPORT_OFF);
define('DB_SERVER','127.0.0.1');
define('DB_NAME', 'regsys_poster26');

// Check if running locally
$is_local = false;
if (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
    $is_local = true;
}
// Also check CLI
if (php_sapi_name() === 'cli') {
    $is_local = true;
}

if ($is_local) {
    define('DB_USER','root');
    define('DB_PASS' ,'');
} else {
    define('DB_USER','regsys_poster');
    define('DB_PASS' ,'regsys@2025');
}

$host = getenv('DB_HOST') ?: DB_SERVER;
$con = @mysqli_connect($host,DB_USER,DB_PASS,DB_NAME);
if (!$con && !$is_local) {
    // Fallback logic for production if needed, or just retry with hardcoded if env vars failed
    $con = @mysqli_connect($host,'regsys_poster','regsys@2025',DB_NAME);
}
if (!$con && $is_local) {
     // Fallback for local: try with production creds just in case
     $con = @mysqli_connect($host,'regsys_poster','regsys@2025',DB_NAME);
}

if (!$con) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection failed. Please try again later.");
}
mysqli_set_charset($con, 'utf8mb4');
?>
