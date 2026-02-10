<?php
require_once 'session_setup.php';
include 'dbconnection.php';
require_once 'permission_helper.php';

// Auth Check
if (empty($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Permission Check
if (!has_permission($con, 'manage_admins')) {
    die("You do not have permission to access this page.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Dashboard">
    <meta name="keyword" content="Dashboard, Bootstrap, Admin, Template, Theme, Responsive, Fluid, Retina">

    <title>Manage Admins | Admin</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
    
    <style>
        .role-item { margin-bottom: 5px; }
    </style>
</head>

<body>

<section id="container" >
    <header class="header black-bg">
        <div class="sidebar-toggle-box">
            <div class="fa fa-bars tooltips" data-placement="right" data-original-title="Toggle Navigation"></div>
        </div>
        <a href="index.php" class="logo"><b>Admin Dashboard</b></a>
        <div class="top-menu">
            <ul class="nav pull-right top-menu">
                <li><a class="logout" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </header>
    
    <aside>
        <div id="sidebar"  class="nav-collapse ">
            <ul class="sidebar-menu" id="nav-accordion">
                <p class="centered"><a href="#"><img src="assets/img/ui-sam.jpg" class="img-circle" width="60"></a></p>
                <h5 class="centered"><?php echo htmlspecialchars($_SESSION['login'] ?? 'Admin'); ?></h5>
                
                <li class="mt">
                    <a href="manage-users.php">
                        <i class="fa fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                <li class="sub-menu">
                    <a href="manage-attendance.php">
                        <i class="fa fa-clock-o"></i>
                        <span>Attendance</span>
                    </a>
                </li>
                <?php if(has_permission($con, 'manage_admins')): ?>
                <li class="sub-menu">
                    <a class="active" href="manage-admins.php">
                        <i class="fa fa-user-md"></i>
                        <span>Manage Admins</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if(has_permission($con, 'manage_roles')): ?>
                <li class="sub-menu">
                    <a href="manage-roles.php">
                        <i class="fa fa-lock"></i>
                        <span>Manage Roles</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </aside>

    <section id="main-content">
        <section class="wrapper">
            <h3><i class="fa fa-angle-right"></i> Manage Admins</h3>
            
            <div class="row mt">
                <div class="col-md-12">
                    <div class="content-panel">
                        <div class="pull-right" style="padding-right: 15px; padding-bottom: 10px;">
                            <button class="btn btn-primary" onclick="openAdminModal()">Add New Admin</button>
                        </div>
                        <table class="table table-striped table-advance table-hover">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Scope</th>
                                <th>Roles</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody id="adminsTableBody">
                                <tr><td colspan="6" class="text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </section>

    <!-- Admin Modal -->
    <div class="modal fade" id="adminModal" tabindex="-1" role="dialog" aria-labelledby="adminModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="adminForm" onsubmit="saveAdmin(event)">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title" id="adminModalLabel">Manage Admin</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="adminId" name="id" value="0">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control" id="adminUsername" name="username" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" id="adminEmail" name="email">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" id="adminPassword" name="password" placeholder="Leave blank to keep current">
                            <p class="help-block" id="passwordHelp">Required for new admins.</p>
                        </div>
                        <div class="form-group">
                            <label>Access Scope</label>
                            <select class="form-control" id="adminScope" name="access_scope" required>
                                <option value="both">Both Systems</option>
                                <option value="poster">Poster Only</option>
                                <option value="scientific">Scientific Only</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Roles</label>
                            <div id="rolesContainer">
                                <!-- Roles loaded via JS -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- common script for all pages-->
    <script src="assets/js/jquery.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/jquery-ui-1.9.2.custom.min.js"></script>
    <script src="assets/js/jquery.ui.touch-punch.min.js"></script>
    <script class="include" type="text/javascript" src="assets/js/jquery.dcjqaccordion.2.7.js"></script>
    <script src="assets/js/jquery.scrollTo.min.js"></script>
    <script src="assets/js/jquery.nicescroll.js" type="text/javascript"></script>
    <script src="assets/js/common-scripts.js"></script>

    <script>
        let allRoles = [];

        $(document).ready(function() {
            loadRolesList();
            loadAdmins();
        });

        function loadRolesList() {
            $.getJSON('admin_action.php?action=get_roles', function(res) {
                if (res.status === 'ok') {
                    allRoles = res.roles;
                    renderRoles();
                }
            });
        }

        function renderRoles() {
            let html = '';
            allRoles.forEach(role => {
                html += `<div class="role-item">
                            <label>
                                <input type="checkbox" name="roles[]" value="${role.id}"> ${role.name}
                            </label>
                         </div>`;
            });
            $('#rolesContainer').html(html);
        }

        function loadAdmins() {
            $.getJSON('admin_action.php?action=get_admins', function(res) {
                if (res.status === 'ok') {
                    let html = '';
                    res.admins.forEach(admin => {
                        let roles = admin.role_names.join(', ');
                        
                        html += `<tr>
                                    <td>${admin.id}</td>
                                    <td>${admin.username}</td>
                                    <td>${admin.email}</td>
                                    <td>${admin.access_scope || 'both'}</td>
                                    <td>${roles}</td>
                                    <td>${admin.created_at}</td>
                                    <td>
                                        <button class="btn btn-primary btn-xs" onclick='editAdmin(${JSON.stringify(admin)})'><i class="fa fa-pencil"></i></button>
                                        <button class="btn btn-danger btn-xs" onclick="deleteAdmin(${admin.id})"><i class="fa fa-trash-o"></i></button>
                                    </td>
                                 </tr>`;
                    });
                    $('#adminsTableBody').html(html);
                } else {
                    alert(res.error);
                }
            });
        }

        function openAdminModal() {
            $('#adminId').val(0);
            $('#adminUsername').val('');
            $('#adminEmail').val('');
            $('#adminScope').val('both');
            $('#adminPassword').val('');
            $('#passwordHelp').text('Required for new admins.');
            $('input[name="roles[]"]').prop('checked', false);
            $('#adminModalLabel').text('Add New Admin');
            $('#adminModal').modal('show');
        }

        function editAdmin(admin) {
            $('#adminId').val(admin.id);
            $('#adminUsername').val(admin.username);
            $('#adminEmail').val(admin.email);
            $('#adminPassword').val('');
            $('#passwordHelp').text('Leave blank to keep current password.');
            $('input[name="roles[]"]').prop('checked', false);
            admin.role_ids.forEach(rid => {
                $(`input[name="roles[]"][value="${rid}"]`).prop('checked', true);
            });
            $('#adminModalLabel').text('Edit Admin');
            $('#adminModal').modal('show');
        }

        function saveAdmin(e) {
            e.preventDefault();
            
            // Loading indicator
            const btn = $(e.target).find('button[type="submit"]');
            const originalText = btn.text();
            btn.prop('disabled', true).text('Saving...');

            const formData = $('#adminForm').serialize();
            
            $.ajax({
                url: 'admin_action.php?action=save_admin',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(data) {
                    if (data.status === 'ok') {
                        $('#adminModal').modal('hide');
                        loadAdmins();
                        // Optional: Show success message via alert or toast
                    } else {
                        alert('Error: ' + data.error);
                    }
                },
                error: function(xhr, status, error) {
                    let msg = 'System error occurred';
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        msg = xhr.responseJSON.error;
                    } else if (xhr.responseText) {
                        // Try parsing responseText if it wasn't automatically parsed
                        try {
                            const json = JSON.parse(xhr.responseText);
                            if (json.error) msg = json.error;
                        } catch(e) {
                             msg += ': ' + error;
                        }
                    }
                    alert(msg);
                },
                complete: function() {
                    btn.prop('disabled', false).text(originalText);
                }
            });
        }

        function deleteAdmin(id) {
            if (confirm('Are you sure you want to delete this admin?')) {
                const csrfToken = $('input[name="csrf_token"]').val();
                
                $.ajax({
                    url: 'admin_action.php?action=delete_admin',
                    type: 'POST',
                    data: {id: id, csrf_token: csrfToken},
                    dataType: 'json',
                    success: function(data) {
                        if (data.status === 'ok') {
                            loadAdmins();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    },
                    error: function(xhr) {
                        let msg = 'System error occurred';
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            msg = xhr.responseJSON.error;
                        }
                        alert(msg + ' (' + xhr.status + ')');
                    }
                });
            }
        }
    </script>

</body>
</html>