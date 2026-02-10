<?php
echo "Starting seed script...\n";
require_once 'dbconnection.php';
echo "DB Connected.\n";
require_once 'permission_helper.php';
echo "Helper loaded.\n";

// Define Roles
$roles = [
    'Super Admin' => [
        'description' => 'Full system access with all permissions',
        'permissions' => array_keys($AVAILABLE_PERMISSIONS)
    ],
    'Admin' => [
        'description' => 'Elevated privileges for managing users and content',
        'permissions' => ['view_users', 'edit_users', 'unlink_users', 'delete_users', 'export_data', 'view_logs']
    ],
    'Viewer' => [
        'description' => 'Read-only access to view content',
        'permissions' => ['view_users']
    ],
    'Editor' => [
        'description' => 'Content creation and editing permissions',
        'permissions' => ['view_users', 'edit_users']
    ],
    'Moderator' => [
        'description' => 'Content approval and user management',
        'permissions' => ['view_users', 'edit_users', 'unlink_users', 'view_logs']
    ]
];

foreach ($roles as $name => $data) {
    echo "Processing $name...\n";
    $desc = $data['description'];
    $perms = json_encode($data['permissions']);

    // Check if role exists
    $stmt = mysqli_prepare($con, "SELECT id FROM admin_roles WHERE name = ?");
    mysqli_stmt_bind_param($stmt, 's', $name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo "Role '$name' already exists. Updating permissions...\n";
        $stmt = mysqli_prepare($con, "UPDATE admin_roles SET description=?, permissions=? WHERE name=?");
        mysqli_stmt_bind_param($stmt, 'sss', $desc, $perms, $name);
        mysqli_stmt_execute($stmt);
    } else {
        echo "Creating role '$name'...\n";
        $stmt = mysqli_prepare($con, "INSERT INTO admin_roles (name, description, permissions) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sss', $name, $desc, $perms);
        mysqli_stmt_execute($stmt);
    }
}

echo "Roles seeded successfully.\n";
?>
