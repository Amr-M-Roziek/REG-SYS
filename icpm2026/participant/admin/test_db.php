<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Connecting to DB...\n";

// Manual connection params for testing
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'regsys_participant';

$con = mysqli_connect($host, $user, $pass, $db);

if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    exit(1);
}

echo "Connected successfully to $db\n";

// Test table creation
$sql = "CREATE TABLE IF NOT EXISTS test_table (id INT PRIMARY KEY)";
if (mysqli_query($con, $sql)) {
    echo "Table created/exists.\n";
} else {
    echo "Error creating table: " . mysqli_error($con) . "\n";
}

mysqli_close($con);
?>
