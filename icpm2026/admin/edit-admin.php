<?php
session_start();
include'dbconnection.php';
require_once 'includes/auth_helper.php';
$currentPage = 'manage-admins';

require_permission('admin_manage');

$id = intval($_GET['id']);
$msg = "";

if(isset($_POST['submit'])) {
    $email = $_POST['email'];
    $role_id = $_POST['role_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Security: Prevent non-Super Admin from assigning Super Admin role
    if ($role_id == 1 && $_SESSION['role_id'] != 1) {
        die("Security Alert: You are not authorized to assign the Super Admin role.");
    }
    
    if(!empty($_POST['password'])) {
        $password = md5($_POST['password']);
        $query = "UPDATE admin SET email=?, role_id=?, is_active=?, password=? WHERE id=?";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, 'siisi', $email, $role_id, $is_active, $password, $id);
    } else {
        $query = "UPDATE admin SET email=?, role_id=?, is_active=? WHERE id=?";
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, 'siii', $email, $role_id, $is_active, $id);
    }
    
    if(mysqli_stmt_execute($stmt)) {
        log_audit("update_admin", "Updated admin ID: $id");
        $msg = "Admin updated successfully";
        // Refresh data
        $stmt = mysqli_prepare($con, "SELECT * FROM admin WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    } else {
        $msg = "Error updating admin: " . mysqli_error($con);
    }
} else {
    $stmt = mysqli_prepare($con, "SELECT * FROM admin WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if(!$row) {
        header("Location: manage-admins.php");
        exit();
    }
}

$roles = mysqli_query($con, "SELECT * FROM admin_roles");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Admin</title>
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
            <h3><i class="fa fa-angle-right"></i> Edit Administrator: <?php echo htmlspecialchars($row['username']); ?></h3>
            <div class="row mt">
                <div class="col-lg-12">
                    <div class="form-panel">
                        <?php if($msg) { echo "<div class='alert alert-info'>$msg</div>"; } ?>
                        <form class="form-horizontal style-form" method="post">
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label">Username</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($row['username']); ?>" disabled>
                                    <span class="help-block">Username cannot be changed</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label">New Password</label>
                                <div class="col-sm-10">
                                    <input type="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label">Email</label>
                                <div class="col-sm-10">
                                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($row['email'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label">Role</label>
                                <div class="col-sm-10">
                                    <select name="role_id" class="form-control" required>
                                        <option value="">Select Role</option>
                                        <?php 
                                        mysqli_data_seek($roles, 0);
                                        while($r = mysqli_fetch_assoc($roles)) { 
                                            $selected = ($r['id'] == $row['role_id']) ? 'selected' : '';
                                        ?>
                                            <option value="<?php echo $r['id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($r['role_name']); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label">Active Status</label>
                                <div class="col-sm-10">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="is_active" value="1" <?php echo (isset($row['is_active']) && $row['is_active']) ? 'checked' : ''; ?>> Account is Active
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-12" style="text-align: center;">
                                    <button type="submit" name="submit" class="btn btn-primary">Update Admin</button>
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
