<?php
session_start();
include 'dbconnection.php';
require_once 'includes/auth_helper.php';
$currentPage = 'manage-admins';

require_permission('admin_manage');

$msg = "";
if(isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = md5($_POST['password']); 
    
    $email = $_POST['email'];
    $role_id = $_POST['role_id'];
    
    // Security: Prevent non-Super Admin from assigning Super Admin role
    // Using loose comparison for session role_id as it might be string or int
    if ($role_id == 1 && (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1)) {
        // Fallback check for admin username if role not set
        if (!isset($_SESSION['login']) || $_SESSION['login'] !== 'admin') {
            die("Security Alert: You are not authorized to assign the Super Admin role.");
        }
    }

    // Check if username exists
    $check = mysqli_query($con, "SELECT id FROM admin WHERE username='$username'");
    if(mysqli_num_rows($check) > 0) {
        $msg = "Username already exists";
    } else {
        $query = mysqli_prepare($con, "INSERT INTO admin (username, password, email, role_id) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($query, 'sssi', $username, $password, $email, $role_id);
        
        if(mysqli_stmt_execute($query)) {
            log_audit("create_admin", "Created admin user: $username with role ID: $role_id");
            echo "<script>alert('Admin added successfully'); window.location='manage-admins.php';</script>";
        } else {
            $msg = "Error adding admin: " . mysqli_error($con);
        }
    }
}

// Fetch roles for dropdown
$roles_query = "SELECT * FROM admin_roles";
if (isset($_SESSION['role_id']) && $_SESSION['role_id'] != 1) {
    // If not super admin (and not 'admin' user), filter roles
    if (!isset($_SESSION['login']) || $_SESSION['login'] !== 'admin') {
        $roles_query .= " WHERE id != 1";
    }
}

$roles = null;
try {
    $roles = mysqli_query($con, $roles_query);
} catch (Throwable $e) {
    // Suppress error if table doesn't exist yet
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Admin</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
</head>
<body>
<section id="container">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <section id="main-content">
        <section class="wrapper">
            <h3><i class="fa fa-angle-right"></i> Add Administrator</h3>
            <div class="row mt">
                <div class="col-lg-12">
                    <div class="form-panel">
                        <?php if($msg) { echo "<div class='alert alert-danger'>$msg</div>"; } ?>
                        <form class="form-horizontal style-form" method="post">
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label">Username</label>
                                <div class="col-sm-10">
                                    <input type="text" name="username" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label">Password</label>
                                <div class="col-sm-10">
                                    <input type="password" name="password" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label">Email</label>
                                <div class="col-sm-10">
                                    <input type="email" name="email" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label">Role</label>
                                <div class="col-sm-10">
                                    <select name="role_id" class="form-control" required>
                                        <option value="">Select Role</option>
                                        <?php 
                                        if ($roles) {
                                            while($r = mysqli_fetch_assoc($roles)) { 
                                        ?>
                                            <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['role_name']); ?></option>
                                        <?php 
                                            }
                                        } 
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-12" style="text-align: center;">
                                    <button type="submit" name="submit" class="btn btn-primary">Create Admin</button>
                                    <a href="manage-admins.php" class="btn btn-default">Cancel</a>
                                </div>
                            </div>
                        </form>
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
