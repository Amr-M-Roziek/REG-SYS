<?php
session_start();
include 'dbconnection.php';
mysqli_set_charset($con, 'utf8mb4');

if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

if (isset($_POST['submit'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $contact = $_POST['contact'];
    $profession = $_POST['profession'];
    $organization = $_POST['organization'];
    $category = 'Participant'; // Forced

    // Optional: Fullname
    $fullname = trim($fname . ' ' . $lname);

    // Check duplicate
    $ret = mysqli_query($con, "select id from users where email='$email'");
    $result = mysqli_fetch_array($ret);
    if ($result) {
        $_SESSION['msg'] = "This email already exists.";
    } else {
        $enc_password = password_hash($password, PASSWORD_DEFAULT);
        $msg = mysqli_query($con, "insert into users(fname,lname,fullname,email,password,contactno,profession,organization,category,posting_date) values('$fname','$lname','$fullname','$email','$enc_password','$contact','$profession','$organization','$category',NOW())");
        if ($msg) {
            $_SESSION['msg'] = "User added successfully";
            echo "<script>alert('User added successfully'); window.location.href='manage-users.php';</script>";
        } else {
            $_SESSION['msg'] = "Error: " . mysqli_error($con);
        }
    }
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

    <title>Admin | Add User</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
</head>

<body>

<section id="container" >
    <header class="header black-bg">
        <div class="sidebar-toggle-box">
            <div class="fa fa-bars tooltips" data-placement="right" data-original-title="Toggle Navigation"></div>
        </div>
        <a href="#" class="logo"><b>Admin Dashboard</b></a>
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
                <h5 class="centered"><?php echo $_SESSION['login'];?></h5>
                <li class="mt">
                    <a href="change-password.php">
                        <i class="fa fa-file"></i>
                        <span>Change Password</span>
                    </a>
                </li>
                <li class="sub-menu">
                    <a href="manage-users.php" >
                        <i class="fa fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </li>
                <li class="sub-menu">
                    <a href="whatsapp-dashboard.php" >
                        <i class="fa fa-comments"></i>
                        <span>WhatsApp Bulk</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>
    <section id="main-content">
        <section class="wrapper">
            <h3><i class="fa fa-angle-right"></i> Add User</h3>
            <div class="row">
                <div class="col-md-12">
                    <div class="content-panel">
                        <p align="center" style="color:#F00;"><?php if(isset($_SESSION['msg'])) { echo $_SESSION['msg']; $_SESSION['msg']=""; } ?></p>
                        <form class="form-horizontal style-form" name="form1" method="post" action="">
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">First Name</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="fname" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Last Name</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="lname" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Email</label>
                                <div class="col-sm-10">
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Password</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="password" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Contact No.</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="contact" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Profession</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="profession" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Organization</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="organization" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Category</label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" name="category" value="Participant" readonly>
                                </div>
                            </div>
                            <div style="margin-left:100px;">
                                <button type="submit" name="submit" class="btn btn-theme">Add User</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </section>
    <script src="assets/js/jquery.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script class="include" type="text/javascript" src="assets/js/jquery.dcjqaccordion.2.7.js"></script>
    <script src="assets/js/jquery.scrollTo.min.js"></script>
    <script src="assets/js/jquery.nicescroll.js" type="text/javascript"></script>
    <script src="assets/js/common-scripts.js"></script>
</body>
</html>
