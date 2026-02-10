<?php
require_once 'session_setup.php';

// Available Permissions Definitions
$AVAILABLE_PERMISSIONS = [
    'view_users' => 'View Users (Read Only)',
    'edit_users' => 'Edit Users (Update Profile)',
    'unlink_users' => 'Unlink Users (From Team)',
    'delete_users' => 'Delete Users',
    'export_data' => 'Export Data (Excel, SQL)',
    'manage_admins' => 'Manage Admins (Create, Edit, Assign Roles)',
    'manage_roles' => 'Manage Roles (Create, Edit Roles & Permissions)',
    'view_logs' => 'View Audit Logs'
];

// Helper to check permissions
function has_permission($con, $required_perm) {
    if (empty($_SESSION['id'])) return false;
    $admin_id = $_SESSION['id'];

    // Hardcoded Super Admin ID 1 always has access
    if ($admin_id == 1) return true;

    $sql = "SELECT r.permissions 
            FROM admin_role_assignments ara 
            JOIN admin_roles r ON ara.role_id = r.id 
            WHERE ara.admin_id = ?";
    
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        // Fallback or error logging could go here
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $admin_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($res)) {
        $perms = json_decode($row['permissions'], true);
        if (is_array($perms)) {
            // Check for exact permission
            if (in_array($required_perm, $perms)) {
                return true;
            }
        }
    }
    return false;
}
