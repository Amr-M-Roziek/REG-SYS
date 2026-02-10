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

  $coauth1name=$_POST['coauth1name'];

  $coauth2name=$_POST['coauth2name'];

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

  </head>



  <body>



  <section id="container" >





      <?php $ret=mysqli_query($con,"select * from users where id='".$_GET['uid']."'");

	  while($row=mysqli_fetch_array($ret))



	  {?>

      <section id="main-content">

          <section class="wrapper" style="text-transform: uppercase;">
            <BR><BR><BR><BR><BR><BR><BR>
    <BR><BR><BR>
            <h5>NAME : <?php echo htmlspecialchars($row['fname']);?> <?php echo isset($row['lname']) ? htmlspecialchars($row['lname']) : '';?></h5>
            <h5>PROFESSION : <?php echo htmlspecialchars($row['profession']);?> </h5>


    <h6> <img src="https://api.qrserver.com/v1/create-qr-code/?size=58x58&data=<?php echo urlencode($row['id']);?>" title="Your reference Number is <?php echo htmlspecialchars($row['id']);?>" alt="Ref No QR" />Ref No: <?php echo htmlspecialchars($row['id']);?></h6>

    </section>


        <?php } ?>

      </section></section>

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
