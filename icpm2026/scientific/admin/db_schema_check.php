<?php
require_once 'dbconnection.php';

echo "Tables in database:\n";
$res = mysqli_query($con, "SHOW TABLES");
while ($row = mysqli_fetch_row($res)) {
    echo "- " . $row[0] . "\n";
}

echo "\nChecking admin_roles table:\n";
$res = mysqli_query($con, "DESCRIBE admin_roles");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "  Table admin_roles does not exist.\n";
}

echo "\nChecking admin_role_assignments table:\n";
$res = mysqli_query($con, "DESCRIBE admin_role_assignments");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        echo "  " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "  Table admin_role_assignments does not exist.\n";
}

echo "\nExisting Roles:\n";
$res = mysqli_query($con, "SELECT * FROM admin_roles");
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        echo "  ID: " . $row['id'] . ", Name: " . $row['name'] . ", Perms: " . $row['permissions'] . "\n";
    }
}
?>
http://localhost:8000/icpm2026/scientific/admin/update-profile.php?uid=2104070256