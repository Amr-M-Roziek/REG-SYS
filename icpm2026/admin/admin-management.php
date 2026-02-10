<?php
session_start();
include 'dbconnection.php';
require_once 'includes/auth_helper.php';
$currentPage = 'admin-management';

require_permission('admin_manage');

// Helper to get counts
function get_count($con, $table) {
    $res = mysqli_query($con, "SELECT COUNT(*) as c FROM $table");
    $row = mysqli_fetch_assoc($res);
    return $row['c'];
}

$total_admins = get_count($con, 'admin');
$total_roles = get_count($con, 'admin_roles');
$total_logs = get_count($con, 'admin_audit_logs');

// Fetch Admins
$admins = mysqli_query($con, "SELECT a.*, r.role_name FROM admin a LEFT JOIN admin_roles r ON a.role_id = r.id ORDER BY a.created_at DESC");

// Fetch Roles
$roles = mysqli_query($con, "SELECT * FROM admin_roles");

// Fetch Audit Logs (Limit 50)
$logs = mysqli_query($con, "SELECT l.*, a.username FROM admin_audit_logs l LEFT JOIN admin a ON l.admin_id = a.id ORDER BY l.created_at DESC LIMIT 50");

// Fetch Attendance Managers
$attendance_role_ids = [];
$perm_res = mysqli_query($con, "SELECT role_id FROM role_permissions rp JOIN admin_permissions p ON rp.permission_id = p.id WHERE p.permission_key = 'attendance_manage'");
while($r = mysqli_fetch_assoc($perm_res)) {
    $attendance_role_ids[] = $r['role_id'];
}
$att_mgr_count = 0;
if(!empty($attendance_role_ids)) {
    $ids = implode(',', $attendance_role_ids);
    $att_res = mysqli_query($con, "SELECT COUNT(*) as c FROM admin WHERE role_id IN ($ids)");
    $att_row = mysqli_fetch_assoc($att_res);
    $att_mgr_count = $att_row['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprehensive Admin Management</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
    <style>
        .nav-tabs { margin-bottom: 20px; border-bottom: 1px solid #ddd; }
        .nav-tabs > li > a { color: #666; background-color: #f9f9f9; margin-right: 5px; border: 1px solid #ddd; border-bottom-color: transparent; }
        .nav-tabs > li.active > a, .nav-tabs > li.active > a:hover, .nav-tabs > li.active > a:focus { color: #333; background-color: #fff; border: 1px solid #ddd; border-bottom-color: transparent; cursor: default; }
        .tab-pane { padding: 15px; background: #fff; border: 1px solid #ddd; border-top: none; }
        .stat-panel { background: #f2f2f2; padding: 15px; border-radius: 4px; text-align: center; margin-bottom: 20px; }
        .stat-number { font-size: 24px; font-weight: bold; color: #4ecdc4; }
        .stat-label { color: #797979; }
    </style>
</head>
<body>
<section id="container">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <section id="main-content">
        <section class="wrapper">
            <h3><i class="fa fa-cogs"></i> Admin System Management</h3>
            
            <div class="row mt">
                <div class="col-md-3">
                    <div class="stat-panel">
                        <div class="stat-number"><?php echo $total_admins; ?></div>
                        <div class="stat-label">Total Administrators</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-panel">
                        <div class="stat-number"><?php echo $total_roles; ?></div>
                        <div class="stat-label">Defined Roles</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-panel">
                        <div class="stat-number"><?php echo $att_mgr_count; ?></div>
                        <div class="stat-label">Attendance Managers</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-panel">
                        <div class="stat-number"><?php echo $total_logs; ?></div>
                        <div class="stat-label">Audit Log Entries</div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div class="content-panel" style="padding: 20px;">
                        <ul class="nav nav-tabs" id="adminTabs">
                            <li class="active"><a href="#users" data-toggle="tab"><i class="fa fa-users"></i> User Management</a></li>
                            <li><a href="#roles" data-toggle="tab"><i class="fa fa-lock"></i> Roles & RBAC</a></li>
                            <li><a href="#audit" data-toggle="tab"><i class="fa fa-list-alt"></i> Audit Logs</a></li>
                        </ul>

                        <div class="tab-content">
                            <!-- User Management Tab -->
                            <div class="tab-pane active" id="users">
                                <div class="row mb" style="margin-bottom: 15px;">
                                    <div class="col-md-6">
                                        <h4>System Users (Admins)</h4>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="add-admin.php" class="btn btn-success"><i class="fa fa-plus"></i> Add New Admin</a>
                                    </div>
                                </div>
                                <table class="table table-striped table-advance table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = mysqli_fetch_assoc($admins)): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email'] ?? ''); ?></td>
                                            <td>
                                                <?php if($row['role_name']): ?>
                                                    <span class="label label-info"><?php echo htmlspecialchars($row['role_name']); ?></span>
                                                    <?php if(in_array($row['role_id'], $attendance_role_ids)): ?>
                                                        <span class="label label-success" title="Has Attendance Access"><i class="fa fa-check"></i> Att.</span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="label label-warning">No Role</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if(isset($row['is_active']) && $row['is_active']): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="edit-admin.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i> Edit</a>
                                                <?php if($row['id'] != $_SESSION['id']): ?>
                                                <a href="delete-admin.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-xs" onclick="return confirm('Delete this admin?');"><i class="fa fa-trash-o"></i></a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Roles Tab -->
                            <div class="tab-pane" id="roles">
                                <div class="row mb" style="margin-bottom: 15px;">
                                    <div class="col-md-6">
                                        <h4>Role-Based Access Control</h4>
                                    </div>
                                    <div class="col-md-6 text-right">
                                        <a href="add-role.php" class="btn btn-success"><i class="fa fa-plus"></i> Create New Role</a>
                                    </div>
                                </div>
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>Role Name</th>
                                            <th>Description</th>
                                            <th>Key Permissions</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = mysqli_fetch_assoc($roles)): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['role_name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                                            <td>
                                                <?php
                                                    $rid = $row['id'];
                                                    $p_query = mysqli_query($con, "SELECT p.permission_key FROM role_permissions rp JOIN admin_permissions p ON rp.permission_id = p.id WHERE rp.role_id = $rid");
                                                    $perms = [];
                                                    while($p = mysqli_fetch_assoc($p_query)) $perms[] = $p['permission_key'];
                                                    
                                                    // Summarize
                                                    if(in_array('admin_manage', $perms)) echo '<span class="label label-danger">Full Admin</span> ';
                                                    if(in_array('user_view', $perms)) echo '<span class="label label-info">View Users</span> ';
                                                    if(in_array('user_edit', $perms)) echo '<span class="label label-primary">Edit Users</span> ';
                                                    if(in_array('attendance_manage', $perms)) echo '<span class="label label-success">Attendance</span> ';
                                                ?>
                                            </td>
                                            <td>
                                                <a href="edit-role.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-xs"><i class="fa fa-pencil"></i> Configure</a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                                <div class="alert alert-info mt">
                                    <strong>Note:</strong> 
                                    <ul>
                                        <li><strong>View:</strong> "Support Admin" role typically has read-only access (`user_view`).</li>
                                        <li><strong>Edit:</strong> "Content Admin" role has modification rights (`user_edit`).</li>
                                        <li><strong>Admin:</strong> "Super Admin" has full system access (`admin_manage`).</li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Audit Logs Tab -->
                            <div class="tab-pane" id="audit">
                                <h4>Recent System Activities</h4>
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Admin</th>
                                            <th>Action</th>
                                            <th>Details</th>
                                            <th>IP</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = mysqli_fetch_assoc($logs)): ?>
                                        <tr>
                                            <td><?php echo $row['created_at']; ?></td>
                                            <td><?php echo htmlspecialchars($row['username'] ?? 'System/Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($row['action']); ?></td>
                                            <td><?php echo htmlspecialchars($row['details']); ?></td>
                                            <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                                <div class="text-center">
                                    <a href="audit-logs.php" class="btn btn-default">View Full Logs</a>
                                </div>
                            </div>
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
