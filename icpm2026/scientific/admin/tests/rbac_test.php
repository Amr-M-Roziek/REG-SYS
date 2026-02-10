<?php
session_start();
include __DIR__ . '/../dbconnection.php';
require_once __DIR__ . '/../permission_helper.php';

echo "Running RBAC Tests...\n";

// Helper to run assertions
function assert_true($condition, $message) {
    if ($condition) {
        echo "[PASS] $message\n";
    } else {
        echo "[FAIL] $message\n";
    }
}

function assert_false($condition, $message) {
    if (!$condition) {
        echo "[PASS] $message\n";
    } else {
        echo "[FAIL] $message\n";
    }
}

// 1. Test Super Admin (ID 1)
$_SESSION['id'] = 1;
assert_true(has_permission($con, 'view_users'), "Super Admin has view_users");
assert_true(has_permission($con, 'delete_users'), "Super Admin has delete_users");
assert_true(has_permission($con, 'manage_roles'), "Super Admin has manage_roles");

// 2. Test Editor Role
// Create a temporary editor user
$username = 'test_editor_' . time();
$email = 'test_editor@example.com';
$password = md5('password');
$stmt = mysqli_prepare($con, "INSERT INTO admin (username, email, password) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'sss', $username, $email, $password);
mysqli_stmt_execute($stmt);
$editor_id = mysqli_insert_id($con);

// Get Editor Role ID
$res = mysqli_query($con, "SELECT id FROM admin_roles WHERE name='Editor'");
$row = mysqli_fetch_assoc($res);
$editor_role_id = $row['id'];

// Assign Role
mysqli_query($con, "INSERT INTO admin_role_assignments (admin_id, role_id) VALUES ($editor_id, $editor_role_id)");

// Test Permissions
$_SESSION['id'] = $editor_id;
assert_true(has_permission($con, 'view_users'), "Editor has view_users");
assert_true(has_permission($con, 'edit_users'), "Editor has edit_users");
assert_false(has_permission($con, 'delete_users'), "Editor does NOT have delete_users");
assert_false(has_permission($con, 'manage_admins'), "Editor does NOT have manage_admins");

// Cleanup Editor
mysqli_query($con, "DELETE FROM admin WHERE id=$editor_id");
mysqli_query($con, "DELETE FROM admin_role_assignments WHERE admin_id=$editor_id"); // Cascade should handle this but just in case


// 3. Test Viewer Role
// Create a temporary viewer user
$username = 'test_viewer_' . time();
$email = 'test_viewer@example.com';
$stmt = mysqli_prepare($con, "INSERT INTO admin (username, email, password) VALUES (?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'sss', $username, $email, $password);
mysqli_stmt_execute($stmt);
$viewer_id = mysqli_insert_id($con);

// Get Viewer Role ID
$res = mysqli_query($con, "SELECT id FROM admin_roles WHERE name='Viewer'");
$row = mysqli_fetch_assoc($res);
$viewer_role_id = $row['id'];

// Assign Role
mysqli_query($con, "INSERT INTO admin_role_assignments (admin_id, role_id) VALUES ($viewer_id, $viewer_role_id)");

// Test Permissions
$_SESSION['id'] = $viewer_id;
assert_true(has_permission($con, 'view_users'), "Viewer has view_users");
assert_false(has_permission($con, 'edit_users'), "Viewer does NOT have edit_users");
assert_false(has_permission($con, 'delete_users'), "Viewer does NOT have delete_users");

// Cleanup Viewer
mysqli_query($con, "DELETE FROM admin WHERE id=$viewer_id");
mysqli_query($con, "DELETE FROM admin_role_assignments WHERE admin_id=$viewer_id");

echo "Tests Completed.\n";
?>
