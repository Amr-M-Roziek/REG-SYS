<?php
$whitelist = array('127.0.0.1','::1','localhost');
$dockerHost = getenv('DB_HOST');

if($dockerHost) {
    // Docker environment
    define('DB_SERVER', $dockerHost);
    define('DB_USER', 'regsys_part');
    define('DB_PASS', 'regsys@2025');
    define('DB_NAME', 'regsys_participant');
} elseif(in_array($_SERVER['SERVER_NAME'] ?? 'localhost', $whitelist)){
    define('DB_SERVER','127.0.0.1');
    define('DB_USER','root');
    define('DB_PASS' ,'');
    define('DB_NAME', 'regsys_participant');
} else {
    define('DB_SERVER','localhost');
    define('DB_USER','regsys_part');
    define('DB_PASS' ,'regsys@2025');
    define('DB_NAME', 'regsys_participant');
}
$host = getenv('DB_HOST') ?: DB_SERVER;
$con = mysqli_connect($host,DB_USER,DB_PASS,DB_NAME);
mysqli_set_charset($con, 'utf8mb4');

// Check connection
if (mysqli_connect_errno())
{
echo "Failed to connect to MySQL: " . mysqli_connect_error();
 }

?>
