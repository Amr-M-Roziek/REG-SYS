<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'dbconnection.php';

if ($con) {
    echo "Database connection successful!\n";
    echo "Host: " . DB_SERVER . "\n";
    echo "User: " . DB_USER . "\n";
    echo "Database: " . DB_NAME . "\n";
    
    // Check if new columns exist
    $result = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'supervisor_%'");
    if ($result) {
        echo "Supervisor columns found: " . mysqli_num_rows($result) . "\n";
        while ($row = mysqli_fetch_assoc($result)) {
            echo " - " . $row['Field'] . "\n";
        }
    } else {
        echo "Error checking columns: " . mysqli_error($con) . "\n";
    }
} else {
    echo "Database connection failed: " . mysqli_connect_error() . "\n";
}
?>
