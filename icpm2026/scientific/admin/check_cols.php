<?php
include 'c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/dbconnection.php';
$res = mysqli_query($con, 'SHOW COLUMNS FROM users');
while($row = mysqli_fetch_assoc($res)) {
    echo $row['Field'] . "\n";
}
?>