<?php
session_start();
include'dbconnection.php';
require_once 'includes/auth_helper.php';
$currentPage = 'manage-roles';

require_permission('admin_manage');

$msg = "";
if(isset($_POST['submit'])) {
    $role_name = $_POST['role_name'];
    $description = $_POST['description'];
    $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
    
    // Check if role exists
    $check = mysqli_query($con, "SELECT id FROM admin_roles WHERE role_name='$role_name'");
    if(mysqli_num_rows($check) > 0) {
        $msg = "Role name already exists";
    } else {
        $query = mysqli_prepare($con, "INSERT INTO admin_roles (role_name, description) VALUES (?, ?)");
        mysqli_stmt_bind_param($query, 'ss', $role_name, $description);
        
        if(mysqli_stmt_execute($query)) {
            $role_id = mysqli_insert_id($con);
            
            // Add permissions
            if(!empty($permissions)) {
                $p_stmt = mysqli_prepare($con, "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
                foreach($permissions as $p_id) {
                    mysqli_stmt_bind_param($p_stmt, 'ii', $role_id, $p_id);
                    mysqli_stmt_execute($p_stmt);
                }
            }
            
            log_audit("create_role", "Created role: $role_name with ID: $role_id");
            echo "<script>alert('Role added successfully'); window.location='manage-roles.php';</script>";
        } else {
            $msg = "Error adding role: " . mysqli_error($con);
        }
    }
}

$perms_result = mysqli_query($con, "SELECT * FROM admin_permissions ORDER BY permission_key");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Role</title>
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
            <h3><i class="fa fa-angle-right"></i> Add Role</h3>
            <div class="row mt">
                <div class="col-lg-12">
                    <div class="form-panel">
                        <?php if($msg) { echo "<div class='alert alert-danger'>$msg</div>"; } ?>
                        <form class="form-horizontal style-form" method="post">
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label">Role Name</label>
                                <div class="col-sm-10">
                                    <input type="text" name="role_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label">Description</label>
                                <div class="col-sm-10">
                                    <input type="text" name="description" class="form-control">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label">Permissions</label>
                                <div class="col-sm-10">
                                    <?php while($p = mysqli_fetch_assoc($perms_result)) { ?>
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="permissions[]" value="<?php echo $p['id']; ?>">
                                                <strong><?php echo htmlspecialchars($p['permission_key']); ?></strong> - <?php echo htmlspecialchars($p['description']); ?>
                                            </label>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-sm-12" style="text-align: center;">
                                    <button type="submit" name="submit" class="btn btn-primary">Create Role</button>
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
