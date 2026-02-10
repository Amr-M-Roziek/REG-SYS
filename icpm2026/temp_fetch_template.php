<?php
$con = mysqli_connect('127.0.0.1', 'root', '', 'icpm2026');
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}
$query = mysqli_query($con, "SELECT elements FROM certificate_templates WHERE name='Final-CME' ORDER BY id DESC LIMIT 1");
if ($row = mysqli_fetch_assoc($query)) {
    echo $row['elements'];
} else {
    echo "NOT_FOUND";
}
?>