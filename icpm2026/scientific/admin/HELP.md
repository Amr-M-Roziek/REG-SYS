# Scientific Admin — Help & FAQ

Table of Contents
- FAQs
- Quick Reference
- Support
- Known Issues
- Resources

## FAQs
Q: I cannot log in. What should I check?
- Verify email and password spelling.
- Ensure MySQL is running and [dbconnection.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/dbconnection.php) is configured.
- Check `php-error.log` for errors.

Q: How do I reset a user’s password?
- Use the system’s "Forgot Password" flow or manually update in the database via a prepared statement.

Q: How can I add many users at once?
- Use [bulk-upload.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/bulk-upload.php) with a validated CSV (UTF-8).

Q: How do I adjust admin permissions?
- Manage roles in [manage-roles.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/manage-roles.php). Initialize RBAC via [setup_rbac.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/setup_rbac.php).

## Quick Reference
- Open Admin: `http://localhost/reg-sys.com/icpm2026/scientific/admin/index.php`
- Manage Users: [manage-users.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/manage-users.php)
- Roles: [manage-roles.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/manage-roles.php)
- Certificates: [certificate-editor.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/certificate-editor.php)
- API actions: [admin_action.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/admin_action.php)

Snippet: reset password securely
```php
require_once 'dbconnection.php';
$newPassword = bin2hex(random_bytes(4));
$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = mysqli_prepare($con, "UPDATE users SET password=? WHERE email=?");
mysqli_stmt_bind_param($stmt, 'ss', $hash, $email);
mysqli_stmt_execute($stmt);
```

## Support
- Internal support: icpm-support@reg-sys.com
- Provide logs (`php-error.log`, `csrf_debug.log`) and steps to reproduce.

## Known Issues
- Email delivery depends on server configuration; verify SMTP or mail transport.
- Large CSV uploads may require increasing PHP max execution time and memory.
- Asset paths must remain relative to the admin directory to prevent 404s.

## Resources
- [README.md](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/README.md)
- [GUIDE.md](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/GUIDE.md)
- Role-based permissions: [permission_helper.php](file:///c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/permission_helper.php)

