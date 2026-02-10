<?php
include 'dbconnection.php';

echo "<h1>Database Schema Updater</h1>";

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Connected to database.<br>";

// Helper function to check if column exists
function columnExists($con, $table, $column) {
    $result = mysqli_query($con, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return (mysqli_num_rows($result) > 0);
}

// 1. Add 'profession'
if (!columnExists($con, 'users', 'profession')) {
    $sql = "ALTER TABLE `users` ADD `profession` VARCHAR(255) NULL AFTER `email`";
    if (mysqli_query($con, $sql)) {
        echo "<span style='color:green'>Added column 'profession'</span><br>";
    } else {
        echo "<span style='color:red'>Error adding 'profession': " . mysqli_error($con) . "</span><br>";
    }
} else {
    echo "Column 'profession' already exists.<br>";
}

// 2. Add 'organization'
if (!columnExists($con, 'users', 'organization')) {
    $sql = "ALTER TABLE `users` ADD `organization` VARCHAR(255) NULL AFTER `profession`";
    if (mysqli_query($con, $sql)) {
        echo "<span style='color:green'>Added column 'organization'</span><br>";
    } else {
        echo "<span style='color:red'>Error adding 'organization': " . mysqli_error($con) . "</span><br>";
    }
} else {
    echo "Column 'organization' already exists.<br>";
}

// 3. Add 'category'
if (!columnExists($con, 'users', 'category')) {
    $sql = "ALTER TABLE `users` ADD `category` VARCHAR(100) NULL AFTER `organization`";
    if (mysqli_query($con, $sql)) {
        echo "<span style='color:green'>Added column 'category'</span><br>";
    } else {
        echo "<span style='color:red'>Error adding 'category': " . mysqli_error($con) . "</span><br>";
    }
} else {
    echo "Column 'category' already exists.<br>";
}

// 4. Handle 'created_at' vs 'posting_date'
if (!columnExists($con, 'users', 'created_at')) {
    if (columnExists($con, 'users', 'posting_date')) {
        // Rename posting_date to created_at
        $sql = "ALTER TABLE `users` CHANGE `posting_date` `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
        if (mysqli_query($con, $sql)) {
            echo "<span style='color:green'>Renamed 'posting_date' to 'created_at'</span><br>";
        } else {
            echo "<span style='color:red'>Error renaming 'posting_date': " . mysqli_error($con) . "</span><br>";
        }
    } else {
        // Create created_at if posting_date doesn't exist
        $sql = "ALTER TABLE `users` ADD `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP";
        if (mysqli_query($con, $sql)) {
            echo "<span style='color:green'>Added column 'created_at'</span><br>";
        } else {
            echo "<span style='color:red'>Error adding 'created_at': " . mysqli_error($con) . "</span><br>";
        }
    }
} else {
    echo "Column 'created_at' already exists.<br>";
}

echo "<h2>Schema Update Complete</h2>";
echo "<a href='manage-users.php'>Go back to Manage Users</a>";
?>
