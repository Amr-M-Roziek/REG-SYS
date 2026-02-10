<?php
// update_schema.php
// Script to add certificate_sent column to users table

require_once 'dbconnection.php';

if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

echo "Checking database schema...\n";

// Check if column exists
$checkQuery = "SHOW COLUMNS FROM users LIKE 'certificate_sent'";
$result = mysqli_query($con, $checkQuery);

if (mysqli_num_rows($result) == 0) {
    echo "Adding 'certificate_sent' column...\n";
    $alterQuery = "ALTER TABLE users ADD COLUMN certificate_sent TINYINT(1) DEFAULT 0";
    if (mysqli_query($con, $alterQuery)) {
        echo "Column 'certificate_sent' added successfully.\n";
        
        // Add index
        $indexQuery = "ALTER TABLE users ADD INDEX idx_certificate_sent (certificate_sent)";
        if (mysqli_query($con, $indexQuery)) {
            echo "Index on 'certificate_sent' added successfully.\n";
        } else {
            echo "Error adding index: " . mysqli_error($con) . "\n";
        }
    } else {
        echo "Error adding column: " . mysqli_error($con) . "\n";
    }
} else {
    echo "Column 'certificate_sent' already exists.\n";
}

echo "Schema update completed.\n";
?>
