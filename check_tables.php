<?php
include 'c:/xampp/htdocs/reg-sys.com/icpm2026/poster26/admin/dbconnection.php';
$result = mysqli_query($con, "SHOW TABLES");
while ($row = mysqli_fetch_array($result)) {
    echo $row[0] . "\n";
}
?>
