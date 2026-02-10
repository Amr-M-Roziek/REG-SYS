<?php
define('DB_SERVER','localhost');
define('DB_USER','regsys_part');
define('DB_PASS' ,'regsys@2025');
define('DB_NAME', 'regsys_participant');
$con = mysqli_connect(DB_SERVER,DB_USER,DB_PASS,DB_NAME);

// Check connection
if (mysqli_connect_errno())
{
echo "Failed to connect to MySQL: " . mysqli_connect_error();
 }

?>
