<aside>
  <div id="sidebar"  class="nav-collapse ">
      <ul class="sidebar-menu" id="nav-accordion">
        <p class="centered"><a href="#"><img src="assets/img/ui-sam.jpg" class="img-circle" width="60"></a></p>
        <h5 class="centered"><?php echo isset($_SESSION['login']) ? htmlspecialchars($_SESSION['login']) : 'Admin';?></h5>
        <li class="mt">
            <a href="change-password.php" class="<?php echo (isset($currentPage) && $currentPage == 'change-password') ? 'active' : ''; ?>">
                <i class="fa fa-file"></i>
                <span>Change Password</span>
            </a>
        </li>
          <li class="sub-menu">
              <a href="manage-users.php" class="<?php echo (isset($currentPage) && $currentPage == 'manage-users') ? 'active' : ''; ?>">
                  <i class="fa fa-users"></i>
                  <span>Manage Users</span>
              </a>
          </li>

          <?php 
          // Check if function exists to avoid errors if helper not included
          if(function_exists('check_permission') && check_permission('admin_manage')): 
          ?>
          <li class="sub-menu">
              <a href="manage-admins.php" class="<?php echo (isset($currentPage) && $currentPage == 'manage-admins') ? 'active' : ''; ?>">
                  <i class="fa fa-user-md"></i>
                  <span>Manage Admins</span>
              </a>
          </li>
          <?php endif; ?>

          <li class="sub-menu">
              <a href="bulk-upload.php" class="<?php echo (isset($currentPage) && $currentPage == 'bulk-upload') ? 'active' : ''; ?>">
                  <i class="fa fa-upload"></i>
                  <span>Bulk Upload</span>
              </a>
          </li>
          
          <?php 
          if(function_exists('check_permission') && check_permission('db_transfer')): 
          ?>
          <li class="sub-menu">
              <a href="db-transfer.php" class="<?php echo (isset($currentPage) && $currentPage == 'db-transfer') ? 'active' : ''; ?>">
                  <i class="fa fa-exchange"></i>
                  <span>Database Transfer</span>
              </a>
          </li>
          <?php endif; ?>
          
          <?php 
          if(function_exists('check_permission') && (check_permission('attendance_manage') || check_permission('attendance_scan') || check_permission('attendance_reports'))): 
          ?>
          <li class="sub-menu">
              <a href="attendance-dashboard.php" class="<?php echo (isset($currentPage) && $currentPage == 'attendance-dashboard') ? 'active' : ''; ?>">
                  <i class="fa fa-qrcode"></i>
                  <span>Attendance</span>
              </a>
          </li>
          <?php endif; ?>
          
          <?php 
          // Check if function exists to avoid errors if helper not included
          if(function_exists('check_permission') && check_permission('admin_manage')): 
          ?>
          <li class="sub-menu">
              <a href="javascript:;" class="<?php echo (isset($currentPage) && in_array($currentPage, ['manage-admins', 'manage-roles', 'audit-logs'])) ? 'active' : ''; ?>">
                  <i class="fa fa-cogs"></i>
                  <span>Admin Management</span>
              </a>
              <ul class="sub">
                  <li class="<?php echo (isset($currentPage) && $currentPage == 'manage-admins') ? 'active' : ''; ?>"><a href="manage-admins.php">Administrators</a></li>
                  <li class="<?php echo (isset($currentPage) && $currentPage == 'manage-roles') ? 'active' : ''; ?>"><a href="manage-roles.php">Roles & Permissions</a></li>
                  <li class="<?php echo (isset($currentPage) && $currentPage == 'audit-logs') ? 'active' : ''; ?>"><a href="audit-logs.php">Audit Logs</a></li>
              </ul>
          </li>
          <?php endif; ?>

          <li class="mt">
              <a href="unified-search.php" class="<?php echo (isset($currentPage) && $currentPage == 'unified-search') ? 'active' : ''; ?>">
                  <i class="fa fa-search"></i>
                  <span>Unified Search</span>
              </a>
          </li>

          <li class="sub-menu">
              <a href="javascript:;" >
                  <i class="fa fa-sitemap"></i>
                  <span>System Modules</span>
              </a>
              <ul class="sub">
                  <li><a href="../participant/admin/" target="_blank">Participant Admin</a></li>
                  <li><a href="../scientific/admin/" target="_blank">Scientific Admin</a></li>
                  <li><a href="../poster/admin/" target="_blank">Poster Admin</a></li>
              </ul>
          </li>

          <li class="mt">
              <a href="documentation.php" class="<?php echo (isset($currentPage) && $currentPage == 'documentation') ? 'active' : ''; ?>">
                  <i class="fa fa-book"></i>
                  <span>Documentation</span>
              </a>
          </li>
      </ul>
  </div>
</aside>
