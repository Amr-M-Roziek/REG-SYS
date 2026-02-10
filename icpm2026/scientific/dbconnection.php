<?php
mysqli_report(MYSQLI_REPORT_OFF);
define('DB_SERVER','127.0.0.1');

// Check if running on localhost to use default XAMPP credentials
$whitelist = array(
    '127.0.0.1',
    '::1'
);

if((isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], $whitelist)) || php_sapi_name() === 'cli'){
    // Local / Development Environment
    define('DB_USER','root');
    define('DB_PASS' ,'');
    define('DB_NAME', 'regsys_poster26');
} else {
    // Production Environment
    define('DB_USER','regsys_poster');
    define('DB_PASS' ,'regsys@2025');
    define('DB_NAME', 'regsys_poster26');
}

$host = getenv('DB_HOST') ?: DB_SERVER;
$con = @mysqli_connect($host,DB_USER,DB_PASS,DB_NAME);

// Fallback for local development if primary DB name doesn't match
if (!$con && ((isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], $whitelist)) || php_sapi_name() === 'cli')) {
  $fallback = 'regsys_poster';
  $con = @mysqli_connect($host,DB_USER,DB_PASS,$fallback);
}

if (!$con) {
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection failed. Please try again later.");
}
mysqli_set_charset($con, 'utf8mb4');
?>
