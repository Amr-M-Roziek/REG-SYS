<?php
session_start();
include'dbconnection.php';
//Checking session is valid or not
if (strlen($_SESSION['id']==0)) {
  header('location:logout.php');
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
$query=mysqli_query($con,"update users set id='$id' ,fname='$fname' ,lname='$lname' ,profession='$profession' ,category='$category' ,email='$email' ,organization='$organization' ,password='$password' ,contactno='$contact' where id='$uid'");
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




      <?php $ret=mysqli_query($con,"select * from users where id='".$_GET['uid']."'");
	  while($row=mysqli_fetch_array($ret))

	  {?>
      <div class="badge-wrapper">
            <h5>NAME : <?php echo $row['fname'];?> <?php echo $row['lname'];?></h5>
            <h5>Profession: <?php echo $row['profession'];?> </h5>


  <h6> <img src=https://api.qrserver.com/v1/create-qr-code/?size=58x58&data=participant:<?php echo $row['id'];?> title="Your reference Number is <?php echo $_SESSION['id'];?>" />Ref No: <?php echo $row['id'];?></h6>

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

  </script>

  </body>
</html>
<?php } ?>
