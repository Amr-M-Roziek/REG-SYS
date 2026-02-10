<?php
session_start();
include'dbconnection.php';
require_once 'includes/auth_helper.php';

require_permission('admin_manage');

$id = intval($_GET['id']);

// Security: Check target role before deletion
$check_stmt = mysqli_prepare($con, "SELECT role_id FROM admin WHERE id=?");
mysqli_stmt_bind_param($check_stmt, 'i', $id);
mysqli_stmt_execute($check_stmt);
$res = mysqli_stmt_get_result($check_stmt);
$target = mysqli_fetch_assoc($res);

if ($target && $target['role_id'] == 1 && $_SESSION['role_id'] != 1) {
    echo "<script>alert('Only Super Admins can delete Super Admins.'); window.location='manage-admins.php';</script>";
    exit();
}

if($id == $_SESSION['id']) {
    echo "<script>alert('You cannot delete yourself!'); window.location='manage-admins.php';</script>";
    exit();
}

$query = "DELETE FROM admin WHERE id=?";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, 'i', $id);

if(mysqli_stmt_execute($stmt)) {
    log_audit("delete_admin", "Deleted admin ID: $id");
    header("Location: manage-admins.php?msg=deleted");
} else {
    echo "Error deleting admin: " . mysqli_error($con);
}
?>
