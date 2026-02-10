<?php
define('DB_SERVER','localhost');
define('DB_USER','regsys_email');
define('DB_PASS' ,'regsys@2025');
define('DB_NAME', 'regsys_email');
$host = getenv('DB_HOST') ?: DB_SERVER;
$con = mysqli_connect($host,DB_USER,DB_PASS,DB_NAME);
mysqli_set_charset($con, 'utf8mb4');

// Check connection
if (mysqli_connect_errno())
{
echo "Failed to connect to MySQL: " . mysqli_connect_error();
 }

?>
