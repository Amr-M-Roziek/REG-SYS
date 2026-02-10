<?php
include 'dbconnection.php';

$columns = [
    'supervisor_choice',
    'supervisor_name',
    'supervisor_nationality',
    'supervisor_contact',
    'supervisor_email'
];

foreach ($columns as $col) {
    $check = mysqli_query($con, "SHOW COLUMNS FROM users LIKE '$col'");
    if (mysqli_num_rows($check) > 0) {
        $sql = "ALTER TABLE users DROP COLUMN $col";
        if (mysqli_query($con, $sql)) {
            echo "Dropped column $col successfully.\n";
        } else {
            echo "Error dropping column $col: " . mysqli_error($con) . "\n";
        }
    } else {
        echo "Column $col does not exist.\n";
    }
}

echo "Database schema rollback completed.\n";
?>
