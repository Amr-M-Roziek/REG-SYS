<?php
session_start();
include 'dbconnection.php';
require_once 'includes/auth_helper.php';
$currentPage = 'manage-admins';

require_permission('admin_manage');

// Fetch admins
$query = "SELECT a.*, r.role_name 
          FROM admin a 
          LEFT JOIN admin_roles r ON a.role_id = r.id 
          ORDER BY a.created_at DESC";

$result = null;
try {
    $result = mysqli_query($con, $query);
} catch (Throwable $e) {
    // If table doesn't exist, it might be due to race condition or failure in auth_helper
    // Just suppress and result will be null
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Administrators</title>
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
            <h3><i class="fa fa-angle-right"></i> Manage Administrators</h3>
            <div class="row mt">
                <div class="col-md-12">
                    <div class="content-panel">
                        <div class="row" style="margin-bottom: 10px; padding: 0 15px;">
                            <div class="col-md-6">
                                <input type="text" id="adminSearch" class="form-control" placeholder="Search by username, email, or role...">
                            </div>
                            <div class="col-md-6 text-right">
                                <a href="add-admin.php" class="btn btn-primary"><i class="fa fa-plus"></i> Add Admin</a>
                            </div>
                        </div>
                        <table class="table table-striped table-advance table-hover" id="adminTable">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Created At</th>
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
                                <td><?php echo htmlspecialchars($row['username']);?></td>
                                <td><?php echo htmlspecialchars($row['email'] ?? '');?></td>
                                <td>
                                    <?php if(isset($row['role_name']) && $row['role_name']): ?>
                                        <span class="label label-info"><?php echo htmlspecialchars($row['role_name']);?></span>
                                    <?php else: ?>
                                        <span class="label label-warning">No Role</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo isset($row['created_at']) ? $row['created_at'] : 'N/A';?></td>
                                <td>
                                    <a href="edit-admin.php?id=<?php echo $row['id'];?>" class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i></a>
                                    <?php if($row['id'] != $_SESSION['id']): // Prevent self-delete ?>
                                    <a href="delete-admin.php?id=<?php echo $row['id'];?>" class="btn btn-danger btn-xs" onclick="return confirm('Are you sure you want to delete this admin?');"><i class="fa fa-trash-o"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php 
                                } 
                            } else {
                                echo "<tr><td colspan='6' class='text-center text-danger'>Error fetching data. Tables might be initializing. Please refresh.</td></tr>";
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
<script>
$(document).ready(function(){
  $("#adminSearch").on("keyup", function() {
    var value = $(this).val().toLowerCase();
    $("#adminTable tbody tr").filter(function() {
      $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
    });
  });
});
</script>
</body>
</html>
