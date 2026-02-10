<?php
session_start();
include'dbconnection.php';
require_once 'includes/auth_helper.php';

require_permission('admin_manage');

$id = intval($_GET['id']);

// Prevent deleting protected roles (Super Admin, etc.)
if($id <= 3) {
    echo "<script>alert('Cannot delete system roles'); window.location='manage-roles.php';</script>";
    exit();
}

// Check if assigned to any admin
$check = mysqli_query($con, "SELECT id FROM admin WHERE role_id=$id");
if(mysqli_num_rows($check) > 0) {
    echo "<script>alert('Cannot delete role because it is assigned to one or more admins. Please reassign them first.'); window.location='manage-roles.php';</script>";
    exit();
}

$query = "DELETE FROM admin_roles WHERE id=?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, 'i', $id);

if(mysqli_stmt_execute($stmt)) {
    log_audit("delete_role", "Deleted role ID: $id");
    header("Location: manage-roles.php?msg=deleted");
} else {
    echo "Error deleting role: " . mysqli_error($con);
}
?>
