<aside>
  <div id="sidebar" class="nav-collapse ">
      <ul class="sidebar-menu" id="nav-accordion">
        <p class="centered"><a href="#"><img src="../admin/assets/img/ui-sam.jpg" class="img-circle" width="60"></a></p>
        <h5 class="centered"><?php echo isset($_SESSION['login']) ? htmlspecialchars($_SESSION['login']) : 'Main Admin';?></h5>
        <li class="mt">
            <a href="main-dashboard.php" class="<?php echo (isset($currentPage) && $currentPage == 'main-dashboard') ? 'active' : ''; ?>">
                <i class="fa fa-dashboard"></i>
                <span>Main Dashboard</span>
            </a>
        </li>
        <li class="sub-menu">
            <a href="../admin/manage-users.php">
                <i class="fa fa-users"></i>
                <span>Registration Admin</span>
            </a>
        </li>
        <li class="sub-menu">
            <a href="../participant/admin/manage-users.php">
                <i class="fa fa-user"></i>
                <span>Participant Admin</span>
            </a>
        </li>
        <li class="sub-menu">
            <a href="../poster26/admin/manage-users.php">
                <i class="fa fa-picture-o"></i>
                <span>Poster26 Admin</span>
            </a>
        </li>
        <li class="sub-menu">
            <a href="workshop-registrations.php" class="<?php echo (isset($currentPage) && $currentPage == 'workshop-registrations') ? 'active' : ''; ?>">
                <i class="fa fa-wrench"></i>
                <span>Workshop Admin</span>
            </a>
        </li>
      </ul>
  </div>
</aside>

