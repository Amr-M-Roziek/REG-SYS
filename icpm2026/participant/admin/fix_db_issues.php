<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Starting DB Fix for PARTICIPANT (regsys_participant)...\n";

$db_name = 'regsys_participant';
$con = false;

// 1. Try 127.0.0.1 root (TCP)
try {
    echo "Attempt 1: 127.0.0.1 root...\n";
    $con = @mysqli_connect('127.0.0.1', 'root', '', $db_name);
} catch (Exception $e) {
    echo "Failed: " . $e->getMessage() . "\n";
}

// 2. Try localhost root (Socket)
if (!$con) {
    try {
        echo "Attempt 2: localhost root...\n";
        $con = @mysqli_connect('localhost', 'root', '', $db_name);
    } catch (Exception $e) {
        echo "Failed: " . $e->getMessage() . "\n";
    }
}

// 3. Try Production Credentials
if (!$con) {
    try {
        echo "Attempt 3: localhost regsys_part...\n";
        $con = @mysqli_connect('localhost', 'regsys_part', 'regsys@2025', $db_name);
    } catch (Exception $e) {
        echo "Failed: " . $e->getMessage() . "\n";
    }
}

if (!$con) {
    die("CRITICAL: Could not connect to database.\n");
}

echo "Connected successfully!\n";

// 1. Fix email_logs table
echo "Checking email_logs table...\n";
$query = "CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    recipient_email VARCHAR(191) NOT NULL,
    subject VARCHAR(255),
    status VARCHAR(50),
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (mysqli_query($con, $query)) {
    echo " - email_logs table check/create: OK\n";
} else {
    echo " - email_logs table check/create: FAILED - " . mysqli_error($con) . "\n";
}

// 2. Fix certificate_sent column in users
echo "Checking certificate_sent column in users...\n";
$checkCol = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'certificate_sent'");
if (mysqli_num_rows($checkCol) == 0) {
    echo " - Column missing. Adding...\n";
    if (mysqli_query($con, "ALTER TABLE users ADD COLUMN certificate_sent TINYINT(1) DEFAULT 0")) {
        echo " - Column added successfully.\n";
        mysqli_query($con, "ALTER TABLE users ADD INDEX idx_certificate_sent (certificate_sent)");
    } else {
        echo " - Failed to add column: " . mysqli_error($con) . "\n";
    }
} else {
    echo " - Column exists.\n";
}

echo "Done.\n";
?>