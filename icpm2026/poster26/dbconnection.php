<?php
mysqli_report(MYSQLI_REPORT_OFF);
define('DB_SERVER','127.0.0.1');
define('DB_USER','regsys_poster');
define('DB_PASS' ,'regsys@2025');
define('DB_NAME', 'regsys_poster26');
$host = getenv('DB_HOST') ?: DB_SERVER;
$con = @mysqli_connect($host,DB_USER,DB_PASS,DB_NAME);
if (!$con) {
  $fallback = 'regsys_poster';
  $con = @mysqli_connect($host,DB_USER,DB_PASS,$fallback);
}
if ($con) { mysqli_set_charset($con, 'utf8mb4'); }
?>
