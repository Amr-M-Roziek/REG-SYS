# Scientific Admin — User Guide

Table of Contents
- Getting Started
- Navigation Overview
- Features
- Configuration
- Best Practices
- Troubleshooting

## Getting Started
1. Start XAMPP (Apache and MySQL).
2. Open: `http://localhost/reg-sys.com/icpm2026/scientific/admin/index.php`
3. Log in with your admin credentials. Roles can be managed in [manage-roles.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/manage-roles.php).

## Navigation Overview
- Dashboard: [index.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/index.php)
- Users: [manage-users.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/manage-users.php)
- Roles & Permissions: [manage-roles.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/manage-roles.php)
- Bulk Upload: [bulk-upload.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/bulk-upload.php)
- Certificates: [certificate-editor.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/certificate-editor.php)
- Profile: [update-profile.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/update-profile.php)
- Logout: [logout.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/logout.php)

## Features
### Manage Users
- Create, edit, delete users.
- Search and filter by email, name, or category.
- Example: create a user safely
```php
require_once 'dbconnection.php';
$stmt = mysqli_prepare($con, "INSERT INTO users (fname, lname, email, category) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'ssss', $fname, $lname, $email, $category);
mysqli_stmt_execute($stmt);
```

### Bulk Upload
- Upload CSV of users via [bulk-upload.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/bulk-upload.php).
- Recommended columns: fname, lname, email, category, organization, contactno.
- Validate data prior to import to avoid duplicates.

### Certificates
- Design and print certificates in [certificate-editor.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/certificate-editor.php).
- Schema setup: [setup_certificates.sql](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/setup_certificates.sql)

### Roles & Permissions
- Initialize RBAC via:
```
c:\xampp\php\php.exe c:\xampp\htdocs\reg-sys.com\icpm2026\scientific\admin\setup_rbac.php
```
- Fine-tune access in [permission_helper.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/permission_helper.php).

## Configuration
- Database: [dbconnection.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/dbconnection.php)
- Sessions: [session_setup.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/session_setup.php)
- Logging: `php-error.log` and `csrf_debug.log` for diagnostics
- Front-end assets: [assets](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/assets)

Examples:
```php
// dbconnection.php snippet
$con = mysqli_connect("localhost", "db_user", "db_pass", "db_name");
mysqli_set_charset($con, 'utf8mb4');
```

## Best Practices
- Always use prepared statements.
- Sanitize and validate inputs (email, category).
- Avoid printing raw errors to users; log to files instead.
- Test changes on Chrome, Firefox, Edge.
- Keep CSV uploads small and validated to prevent timeouts.

## Troubleshooting
- Database connection fails:
  - Verify credentials in [dbconnection.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/dbconnection.php)
  - Check XAMPP MySQL service is running
- Permissions denied:
  - Confirm roles in [manage-roles.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/manage-roles.php)
  - Re-run [setup_rbac.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/setup_rbac.php) if needed
- Bulk upload errors:
  - Validate CSV format, ensure header names match expected fields
  - Check `php-error.log` for detailed messages
- Certificate not rendering:
  - Confirm schema via [setup_certificates.sql](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/setup_certificates.sql)
  - Verify fonts and asset paths

