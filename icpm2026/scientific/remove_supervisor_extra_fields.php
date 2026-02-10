<?php
require_once('dbconnection.php');

$columns = [
    'supervisor_employee_id',
    'supervisor_department'
];

foreach ($columns as $col) {
    $check = mysqli_query($con, "SHOW COLUMNS FROM users LIKE '$col'");
    if (mysqli_num_rows($check) > 0) {
        $sql = "ALTER TABLE users DROP COLUMN $col";
        if (mysqli_query($con, $sql)) {
            echo "Dropped column $col\n";
        } else {
            echo "Error dropping column $col: " . mysqli_error($con) . "\n";
        }
    } else {
        echo "Column $col does not exist\n";
    }
}

echo "Schema cleanup completed.\n";
?>
