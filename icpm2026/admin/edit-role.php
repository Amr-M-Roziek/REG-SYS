<?php
session_start();
include'dbconnection.php';
require_once 'includes/auth_helper.php';
$currentPage = 'manage-roles';

require_permission('admin_manage');

$id = intval($_GET['id']);
$msg = "";

if(isset($_POST['submit'])) {
    $role_name = $_POST['role_name'];
    $description = $_POST['description'];
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    
    // Update role
    $query = "UPDATE admin_roles SET role_name=?, description=? WHERE id=?";
    $stmt = mysqli_prepare($con, $query);
    mysqli_stmt_bind_param($stmt, 'ssi', $role_name, $description, $id);
    
    if(mysqli_stmt_execute($stmt)) {
        // Update permissions
        mysqli_query($con, "DELETE FROM role_permissions WHERE role_id=$id");
        
        if(!empty($permissions)) {
            $p_stmt = mysqli_prepare($con, "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach($permissions as $p_id) {
                mysqli_stmt_bind_param($p_stmt, 'ii', $id, $p_id);
                mysqli_stmt_execute($p_stmt);
            }
        }
        
        log_audit("update_role", "Updated role ID: $id");
        $msg = "Role updated successfully";
    } else {
        $msg = "Error updating role: " . mysqli_error($con);
    }
}

// Fetch Role
$stmt = mysqli_prepare($con, "SELECT * FROM admin_roles WHERE id=?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$role = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if(!$role) {
    header("Location: manage-roles.php");
    exit();
}

// Fetch Assigned Permissions
$assigned_perms = [];
$ap_query = mysqli_query($con, "SELECT permission_id FROM role_permissions WHERE role_id=$id");
while($ap = mysqli_fetch_assoc($ap_query)) {
    $assigned_perms[] = $ap['permission_id'];
}

// Fetch All Permissions
$perms_result = mysqli_query($con, "SELECT * FROM admin_permissions ORDER BY permission_key");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Role</title>
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
            <h3><i class="fa fa-angle-right"></i> Edit Role: <?php echo htmlspecialchars($role['role_name']); ?></h3>
            <div class="row mt">
                <div class="col-lg-12">
                    <div class="form-panel">
                        <?php if($msg) { echo "<div class='alert alert-info'>$msg</div>"; } ?>
                        <form class="form-horizontal style-form" method="post">
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label">Role Name</label>
                                <div class="col-sm-10">
                                    <input type="text" name="role_name" class="form-control" value="<?php echo htmlspecialchars($role['role_name']); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label">Description</label>
                                <div class="col-sm-10">
                                    <input type="text" name="description" class="form-control" value="<?php echo htmlspecialchars($role['description']); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label">Permissions</label>
                                <div class="col-sm-10">
                                    <?php while($p = mysqli_fetch_assoc($perms_result)) { 
                                        $checked = in_array($p['id'], $assigned_perms) ? 'checked' : '';
                                    ?>
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="permissions[]" value="<?php echo $p['id']; ?>" <?php echo $checked; ?>>
                                                <strong><?php echo htmlspecialchars($p['permission_key']); ?></strong> - <?php echo htmlspecialchars($p['description']); ?>
                                            </label>
                                        </div>
                                    <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label" style="color: #d9534f;">Confirm Password</label>
                                <div class="col-sm-10">
                                    <input type="password" name="confirm_password" class="form-control" placeholder="Enter your password to confirm changes" required>
                                    <span class="help-block">Security verification required for permission changes.</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-12" style="text-align: center;">
                                    <button type="submit" name="submit" class="btn btn-primary">Update Role</button>
                                    <a href="manage-roles.php" class="btn btn-default">Cancel</a>
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
