<?php
require 'dbconnection.php';

$id = '202102734';
$hash = 'b9207f3554b7bd313b76244c4452bc5f';
$salt = 'ICPM2026_Secure_Salt';

echo "Calculated Hash: " . md5($id . $salt) . "\n";
echo "Provided Hash:   " . $hash . "\n";

if (md5($id . $salt) === $hash) {
    echo "Hash Match: YES\n";
} else {
    echo "Hash Match: NO\n";
}

// Simulate DB Check
$dbs = ['regsys_participant', 'regsys_reg', 'icpmvibe_icpm2026'];
$found = false;

foreach ($dbs as $db) {
    if (mysqli_select_db($con, $db)) {
        $q = mysqli_query($con, "SELECT * FROM users WHERE id='$id'");
        if ($q && mysqli_num_rows($q) > 0) {
            $u = mysqli_fetch_assoc($q);
            echo "User Found in: $db\n";
            echo "Name: " . $u['fname'] . " " . $u['lname'] . "\n";
            $found = true;
            break;
        }
    }
}

if (!$found) {
    echo "User NOT FOUND in any local database.\n";
}
?>
