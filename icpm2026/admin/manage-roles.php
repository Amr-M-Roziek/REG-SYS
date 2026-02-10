<?php
session_start();
include'dbconnection.php';
require_once 'includes/auth_helper.php';
$currentPage = 'manage-roles';

require_permission('admin_manage');

// Check last review
$review_check = mysqli_query($con, "SELECT created_at FROM admin_audit_logs WHERE action='permissions_reviewed' ORDER BY created_at DESC LIMIT 1");
$last_review = mysqli_fetch_assoc($review_check);
$needs_review = false;
$msg_review = "";

if (!$last_review) {
    $needs_review = true;
    $msg_review = "Permissions have never been reviewed.";
} else {
    $days_since = floor((time() - strtotime($last_review['created_at'])) / (60 * 60 * 24));
    if ($days_since > 90) {
        $needs_review = true;
        $msg_review = "Permissions were last reviewed $days_since days ago.";
    }
}

if (isset($_POST['mark_reviewed'])) {
    log_audit("permissions_reviewed", "Admin marked permissions as reviewed");
    echo "<script>alert('Permissions marked as reviewed.'); window.location='manage-roles.php';</script>";
}

$query = "SELECT * FROM admin_roles";
$result = @mysqli_query($con, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Roles</title>
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
            <h3><i class="fa fa-angle-right"></i> Manage Roles & Permissions</h3>
            <div class="row mt">
                <div class="col-md-12">
                    <div class="content-panel">
                        <div class="pull-right" style="padding-right: 15px; padding-bottom: 10px;">
                            <a href="add-role.php" class="btn btn-primary"><i class="fa fa-plus"></i> Add Role</a>
                        </div>
                        <table class="table table-striped table-advance table-hover">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Role Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php 
                            if($result) {
                                while($row = mysqli_fetch_array($result)) {
                            ?>
                            <tr>
                                <td><?php echo $row['id'];?></td>
                                <td><?php echo htmlspecialchars($row['role_name']);?></td>
                                <td><?php echo htmlspecialchars($row['description']);?></td>
                                <td>
                                    <a href="edit-role.php?id=<?php echo $row['id'];?>" class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i> Edit Permissions</a>
                                    <?php if($row['id'] > 3): // Protect default roles ?>
                                    <a href="delete-role.php?id=<?php echo $row['id'];?>" class="btn btn-danger btn-xs" onclick="return confirm('Are you sure?');"><i class="fa fa-trash-o"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                } 
                            } else {
                                echo "<tr><td colspan='4' class='text-center text-danger'>Error fetching data. Please ensure the database schema has been updated.</td></tr>";
                            }
                            ?>
                            </tbody>
                        </table>
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
