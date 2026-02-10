<?php
require_once 'session_setup.php';

include 'dbconnection.php';
require_once 'permission_helper.php';

// Auth Check
if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Helper to log actions
function log_action($con, $action, $details) {
    $admin_id = $_SESSION['id'];
    $admin_username = isset($_SESSION['login']) ? $_SESSION['login'] : 'Unknown';
    $ip = $_SERVER['REMOTE_ADDR'];
    $system_context = 'poster';
    $stmt = mysqli_prepare($con, "INSERT INTO admin_audit_logs (admin_id, admin_username, action, details, ip_address, system_context) VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'isssss', $admin_id, $admin_username, $action, $details, $ip, $system_context);
    mysqli_stmt_execute($stmt);
}

// CSRF Check for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : (isset($headers['X-CSRF-Token']) ? $headers['X-CSRF-Token'] : '');
    
    if (empty($token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        // Debug logging
        $debug = "Session ID: " . session_id() . "\n";
        $debug .= "Session Token: " . (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : 'NULL') . "\n";
        $debug .= "Post Token: " . $token . "\n";
        file_put_contents('csrf_debug.log', $debug);
        
        http_response_code(403);
        echo json_encode(['error' => 'Invalid CSRF token', 'debug' => 'Check csrf_debug.log']);
        exit;
    }
}

// Available Permissions Definitions are in permission_helper.php

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

if ($action === 'get_permissions') {
    if (!has_permission($con, 'manage_roles')) {
        echo json_encode(['error' => 'Permission denied']);
        exit;
    }
    echo json_encode(['status' => 'ok', 'permissions' => $AVAILABLE_PERMISSIONS]);
    exit;
}

if ($action === 'get_roles') {
    if (!has_permission($con, 'manage_admins') && !has_permission($con, 'manage_roles')) {
        echo json_encode(['error' => 'Permission denied']);
        exit;
    }
    $res = mysqli_query($con, "SELECT * FROM admin_roles ORDER BY name ASC");
    $roles = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $row['permissions'] = json_decode($row['permissions']);
        $roles[] = $row;
    }
    echo json_encode(['status' => 'ok', 'roles' => $roles]);
    exit;
}

if ($action === 'get_admins') {
    if (!has_permission($con, 'manage_admins')) {
        echo json_encode(['error' => 'Permission denied']);
        exit;
    }
    // Get admins and their roles
    $sql = "SELECT a.id, a.username, a.email, a.access_scope, a.created_at, GROUP_CONCAT(r.id) as role_ids, GROUP_CONCAT(r.name) as role_names 
            FROM admin a 
            LEFT JOIN admin_role_assignments ara ON a.id = ara.admin_id 
            LEFT JOIN admin_roles r ON ara.role_id = r.id 
            GROUP BY a.id";
    $res = mysqli_query($con, $sql);
    $admins = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $row['role_ids'] = $row['role_ids'] ? explode(',', $row['role_ids']) : [];
        $row['role_names'] = $row['role_names'] ? explode(',', $row['role_names']) : [];
        // Don't send password
        $admins[] = $row;
    }
    echo json_encode(['status' => 'ok', 'admins' => $admins]);
    exit;
}

if ($action === 'save_admin') {
    if (!has_permission($con, 'manage_admins')) {
        echo json_encode(['error' => 'Permission denied']);
        exit;
    }
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $roles = isset($_POST['roles']) ? $_POST['roles'] : []; // Array of role IDs
    $scope = isset($_POST['access_scope']) ? $_POST['access_scope'] : 'poster';

    $valid_scopes = ['poster', 'scientific', 'both'];
    if (!in_array($scope, $valid_scopes)) {
        $scope = 'poster';
    }

    if (empty($username)) {
        echo json_encode(['error' => 'Username is required']);
        exit;
    }

    // Password validation for new users or if password is provided
    if (($id === 0 || !empty($password))) {
        if (strlen($password) < 8) {
            echo json_encode(['error' => 'Password must be at least 8 characters']);
            exit;
        }
        // Add more strength checks if needed
    }

    mysqli_begin_transaction($con);
    try {
        if ($id === 0) {
            // Create
            // Check existence
            $stmt = mysqli_prepare($con, "SELECT id FROM admin WHERE username = ?");
            mysqli_stmt_bind_param($stmt, 's', $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            if (mysqli_stmt_num_rows($stmt) > 0) {
                throw new Exception("Username already exists");
            }

            $hash = md5($password); // Legacy system uses MD5, continuing pattern (should upgrade but keeping consistent)
            // Note: User prompt asked for "Strength validation" but system uses MD5. 
            // I will use MD5 to match index.php login logic: $pass=md5($_POST['password']);
            
            $stmt = mysqli_prepare($con, "INSERT INTO admin (username, password, email, access_scope) VALUES (?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssss', $username, $hash, $email, $scope);
            mysqli_stmt_execute($stmt);
            $id = mysqli_insert_id($con);
            log_action($con, 'create_admin', "Created admin $username (ID: $id) with scope $scope");
        } else {
            // Update
            if (!empty($password)) {
                $hash = md5($password);
                $stmt = mysqli_prepare($con, "UPDATE admin SET username=?, email=?, password=?, access_scope=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, 'ssssi', $username, $email, $hash, $scope, $id);
            } else {
                $stmt = mysqli_prepare($con, "UPDATE admin SET username=?, email=?, access_scope=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, 'sssi', $username, $email, $scope, $id);
            }
            mysqli_stmt_execute($stmt);
            log_action($con, 'update_admin', "Updated admin $username (ID: $id) scope to $scope");
        }

        // Update Roles
        // First remove all
        mysqli_query($con, "DELETE FROM admin_role_assignments WHERE admin_id=$id");
        
        // Add new
        if (!empty($roles)) {
            $stmt = mysqli_prepare($con, "INSERT INTO admin_role_assignments (admin_id, role_id) VALUES (?, ?)");
            foreach ($roles as $rid) {
                mysqli_stmt_bind_param($stmt, 'ii', $id, $rid);
                mysqli_stmt_execute($stmt);
            }
        }

        mysqli_commit($con);
        echo json_encode(['status' => 'ok']);
    } catch (Exception $e) {
        mysqli_rollback($con);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete_admin') {
    if (!has_permission($con, 'manage_admins')) {
        echo json_encode(['error' => 'Permission denied']);
        exit;
    }
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid ID']);
        exit;
    }

    if ($id == $_SESSION['id']) {
        echo json_encode(['error' => 'Cannot delete yourself']);
        exit;
    }

    $stmt = mysqli_prepare($con, "DELETE FROM admin WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (mysqli_stmt_execute($stmt)) {
        log_action($con, 'delete_admin', "Deleted admin ID $id");
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['error' => 'Failed to delete']);
    }
    exit;
}

if ($action === 'save_role') {
    if (!has_permission($con, 'manage_roles')) {
        echo json_encode(['error' => 'Permission denied']);
        exit;
    }
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : []; // Array of permission keys

    if (empty($name)) {
        echo json_encode(['error' => 'Role name is required']);
        exit;
    }

    // Validate permissions against available keys
    global $AVAILABLE_PERMISSIONS;
    $valid_perms = [];
    foreach ($permissions as $p) {
        if (array_key_exists($p, $AVAILABLE_PERMISSIONS)) {
            $valid_perms[] = $p;
        }
    }
    $json_perms = json_encode($valid_perms);

    if ($id === 0) {
        // Create
        // Check duplicate name
        $stmt = mysqli_prepare($con, "SELECT id FROM admin_roles WHERE name = ?");
        mysqli_stmt_bind_param($stmt, 's', $name);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            echo json_encode(['error' => 'Role name already exists']);
            exit;
        }

        $stmt = mysqli_prepare($con, "INSERT INTO admin_roles (name, description, permissions) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sss', $name, $description, $json_perms);
        if (mysqli_stmt_execute($stmt)) {
            $new_id = mysqli_insert_id($con);
            log_action($con, 'create_role', "Created role $name (ID: $new_id)");
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['error' => 'Failed to create role']);
        }
    } else {
        // Update
        // Check duplicate name on other IDs
        $stmt = mysqli_prepare($con, "SELECT id FROM admin_roles WHERE name = ? AND id != ?");
        mysqli_stmt_bind_param($stmt, 'si', $name, $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            echo json_encode(['error' => 'Role name already exists']);
            exit;
        }

        $stmt = mysqli_prepare($con, "UPDATE admin_roles SET name=?, description=?, permissions=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'sssi', $name, $description, $json_perms, $id);
        if (mysqli_stmt_execute($stmt)) {
            log_action($con, 'update_role', "Updated role $name (ID: $id)");
            echo json_encode(['status' => 'ok']);
        } else {
            echo json_encode(['error' => 'Failed to update role']);
        }
    }
    exit;
}

if ($action === 'delete_role') {
    if (!has_permission($con, 'manage_roles')) {
        echo json_encode(['error' => 'Permission denied']);
        exit;
    }
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        echo json_encode(['error' => 'Invalid ID']);
        exit;
    }

    // Check if role is assigned to any admin
    $stmt = mysqli_prepare($con, "SELECT COUNT(*) FROM admin_role_assignments WHERE role_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if ($count > 0) {
        echo json_encode(['error' => "Cannot delete role. It is assigned to $count admin(s)."]);
        exit;
    }

    $stmt = mysqli_prepare($con, "DELETE FROM admin_roles WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (mysqli_stmt_execute($stmt)) {
        log_action($con, 'delete_role', "Deleted role ID $id");
        echo json_encode(['status' => 'ok']);
    } else {
        echo json_encode(['error' => 'Failed to delete role']);
    }
    exit;
}

echo json_encode(['error' => 'Invalid action']);
