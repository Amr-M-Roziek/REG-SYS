<?php
$whitelist = array('127.0.0.1','::1','localhost');
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', $whitelist);

// Check if running in Docker
$dockerHost = getenv('DB_HOST');
if ($dockerHost) {
    // Docker environment - use production/docker credentials
    $defServer = $dockerHost;
    $defUser = 'regsys_reg';
    $defPass = 'regsys@2025';
} else {
    // Standard environment
    $defServer = $isLocal ? '127.0.0.1' : 'localhost';
    $defUser = $isLocal ? 'root' : 'regsys_reg';
    $defPass = $isLocal ? '' : 'regsys@2025';
}

$defName = 'regsys_reg';
$host = getenv('DB_HOST') ?: $defServer;
$user = getenv('DB_USER') ?: $defUser;
$pass = getenv('DB_PASS') ?: $defPass;
$name = getenv('DB_NAME') ?: $defName;
$con = mysqli_connect($host,$user,$pass,$name);
if (mysqli_connect_errno()){
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
} else {
    mysqli_set_charset($con,'utf8mb4');
}
