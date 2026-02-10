<?php
session_start();
include'dbconnection.php';
require_once 'includes/auth_helper.php';
$currentPage = 'audit-logs';

require_permission('admin_manage');

$query = "SELECT l.*, a.username 
          FROM admin_audit_logs l 
          LEFT JOIN admin a ON l.admin_id = a.id 
          ORDER BY l.created_at DESC LIMIT 100";
$result = @mysqli_query($con, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs</title>
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
            <h3><i class="fa fa-angle-right"></i> Audit Logs (Last 100)</h3>
            <div class="row mt">
                <div class="col-md-12">
                    <div class="content-panel">
                        <table class="table table-striped table-advance table-hover">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>IP Address</th>
                                <th>Timestamp</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php 
                            if($result) {
                                while($row = mysqli_fetch_array($result)) {
                            ?>
                            <tr>
                                <td><?php echo $row['id'];?></td>
                                <td><?php echo htmlspecialchars($row['username'] ?? 'Unknown');?></td>
                                <td><?php echo htmlspecialchars($row['action']);?></td>
                                <td><?php echo htmlspecialchars($row['details']);?></td>
                                <td><?php echo htmlspecialchars($row['ip_address']);?></td>
                                <td><?php echo $row['created_at'];?></td>
                            </tr>
                            <?php 
                                } 
                            } else {
                                echo "<tr><td colspan='6' class='text-center'>No logs found or table not created.</td></tr>";
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
