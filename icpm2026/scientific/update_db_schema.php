<?php
include 'dbconnection.php';

$columns = [
    'supervisor_choice' => "VARCHAR(10) DEFAULT 'no'",
    'supervisor_name' => "VARCHAR(255) DEFAULT NULL",
    'supervisor_nationality' => "VARCHAR(255) DEFAULT NULL",
    'supervisor_contact' => "VARCHAR(20) DEFAULT NULL",
    'supervisor_email' => "VARCHAR(255) DEFAULT NULL"
];

foreach ($columns as $col => $def) {
    $check = mysqli_query($con, "SHOW COLUMNS FROM users LIKE '$col'");
    if (mysqli_num_rows($check) == 0) {
        $sql = "ALTER TABLE users ADD COLUMN $col $def";
        if (mysqli_query($con, $sql)) {
            echo "Added column $col successfully.\n";
        } else {
            echo "Error adding column $col: " . mysqli_error($con) . "\n";
        }
    } else {
        echo "Column $col already exists.\n";
    }
}

echo "Database schema update completed.\n";
?>
