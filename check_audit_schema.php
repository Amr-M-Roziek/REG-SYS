<?php
include 'c:/xampp/htdocs/reg-sys.com/icpm2026/poster26/admin/dbconnection.php';
$result = mysqli_query($con, "SHOW COLUMNS FROM admin_audit_logs");
while ($row = mysqli_fetch_assoc($result)) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
