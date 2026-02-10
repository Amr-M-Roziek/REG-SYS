<?php
// Mock GET
$_GET['id'] = '202102767';
$_GET['hash'] = 'dd0b54b6a3f5dfc3acf48e4cd38efefb';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SERVER_PORT'] = 80;

// Run the script
require_once('download-certificate.php');
?>