<?php
$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db = 'regsys_poster26';

$con = mysqli_connect($host, $user, $pass, $db);
if ($con) {
    echo "Connected successfully to $db with root\n";
} else {
    echo "Failed with root: " . mysqli_connect_error() . "\n";
    
    // Try other credentials
    $user = 'regsys_poster';
    $pass = 'regsys@2025';
    $con = mysqli_connect($host, $user, $pass, $db);
    if ($con) {
        echo "Connected successfully to $db with regsys_poster\n";
    } else {
        echo "Failed with regsys_poster: " . mysqli_connect_error() . "\n";
    }
}
?>
