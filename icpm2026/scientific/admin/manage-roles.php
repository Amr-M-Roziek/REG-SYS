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
if (!has_permission($con, 'manage_roles')) {
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

    <title>Manage Roles | Admin</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
    
    <style>
        .permission-group { margin-bottom: 15px; }
        .permission-item { margin-bottom: 5px; }
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
                <?php if(has_permission($con, 'manage_admins')): ?>
                <li class="sub-menu">
                    <a href="manage-admins.php">
                        <i class="fa fa-user-md"></i>
                        <span>Manage Admins</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if(has_permission($con, 'manage_roles')): ?>
                <li class="sub-menu">
                    <a class="active" href="manage-roles.php">
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
            <h3><i class="fa fa-angle-right"></i> Manage Roles</h3>
            
            <div class="row mt">
                <div class="col-md-12">
                    <div class="content-panel">
                        <div class="pull-right" style="padding-right: 15px; padding-bottom: 10px;">
                            <button class="btn btn-primary" onclick="openRoleModal()">Add New Role</button>
                        </div>
                        <table class="table table-striped table-advance table-hover">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Permissions</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody id="rolesTableBody">
                                <tr><td colspan="5" class="text-center">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </section>

    <!-- Role Modal -->
    <div class="modal fade" id="roleModal" tabindex="-1" role="dialog" aria-labelledby="roleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="roleForm" onsubmit="saveRole(event)">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title" id="roleModalLabel">Manage Role</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="roleId" name="id" value="0">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="form-group">
                            <label>Role Name</label>
                            <input type="text" class="form-control" id="roleName" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" id="roleDesc" name="description"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Permissions</label>
                            <div id="permissionsContainer">
                                <!-- Permissions loaded via JS -->
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
        let allPermissions = {};

        $(document).ready(function() {
            loadPermissions();
            loadRoles();
        });

        function loadPermissions() {
            $.getJSON('admin_action.php?action=get_permissions', function(res) {
                if (res.status === 'ok') {
                    allPermissions = res.permissions;
                    renderPermissions();
                }
            });
        }

        function renderPermissions() {
            let html = '';
            for (const [key, label] of Object.entries(allPermissions)) {
                html += `<div class="permission-item">
                            <label>
                                <input type="checkbox" name="permissions[]" value="${key}"> ${label}
                            </label>
                         </div>`;
            }
            $('#permissionsContainer').html(html);
        }

        function loadRoles() {
            $.getJSON('admin_action.php?action=get_roles', function(res) {
                if (res.status === 'ok') {
                    let html = '';
                    res.roles.forEach(role => {
                        let perms = role.permissions.map(p => allPermissions[p] || p).join(', ');
                        if (perms.length > 50) perms = perms.substring(0, 50) + '...';
                        
                        html += `<tr>
                                    <td>${role.id}</td>
                                    <td>${role.name}</td>
                                    <td>${role.description}</td>
                                    <td>${perms}</td>
                                    <td>
                                        <button class="btn btn-primary btn-xs" onclick='editRole(${JSON.stringify(role)})'><i class="fa fa-pencil"></i></button>
                                        <button class="btn btn-danger btn-xs" onclick="deleteRole(${role.id})"><i class="fa fa-trash-o"></i></button>
                                    </td>
                                 </tr>`;
                    });
                    $('#rolesTableBody').html(html);
                } else {
                    alert(res.error);
                }
            });
        }

        function openRoleModal() {
            $('#roleId').val(0);
            $('#roleName').val('');
            $('#roleDesc').val('');
            $('input[name="permissions[]"]').prop('checked', false);
            $('#roleModalLabel').text('Add New Role');
            $('#roleModal').modal('show');
        }

        function editRole(role) {
            $('#roleId').val(role.id);
            $('#roleName').val(role.name);
            $('#roleDesc').val(role.description);
            $('input[name="permissions[]"]').prop('checked', false);
            role.permissions.forEach(p => {
                $(`input[name="permissions[]"][value="${p}"]`).prop('checked', true);
            });
            $('#roleModalLabel').text('Edit Role');
            $('#roleModal').modal('show');
        }

        function saveRole(e) {
            e.preventDefault();
            
            const btn = $(e.target).find('button[type="submit"]');
            const originalText = btn.text();
            btn.prop('disabled', true).text('Saving...');

            const formData = $('#roleForm').serialize();
            
            $.ajax({
                url: 'admin_action.php?action=save_role',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(data) {
                    if (data.status === 'ok') {
                        $('#roleModal').modal('hide');
                        loadRoles();
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
                },
                complete: function() {
                    btn.prop('disabled', false).text(originalText);
                }
            });
        }

        function deleteRole(id) {
            if (confirm('Are you sure you want to delete this role?')) {
                const csrfToken = $('input[name="csrf_token"]').val();
                
                $.ajax({
                    url: 'admin_action.php?action=delete_role',
                    type: 'POST',
                    data: {id: id, csrf_token: csrfToken},
                    dataType: 'json',
                    success: function(data) {
                        if (data.status === 'ok') {
                            loadRoles();
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
