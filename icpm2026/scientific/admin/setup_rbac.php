<?php
include 'dbconnection.php';

// Create admin_roles table
$sql = "CREATE TABLE IF NOT EXISTS `admin_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `permissions` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($con, $sql)) {
    echo "Table admin_roles created or exists.<br>";
} else {
    echo "Error creating admin_roles: " . mysqli_error($con) . "<br>";
}

// Create admin_role_assignments table
$sql = "CREATE TABLE IF NOT EXISTS `admin_role_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_role` (`admin_id`,`role_id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `admin_role_assignments_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (mysqli_query($con, $sql)) {
    echo "Table admin_role_assignments created or exists.<br>";
} else {
    echo "Error creating admin_role_assignments: " . mysqli_error($con) . "<br>";
}

// Add 'created_at' to admin if not exists (optional, but good for audit)
$res = mysqli_query($con, "SHOW COLUMNS FROM admin LIKE 'created_at'");
if (mysqli_num_rows($res) == 0) {
    mysqli_query($con, "ALTER TABLE admin ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "Added created_at to admin table.<br>";
}

// Seed Roles
$roles = [
    'Super Admin' => [
        'description' => 'Full access to everything',
        'permissions' => ['view_users', 'edit_users', 'unlink_users', 'delete_users', 'export_data', 'manage_admins', 'manage_roles', 'view_logs']
    ],
    'Admin' => [
        'description' => 'Can manage users and export data, but cannot manage admins/roles',
        'permissions' => ['view_users', 'edit_users', 'unlink_users', 'delete_users', 'export_data', 'view_logs']
    ],
    'Editor' => [
        'description' => 'Can view and edit users, but cannot delete or export',
        'permissions' => ['view_users', 'edit_users']
    ],
    'Moderator' => [
        'description' => 'Can view and unlink users',
        'permissions' => ['view_users', 'unlink_users']
    ],
    'Viewer' => [
        'description' => 'Read-only access to users',
        'permissions' => ['view_users']
    ]
];

foreach ($roles as $name => $data) {
    $perms = json_encode($data['permissions']);
    $desc = $data['description'];
    
    // Check if exists
    $stmt = mysqli_prepare($con, "SELECT id FROM admin_roles WHERE name = ?");
    mysqli_stmt_bind_param($stmt, 's', $name);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) == 0) {
        $stmt = mysqli_prepare($con, "INSERT INTO admin_roles (name, description, permissions) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sss', $name, $desc, $perms);
        mysqli_stmt_execute($stmt);
        echo "Created role: $name<br>";
    } else {
        echo "Role $name already exists.<br>";
    }
}

// Assign Super Admin role to ID 1
$admin_id = 1;
$role_name = 'Super Admin';
$res = mysqli_query($con, "SELECT id FROM admin_roles WHERE name='$role_name'");
if ($row = mysqli_fetch_assoc($res)) {
    $role_id = $row['id'];
    
    // Check assignment
    $res2 = mysqli_query($con, "SELECT * FROM admin_role_assignments WHERE admin_id=$admin_id AND role_id=$role_id");
    if (mysqli_num_rows($res2) == 0) {
        mysqli_query($con, "INSERT INTO admin_role_assignments (admin_id, role_id) VALUES ($admin_id, $role_id)");
        echo "Assigned Super Admin role to Admin ID 1.<br>";
    }
}

echo "RBAC Setup Completed.";
?>
