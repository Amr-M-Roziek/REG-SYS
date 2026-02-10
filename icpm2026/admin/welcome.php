<?php
session_start();
include'dbconnection.php';

// Handle multi-database connection
$db_source = isset($_GET['db']) ? $_GET['db'] : 'reg';
$db_con = $con; // Default to main DB

if ($db_source == 'participant') {
    $db_con = @mysqli_connect('localhost', 'regsys_part', 'regsys@2025', 'regsys_participant');
    if (!$db_con) $db_con = @mysqli_connect('localhost', 'root', '', 'regsys_participant');
} elseif ($db_source == 'poster') {
    $db_con = @mysqli_connect('localhost', 'regsys_poster', 'regsys@2025', 'regsys_poster26');
    if (!$db_con) $db_con = @mysqli_connect('localhost', 'root', '', 'regsys_poster26');
} elseif ($db_source == 'workshop') {
    $db_con = @mysqli_connect('localhost', 'regsys_ws', 'regsys@2025', 'regsys_workshop');
    if (!$db_con) $db_con = @mysqli_connect('localhost', 'root', '', 'regsys_workshop');
}

//Checking session is valid or not
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
  header('location:logout.php');
  exit();
  } else{

// for updating user info
if(isset($_POST['Submit']))
{
  $id=$_POST['id'];
  $fname=$_POST['fname'];
  $lname=$_POST['lname'];
  $contact=$_POST['contact'];
  $email=$_POST['email'];
  $profession=$_POST['profession'];
  $category=$_POST['category'];
  $organization=$_POST['organization'];
  $password=$_POST['password'];
  $uid=intval($_GET['uid']);
$query=mysqli_query($db_con,"update users set id='$id' ,fname='$fname' ,lname='$lname' ,profession='$profession' ,category='$category' ,email='$email' ,organization='$organization' ,password='$password' ,contactno='$contact' where id='$uid'");
$_SESSION['msg']="Profile Updated successfully";
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

    <title>Admin | Print Profile</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            @page {
                size: 9.5cm 13.3cm;
                margin: 0;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .badge-wrapper {
                width: 9.5cm;
                height: 13.3cm;
                margin: 0;
                page-break-after: always;
                overflow: hidden;
            }
        }
        .badge-wrapper {
            width: 9.5cm;
            height: 13.3cm;
            border: 1px dashed #ccc; /* Visual aid for screen, removed in print if needed or helpful */
            margin: 20px auto;
            background: white;
            box-sizing: border-box;
            text-transform: uppercase;
            text-align: center;
            padding-top: 7.2cm; /* Replaces the <BR> tags for consistent positioning */
        }
        @media print {
            .badge-wrapper {
                border: none;
                margin: 0;
            }
        }
        /* Hide UI elements when printing */
        @media print {
            #container, header, aside {
                display: none;
            }
            /* Reset container visibility for the badge */
            #main-content, .wrapper {
                display: block !important;
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
  </head>

  <body>

  <?php $ret=mysqli_query($db_con,"select * from users where id='".$_GET['uid']."'");
  while($row=mysqli_fetch_array($ret))
  {?>
      <div class="no-print" style="text-align: center; padding: 20px; background: #eee; border-bottom: 1px solid #ccc;">
        <?php if ($db_source == 'poster'): ?>
            <h4>Select Role for Poster Presenter</h4>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-primary" onclick="setRole('Author')">Author</button>
                <button type="button" class="btn btn-info" onclick="setRole('Co-Author')">Co-Author</button>
            </div>
            <br><br>
        <?php endif; ?>
        <button class="btn btn-warning btn-lg" onclick="window.print()"><i class="fa fa-print"></i> Print Badge</button>
      </div>

      <div class="badge-wrapper">
            <h5>NAME : <?php echo $row['fname'];?> <?php echo $row['lname'];?></h5>
            <h5>Profession: <?php echo $row['profession'];?> </h5>
            <?php if ($db_source == 'poster'): ?>
            <h5 id="poster-role" style="font-weight: bold; margin: 5px 0;">Author</h5>
            <?php endif; ?>

  <h6> <img src="https://api.qrserver.com/v1/create-qr-code/?size=58x58&data=<?php echo $db_source . ':' . $row['id'];?>" title="Your reference Number is <?php echo $_SESSION['id'];?>" />Ref No: <?php echo $row['id'];?></h6>

    </div>
  <?php } ?>

    <script src="assets/js/jquery.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script class="include" type="text/javascript" src="assets/js/jquery.dcjqaccordion.2.7.js"></script>
    <script src="assets/js/jquery.scrollTo.min.js"></script>
    <script src="assets/js/jquery.nicescroll.js" type="text/javascript"></script>
    <script src="assets/js/common-scripts.js"></script>
  <script>
      $(function(){
          $('select.styled').customSelect();
      });

      function setRole(role) {
          $('#poster-role').text(role);
      }
  </script>

  </body>
</html>
<?php } ?>
