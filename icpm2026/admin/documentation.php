<?php
session_start();
include'dbconnection.php';
require_once 'includes/auth_helper.php';
$currentPage = 'documentation';

// Accessible by all admins
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Documentation</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
    <style>
        .doc-section { margin-bottom: 30px; }
        .doc-section h4 { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 20px; color: #4ecdc4; }
        .doc-section p { line-height: 1.6; }
        .doc-section ul { list-style-type: disc; padding-left: 20px; }
        .doc-section li { margin-bottom: 10px; }
    </style>
</head>
<body>
<section id="container">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <section id="main-content">
        <section class="wrapper">
            <h3><i class="fa fa-book"></i> Admin Management Documentation</h3>
            <div class="row mt">
                <div class="col-md-12">
                    <div class="content-panel" style="padding: 20px;">
                        
                        <div class="doc-section">
                            <h4>Overview</h4>
                            <p>This system allows you to manage administrators, roles, and permissions effectively. It is designed to be secure, scalable, and easy to use.</p>
                            <p>Text on light backgrounds in the main admin theme uses a dark gray color (#333333) instead of light gray, to provide sufficient contrast against white panels and meet WCAG AA readability standards.</p>
                        </div>

                        <div class="doc-section">
                            <h4>Managing Administrators</h4>
                            <ul>
                                <li><strong>Adding an Admin:</strong> Go to <em>Admin Management > Administrators</em> and click "Add Admin". Enter the username, password, email, and assign a role.</li>
                                <li><strong>Editing an Admin:</strong> Click the pencil icon next to an admin to update their email, role, or active status. You can also reset their password here.</li>
                                <li><strong>Deleting an Admin:</strong> Click the trash icon to remove an admin. <em>Note: You cannot delete your own account.</em></li>
                            </ul>
                        </div>

                        <div class="doc-section">
                            <h4>Roles & Permissions (RBAC)</h4>
                            <p>The system uses Role-Based Access Control (RBAC). Permissions are assigned to Roles, and Roles are assigned to Admins.</p>
                            <ul>
                                <li><strong>Super Admin:</strong> Has full access to all features.</li>
                                <li><strong>Content Admin:</strong> Can manage users and content, but cannot manage other admins.</li>
                                <li><strong>Support Admin:</strong> Read-only access to users.</li>
                                <li><strong>Creating Custom Roles:</strong> Go to <em>Admin Management > Roles & Permissions</em> and click "Add Role". Select the specific permissions you want to grant.</li>
                                <li><strong>Security:</strong> Modifying roles or permissions requires you to <strong>re-enter your password</strong> to confirm the action.</li>
                                <li><strong>Review Cycle:</strong> The system will remind you to review permissions every 90 days to ensure security compliance.</li>
                            </ul>
                        </div>

                        <div class="doc-section">
                            <h4>Security Best Practices</h4>
                            <ul>
                                <li><strong>Least Privilege:</strong> Always assign the most restrictive role necessary for an admin to do their job.</li>
                                <li><strong>Privilege Escalation Protection:</strong> Non-Super Admins cannot create, edit, or delete Super Admin accounts.</li>
                                <li><strong>Regular Audits:</strong> Check the <em>Audit Logs</em> regularly to monitor admin activity and detect any suspicious actions.</li>
                                <li><strong>Active Status:</strong> Instead of deleting an admin immediately, you can uncheck "Account is Active" to disable their access temporarily.</li>
                                <li><strong>Password Management:</strong> Encourage admins to use strong, unique passwords and change them periodically.</li>
                            </ul>
                        </div>

                        <div class="doc-section">
                            <h4>Attendance Integration</h4>
                            <p>The system supports centralized attendance tracking across multiple databases (Main, Participant, Poster, Workshop).</p>
                            <ul>
                                <li><strong>Attendance Managers:</strong> Admins with the <code>attendance_manage</code> permission can view and manage attendance records.</li>
                                <li><strong>Access Badge:</strong> In the <em>User Management</em> tab, admins with attendance access are marked with a green "Att." badge.</li>
                                <li><strong>QR Scanning:</strong> The attendance dashboard supports scanning QR codes from any module. The system automatically routes the request to the correct database based on the source prefix (e.g., <code>participant:123</code>).</li>
                            </ul>
                        </div>

                        <div class="doc-section">
                            <h4>API Specification</h4>
                            <p>The system uses <code>ajax_handler.php</code> for asynchronous operations. All responses are in JSON format.</p>
                            
                            <h5>Authentication</h5>
                            <p>All endpoints require a valid Admin Session. Some critical actions (like bulk delete) require re-authentication via password and CSRF tokens.</p>

                            <h5>Endpoints</h5>
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Action</th>
                                        <th>Method</th>
                                        <th>Parameters</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>get_user_data</code></td>
                                        <td>POST</td>
                                        <td><code>uid</code> (int)</td>
                                        <td>Fetches basic user details (fname, lname, email).</td>
                                    </tr>
                                    <tr>
                                        <td><code>delete_users</code></td>
                                        <td>POST</td>
                                        <td><code>ids[]</code> (array), <code>password</code> (string), <code>csrf_token</code> (string)</td>
                                        <td>Bulk deletes users after verifying admin password.</td>
                                    </tr>
                                    <tr>
                                        <td><code>save_template</code></td>
                                        <td>POST</td>
                                        <td><code>name</code> (string), <code>data</code> (html)</td>
                                        <td>Saves a certificate email template.</td>
                                    </tr>
                                    <tr>
                                        <td><code>send_certificate</code></td>
                                        <td>POST</td>
                                        <td><code>uid</code> (int), <code>pdf_data</code> (base64)</td>
                                        <td>Sends a generated PDF certificate to the user via email.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="doc-section">
                            <h4>Troubleshooting</h4>
                            <ul>
                                <li><strong>Permission Denied:</strong> If an admin cannot access a page, check their assigned role and ensure the role has the required permission.</li>
                                <li><strong>Login Issues:</strong> Verify the account is marked as "Active" in the edit admin screen.</li>
                                <li><strong>Database Errors:</strong> Ensure the `setup_rbac.sql` script has been run to update the database schema.</li>
                            </ul>
                        </div>

                    </div>
                </div>
            </div>
        </section>
    </section>
</section>
<script src="assets/js/jquery.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script class="include" type="text/javascript" src="assets/js/jquery.dcjqaccordion.2.7.js"></script>
<script src="assets/js/jquery.scrollTo.min.js"></script>
<script src="assets/js/common-scripts.js"></script>
</body>
</html>
