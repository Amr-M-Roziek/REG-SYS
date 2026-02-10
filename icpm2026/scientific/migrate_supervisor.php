<?php
require_once 'dbconnection.php';

echo "Starting migration...\n";

// Function to add column if not exists
function add_column_if_missing($con, $table, $column, $definition) {
    $check = mysqli_query($con, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$table' AND COLUMN_NAME='$column'");
    if ($check && mysqli_num_rows($check) == 0) {
        $sql = "ALTER TABLE $table ADD COLUMN `$column` $definition";
        if (mysqli_query($con, $sql)) {
            echo "Added column $column to $table\n";
        } else {
            echo "Error adding column $column: " . mysqli_error($con) . "\n";
        }
    } else {
        echo "Column $column already exists in $table\n";
    }
}

add_column_if_missing($con, 'users', 'supervisor_choice', "VARCHAR(3) NULL DEFAULT 'no'");
add_column_if_missing($con, 'users', 'supervisor_name', "VARCHAR(255) NULL");
add_column_if_missing($con, 'users', 'supervisor_nationality', "VARCHAR(255) NULL");
add_column_if_missing($con, 'users', 'supervisor_contact', "VARCHAR(20) NULL");
add_column_if_missing($con, 'users', 'supervisor_email', "VARCHAR(255) NULL");

echo "Migration completed.\n";
?>