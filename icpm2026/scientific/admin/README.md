# ICPM 2026 — Scientific Admin System

Table of Contents
- Overview
- System Requirements
- Dependencies
- Installation
- Setup
- Basic Usage
- Contribution Guidelines
- License

## Overview
The ICPM 2026 Scientific Admin System is a PHP/MySQL web application for managing scientific participants, abstracts, certificates, and admin operations. It provides role-based access, data management, bulk uploads, certificate editing, and reporting within an admin dashboard.

Key modules:
- Dashboard and navigation: [index.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/index.php)
- Users management: [manage-users.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/manage-users.php)
- Roles/RBAC: [manage-roles.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/manage-roles.php), [setup_rbac.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/setup_rbac.php), [permission_helper.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/permission_helper.php)
- Bulk import: [bulk-upload.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/bulk-upload.php)
- Certificates: [certificate-editor.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/certificate-editor.php), [setup_certificates.sql](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/setup_certificates.sql)
- Admin actions API: [admin_action.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/admin_action.php)
- Auth/session: [session_setup.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/session_setup.php), [dbconnection.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/dbconnection.php)

## System Requirements
- PHP 7.4+ (recommended 8.1+)
- MySQL 5.7+ or MariaDB 10.4+
- Web server (Apache via XAMPP)
- Enabled PHP extensions: mysqli, mbstring, openssl, json
- Outbound email configured on server (for notifications)

## Dependencies
Front-end libraries bundled in [assets/js](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/assets/js) and [assets/css](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/assets/css):
- Bootstrap, jQuery
- Chart.js, FullCalendar
- Gritter (notifications)
- Easy Pie Chart

## Installation
1. Copy the project into:
   - `c:\xampp\htdocs\reg-sys.com\icpm2026\scientific\admin`
2. Create a database and update credentials in:
   - [dbconnection.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/dbconnection.php)
3. Ensure Apache and MySQL are running in XAMPP.

## Setup
Run initial setup scripts:
- RBAC and roles:
  - Command:
    ```
    c:\xampp\php\php.exe c:\xampp\htdocs\reg-sys.com\icpm2026\scientific\admin\setup_rbac.php
    ```
- Certificates schema:
  - Import SQL via MySQL client:
    ```
    mysql -u <user> -p <database> < c:\xampp\htdocs\reg-sys.com\icpm2026\scientific\admin\setup_certificates.sql
    ```
- Session configuration (if required by your environment):
  - [session_setup.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/session_setup.php)

## Basic Usage
Access the admin panel:
- URL: `http://localhost/reg-sys.com/icpm2026/scientific/admin/index.php`

Typical workflows:
- Manage users: add, edit, delete in [manage-users.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/manage-users.php)
- Bulk upload participants: [bulk-upload.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/bulk-upload.php)
- Edit and print certificates: [certificate-editor.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/certificate-editor.php)
- Configure roles and permissions: [manage-roles.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/manage-roles.php)

Example: fetching users with mysqli prepared statements
```php
require_once 'dbconnection.php';
$stmt = mysqli_prepare($con, "SELECT id, fname, lname, email FROM users WHERE email LIKE ?");
$like = '%@example.com';
mysqli_stmt_bind_param($stmt, 's', $like);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
    // process $row
}
```

## Contribution Guidelines
- Use prepared statements for all DB operations
- Keep credentials and secrets out of versioned files
- Follow existing coding patterns and file structures
- Write clear commit messages and include rationale
- Test features on Chrome, Firefox, Edge before delivery

## License
Proprietary — ICPM internal use only. Redistribution or external publication requires prior written consent.

