<?php
session_start();
require_once('dbconnection.php');
require_once('session_manager.php');

// Session Security Check
$session_status = check_session_validity($con);
if ($session_status !== 'valid') {
    $msg = "Session expired or invalid.";
    if ($session_status === 'duplicate') {
        $msg = "You have been logged out because your account was accessed from another device.";
    } elseif ($session_status === 'timeout') {
        $msg = "Your session has expired due to inactivity.";
    }
    
    // Clear local session just in case
    session_unset();
    session_destroy();
    
    // Redirect to login (index.php or login.php)
    echo "<script>alert('$msg'); window.location.href='login.php';</script>";
    exit();
}

if (strlen($_SESSION['id']==0)) {
  header('location:logout.php');
  } else{

?><!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Welcome </title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/heroic-features.css" rel="stylesheet">
</head>
<body>
    <?php include 'header.php'; ?>
    <nav class="navbar navbar-inverse" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="#">Welcome !</a>
            </div>
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <li>
                        <a href="#"><?php echo $_SESSION['name'];?> <?php echo $_SESSION['slname'];?>
                          </H2></a>
                    </li>
                    <li>
                        <a href="logout.php">Logout</a>
                    </li>

                </ul>
            </div>
        </div>
    </nav>
    <div>
    <center>  <img src="images/icpm-logo.png" alt=""/ ></center>
    </div>
    <div class="container" style="text-align: center;">
        <header class="jumbotron hero-spacer">
            <H2>Welcome! <?php echo $_SESSION['name'];?> <?php echo $_SESSION['slname'];?></H2>
              <H2>To ICPM 14 - 2026</H2>

            <H2> <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo $_SESSION['id'];?>" title="Your reference Number is <?php echo $_SESSION['id'];?>" /></H2>
              <H3>Reg No: <?php echo $_SESSION['id'];?></H3>
            <br>
            <H2 style="text-transform:uppercase"><?php echo $_SESSION['scategory'];?></H2>
            <p>
                <a href="download-certificate.php" class="btn btn-success btn-large" style="margin-right: 10px;" target="_blank">Download Certificate</a>
                <a href="logout.php" class="btn btn-primary btn-large"> Logout </a>
                <a href="https://icpm.ae/" class="btn btn-primary btn-large"> Back Home </a>
            </p>
            </p>
        </header>

        <hr>





        </div>

        <hr>


    </div>
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
</body>

</html>
<?php } ?>
