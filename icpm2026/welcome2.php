<?php
session_start();
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
                        <a href="#"><?php echo $_SESSION['name'];?>
                          <?php echo $_SESSION['slname'];?></h1></a>
                    </li>
                    <li>
                        <a href="logout.php">Logout</a>
                    </li>

                </ul>
            </div>
        </div>
    </nav>
    <div class="container">
        <header class="jumbotron hero-spacer">
            <h1>Welcome To ICPM 14- 2026!</h1>
            <h1>Welcome! <?php echo $_SESSION['name'];?> <?php echo $_SESSION['slname'];?></h1>
            <h1>Reg No: <?php echo $_SESSION['id'];?></h1>
            <h1> <img src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?php echo $_SESSION['id'];?>" title="Your reference Number is <?php echo $_SESSION['id'];?>" /></h1>
            <br>
            <h1 style="text-transform:uppercase"><?php echo $_SESSION['scategory'];?></h1>

              <div>
                <p>Welcome To ICPM 14- 2026</p>
              </div>
            <p><a  href="logout.php" class="btn btn-primary btn-large">Logout </a>
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
