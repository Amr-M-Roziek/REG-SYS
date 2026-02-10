<?php
// run_migration.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DB_SERVER','localhost');
define('DB_NAME', 'regsys_poster26');

// Include the connection file
require_once 'c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/dbconnection.php';

if (!$con) {
    die("Connection failed even after including dbconnection.php\n");
}

echo "Connected successfully using dbconnection.php logic.\n";

// 1. Add access_scope to admin table
$check_admin = mysqli_query($con, "SHOW COLUMNS FROM admin LIKE 'access_scope'");
if (mysqli_num_rows($check_admin) == 0) {
    echo "Adding access_scope column to admin table...\n";
    $sql = "ALTER TABLE admin ADD COLUMN access_scope ENUM('scientific', 'poster', 'both') NOT NULL DEFAULT 'both' AFTER password";
    if (mysqli_query($con, $sql)) {
        echo "Successfully added access_scope to admin table.\n";
    } else {
        echo "Error adding access_scope to admin: " . mysqli_error($con) . "\n";
    }
} else {
    echo "access_scope column already exists in admin table.\n";
}

// 2. Add source_system to users table
$check_users = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'source_system'");
if (mysqli_num_rows($check_users) == 0) {
    echo "Adding source_system column to users table...\n";
    $sql = "ALTER TABLE users ADD COLUMN source_system ENUM('scientific', 'poster', 'both') NOT NULL DEFAULT 'both' AFTER id";
    if (mysqli_query($con, $sql)) {
        echo "Successfully added source_system to users table.\n";
    } else {
        echo "Error adding source_system to users: " . mysqli_error($con) . "\n";
    }
} else {
    echo "source_system column already exists in users table.\n";
}

// 3. Create admin_audit_logs table if not exists
$check_logs = mysqli_query($con, "SHOW TABLES LIKE 'admin_audit_logs'");
if (mysqli_num_rows($check_logs) == 0) {
    echo "Creating admin_audit_logs table...\n";
    $sql = "CREATE TABLE `admin_audit_logs` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `admin_id` int(11) NOT NULL,
      `admin_username` varchar(255) NOT NULL,
      `action` varchar(255) NOT NULL,
      `details` text DEFAULT NULL,
      `ip_address` varchar(45) DEFAULT NULL,
      `system_context` enum('scientific','poster') NOT NULL,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (mysqli_query($con, $sql)) {
        echo "Successfully created admin_audit_logs table.\n";
    } else {
        echo "Error creating admin_audit_logs: " . mysqli_error($con) . "\n";
    }
} else {
    echo "admin_audit_logs table already exists.\n";
}

echo "Migration completed.\n";
?>
