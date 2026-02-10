<?php
// admin/includes/auth_helper.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function mysqli_stmt_execute_compat($stmt, $types = '', $params = []) {
    if (!$stmt) return false;
    if ($types !== '' && !empty($params)) {
        $bind = [$types];
        foreach ($params as $i => $value) {
            $bind[] = &$params[$i];
        }
        if (!call_user_func_array([$stmt, 'bind_param'], $bind)) {
            return false;
        }
    }
    return mysqli_stmt_execute($stmt);
}

// Ensure Audit Table Exists (Self-Repair)
if (isset($GLOBALS['con']) && $GLOBALS['con'] instanceof mysqli) {
    // 1. Admin Roles
    $checkTable = mysqli_query($GLOBALS['con'], "SHOW TABLES LIKE 'admin_roles'");
    if ($checkTable && mysqli_num_rows($checkTable) == 0) {
        $createSql = "CREATE TABLE IF NOT EXISTS `admin_roles` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `role_name` varchar(50) NOT NULL,
          `description` varchar(255) DEFAULT NULL,
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `role_name` (`role_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($GLOBALS['con'], $createSql);
        
        // Seed Roles
        $seedSql = "INSERT INTO `admin_roles` (`role_name`, `description`) VALUES 
        ('Super Admin', 'Full access to all features'),
        ('Content Admin', 'Can manage users and content'),
        ('Support Admin', 'Read-only access to users')
        ON DUPLICATE KEY UPDATE description=VALUES(description)";
        mysqli_query($GLOBALS['con'], $seedSql);
    }

    // 2. Admin Permissions
    $checkTable = mysqli_query($GLOBALS['con'], "SHOW TABLES LIKE 'admin_permissions'");
    if ($checkTable && mysqli_num_rows($checkTable) == 0) {
        $createSql = "CREATE TABLE IF NOT EXISTS `admin_permissions` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `permission_key` varchar(50) NOT NULL,
          `description` varchar(255) DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `permission_key` (`permission_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($GLOBALS['con'], $createSql);

        // Seed Permissions
        $seedSql = "INSERT INTO `admin_permissions` (`permission_key`, `description`) VALUES 
        ('admin_manage', 'Manage administrators and roles'),
        ('user_view', 'View users'),
        ('user_edit', 'Edit users'),
        ('user_delete', 'Delete users'),
        ('bulk_upload', 'Bulk upload users'),
        ('export_data', 'Export user data')
        ON DUPLICATE KEY UPDATE description=VALUES(description)";
        mysqli_query($GLOBALS['con'], $seedSql);
    }

    // 3. Role Permissions
    $checkTable = mysqli_query($GLOBALS['con'], "SHOW TABLES LIKE 'role_permissions'");
    if ($checkTable && mysqli_num_rows($checkTable) == 0) {
        $createSql = "CREATE TABLE IF NOT EXISTS `role_permissions` (
          `role_id` int(11) NOT NULL,
          `permission_id` int(11) NOT NULL,
          PRIMARY KEY (`role_id`,`permission_id`),
          FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`) ON DELETE CASCADE,
          FOREIGN KEY (`permission_id`) REFERENCES `admin_permissions` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($GLOBALS['con'], $createSql);

        // Seed Role Permissions (Super Admin)
        $seedSql = "INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) 
        SELECT 1, id FROM `admin_permissions`";
        mysqli_query($GLOBALS['con'], $seedSql);
    }

    $checkTable = mysqli_query($GLOBALS['con'], "SHOW TABLES LIKE 'admin_audit_logs'");
    if ($checkTable && mysqli_num_rows($checkTable) == 0) {
        $createSql = "CREATE TABLE IF NOT EXISTS admin_audit_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action VARCHAR(50) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($GLOBALS['con'], $createSql);
    }

    // 4. Update Admin Table Structure
    // Check for 'role_id' column
    $checkCol = mysqli_query($GLOBALS['con'], "SHOW COLUMNS FROM `admin` LIKE 'role_id'");
    if ($checkCol && mysqli_num_rows($checkCol) == 0) {
        mysqli_query($GLOBALS['con'], "ALTER TABLE `admin` ADD COLUMN `role_id` int(11) DEFAULT NULL");
    }

    // Check for 'email' column
    $checkCol = mysqli_query($GLOBALS['con'], "SHOW COLUMNS FROM `admin` LIKE 'email'");
    if ($checkCol && mysqli_num_rows($checkCol) == 0) {
        mysqli_query($GLOBALS['con'], "ALTER TABLE `admin` ADD COLUMN `email` varchar(255) DEFAULT NULL");
    }

    // Check for 'created_at' column
    $checkCol = mysqli_query($GLOBALS['con'], "SHOW COLUMNS FROM `admin` LIKE 'created_at'");
    if ($checkCol && mysqli_num_rows($checkCol) == 0) {
        mysqli_query($GLOBALS['con'], "ALTER TABLE `admin` ADD COLUMN `created_at` timestamp DEFAULT CURRENT_TIMESTAMP");
    }
    
    // Ensure Certificate Templates Table Exists
    $checkTable = mysqli_query($GLOBALS['con'], "SHOW TABLES LIKE 'certificate_templates'");
    if ($checkTable && mysqli_num_rows($checkTable) == 0) {
        $createSql = "CREATE TABLE IF NOT EXISTS certificate_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            data LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($GLOBALS['con'], $createSql);
    }

    $checkTable = mysqli_query($GLOBALS['con'], "SHOW TABLES LIKE 'email_logs'");
    if ($checkTable && mysqli_num_rows($checkTable) == 0) {
        $createSql = "CREATE TABLE IF NOT EXISTS email_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            recipient_email VARCHAR(255),
            subject VARCHAR(255),
            status VARCHAR(50),
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($GLOBALS['con'], $createSql);
    }

    $checkTable = mysqli_query($GLOBALS['con'], "SHOW TABLES LIKE 'db_transfer_jobs'");
    if ($checkTable && mysqli_num_rows($checkTable) == 0) {
        $createSql = "CREATE TABLE IF NOT EXISTS db_transfer_jobs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            source_db VARCHAR(50) NOT NULL,
            target_db VARCHAR(50) NOT NULL,
            criteria TEXT,
            total_rows INT DEFAULT 0,
            success_rows INT DEFAULT 0,
            failed_rows INT DEFAULT 0,
            status VARCHAR(20) DEFAULT 'pending',
            error_message TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($GLOBALS['con'], $createSql);
    }

    $checkTable = mysqli_query($GLOBALS['con'], "SHOW TABLES LIKE 'attendance_sessions'");
    if ($checkTable && mysqli_num_rows($checkTable) == 0) {
        $createSql = "CREATE TABLE IF NOT EXISTS attendance_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            module VARCHAR(50) DEFAULT NULL,
            description TEXT,
            start_time DATETIME DEFAULT NULL,
            end_time DATETIME DEFAULT NULL,
            location VARCHAR(255) DEFAULT NULL,
            created_by INT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($GLOBALS['con'], $createSql);
    }

    $checkTable = mysqli_query($GLOBALS['con'], "SHOW TABLES LIKE 'attendance_events'");
    if ($checkTable && mysqli_num_rows($checkTable) == 0) {
        $createSql = "CREATE TABLE IF NOT EXISTS attendance_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id INT NOT NULL,
            module VARCHAR(50) NOT NULL,
            user_ref VARCHAR(100) NOT NULL,
            user_name VARCHAR(255) DEFAULT NULL,
            user_email VARCHAR(255) DEFAULT NULL,
            user_group VARCHAR(100) DEFAULT NULL,
            event_type VARCHAR(10) NOT NULL,
            status VARCHAR(20) DEFAULT NULL,
            source VARCHAR(20) NOT NULL,
            admin_id INT DEFAULT NULL,
            event_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            meta TEXT DEFAULT NULL,
            INDEX idx_session_user (session_id, user_ref),
            INDEX idx_event_time (event_time),
            INDEX idx_module (module)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($GLOBALS['con'], $createSql);
    }

    $extraPermSql = "INSERT INTO `admin_permissions` (`permission_key`, `description`) VALUES 
        ('db_transfer', 'Transfer data between databases'),
        ('attendance_manage', 'Manage centralized attendance dashboard'),
        ('attendance_scan', 'Scan QR codes for attendance'),
        ('attendance_reports', 'View attendance reports')
        ON DUPLICATE KEY UPDATE description=VALUES(description)";
    mysqli_query($GLOBALS['con'], $extraPermSql);

    $syncSuperSql = "INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) 
        SELECT 1, id FROM `admin_permissions`";
    mysqli_query($GLOBALS['con'], $syncSuperSql);
}

function get_current_admin_id() {
    return isset($_SESSION['id']) ? $_SESSION['id'] : null;
}

function load_permissions($con) {
    if (!$con) return;

    $admin_id = get_current_admin_id();
    if (!$admin_id) return [];

    try {
        $stmt = mysqli_prepare($con, "SELECT role_id FROM admin WHERE id = ?");
        if (!$stmt) {
            $_SESSION['role_id'] = 0;
            $_SESSION['permissions'] = [];
            return [];
        }
        mysqli_stmt_execute_compat($stmt, 'i', [(int)$admin_id]);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;

        if (!$row || empty($row['role_id'])) {
            $_SESSION['role_id'] = 0;
            $_SESSION['permissions'] = [];
            return [];
        }

        $role_id = (int)$row['role_id'];
        $_SESSION['role_id'] = $role_id;

        $sql = "SELECT p.permission_key 
                FROM role_permissions rp 
                JOIN admin_permissions p ON rp.permission_id = p.id 
                WHERE rp.role_id = ?";

        $stmt = mysqli_prepare($con, $sql);
        if (!$stmt) {
            $_SESSION['permissions'] = [];
            return [];
        }
        mysqli_stmt_execute_compat($stmt, 'i', [$role_id]);
        $result = mysqli_stmt_get_result($stmt);

        $permissions = [];
        if ($result) {
            while ($p = mysqli_fetch_assoc($result)) {
                $permissions[] = $p['permission_key'];
            }
        }

        $_SESSION['permissions'] = $permissions;
        return $permissions;
    } catch (Throwable $e) {
        $_SESSION['role_id'] = 0;
        $_SESSION['permissions'] = [];
        return [];
    }
}

function check_permission($permission_key) {
    global $con;
    
    if (!$con) return false;

    // Super admin bypass (optional, but safer to stick to explicit permissions usually)
    // Assuming Role ID 1 is Super Admin
    if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 1) {
        return true;
    }

    // Fallback: If username is 'admin', allow everything (Legacy support)
    if (isset($_SESSION['login']) && $_SESSION['login'] === 'admin') {
        return true;
    }

    if (!isset($_SESSION['permissions'])) {
        try {
            load_permissions($con);
        } catch (Throwable $e) {
            $_SESSION['permissions'] = [];
            $_SESSION['role_id'] = $_SESSION['role_id'] ?? 0;
            return false;
        }
    }
    
    return in_array($permission_key, $_SESSION['permissions'] ?? []);
}


function require_permission($permission_key) {
    if (!check_permission($permission_key)) {
        // Log unauthorized access attempt
        log_audit("unauthorized_access", "Attempted to access protected resource requiring: $permission_key");
        
        // Redirect or die
        header("Location: manage-users.php?error=access_denied");
        exit("Access Denied");
    }
}

function log_audit($action, $details = '') {
    global $con;
    if (!$con) return;

    $admin_id = get_current_admin_id();
    // Fix: Default to 0 if not logged in to avoid DB errors
    if (empty($admin_id)) $admin_id = 0;

    $ip = $_SERVER['REMOTE_ADDR'];

    try {
        $stmt = mysqli_prepare($con, "INSERT INTO admin_audit_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_execute_compat($stmt, 'isss', [(int)$admin_id, (string)$action, (string)$details, (string)$ip]);
        }
    } catch (Throwable $e) {
        return;
    }
}
?>
