-- Admin Roles
CREATE TABLE IF NOT EXISTS `admin_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin Permissions
CREATE TABLE IF NOT EXISTS `admin_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permission_key` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Role Permissions Mapping
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`role_id`,`permission_id`),
  FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`permission_id`) REFERENCES `admin_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit Logs
CREATE TABLE IF NOT EXISTS `admin_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update Admin Table
-- Note: Run these manually if columns don't exist, or ignore errors if they do.
-- ALTER TABLE `admin` ADD COLUMN `email` varchar(255) DEFAULT NULL;
-- ALTER TABLE `admin` ADD COLUMN `role_id` int(11) DEFAULT NULL;
-- ALTER TABLE `admin` ADD COLUMN `is_active` tinyint(1) DEFAULT 1;
-- ALTER TABLE `admin` ADD COLUMN `last_login` timestamp NULL DEFAULT NULL;
-- ALTER TABLE `admin` ADD COLUMN `created_at` timestamp DEFAULT CURRENT_TIMESTAMP;

-- Seed Data: Roles
INSERT INTO `admin_roles` (`role_name`, `description`) VALUES 
('Super Admin', 'Full access to all features'),
('Content Admin', 'Can manage users and content'),
('Support Admin', 'Read-only access to users')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Seed Data: Permissions
INSERT INTO `admin_permissions` (`permission_key`, `description`) VALUES 
('admin_manage', 'Manage administrators and roles'),
('user_view', 'View users'),
('user_edit', 'Edit users'),
('user_delete', 'Delete users'),
('bulk_upload', 'Bulk upload users'),
('export_data', 'Export user data')
ON DUPLICATE KEY UPDATE description=VALUES(description);

-- Seed Data: Role Permissions
-- Super Admin (1) gets all
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) 
SELECT 1, id FROM `admin_permissions`;

-- Content Admin (2) gets user_view, user_edit, bulk_upload
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) 
SELECT 2, id FROM `admin_permissions` WHERE `permission_key` IN ('user_view', 'user_edit', 'bulk_upload', 'export_data');

-- Support Admin (3) gets user_view
INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) 
SELECT 3, id FROM `admin_permissions` WHERE `permission_key` IN ('user_view');
