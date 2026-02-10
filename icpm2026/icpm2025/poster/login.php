<?php
session_start();
require_once( 'dbconnection.php' );


// Function to get the user IP address
function getUserIP() {
    $userIP =   '';
    if(isset($_SERVER['HTTP_CLIENT_IP'])){
        $userIP =   $_SERVER['HTTP_CLIENT_IP'];
    }elseif(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $userIP =   $_SERVER['HTTP_X_FORWARDED_FOR'];
    }elseif(isset($_SERVER['HTTP_X_FORWARDED'])){
        $userIP =   $_SERVER['HTTP_X_FORWARDED'];
    }elseif(isset($_SERVER['HTTP_X_CLUSTER_CLIENT_IP'])){
        $userIP =   $_SERVER['HTTP_X_CLUSTER_CLIENT_IP'];
    }elseif(isset($_SERVER['HTTP_FORWARDED_FOR'])){
        $userIP =   $_SERVER['HTTP_FORWARDED_FOR'];
    }elseif(isset($_SERVER['HTTP_FORWARDED'])){
        $userIP =   $_SERVER['HTTP_FORWARDED'];
    }elseif(isset($_SERVER['REMOTE_ADDR'])){
        $userIP =   $_SERVER['REMOTE_ADDR'];
    }else{
        $userIP =   'UNKNOWN';
    }
    return $userIP;
}


//Code for Registration

if ( isset( $_POST[ 'signup' ] ) )
{
  $fname = $_POST[ 'fname' ];
  $nationality = $_POST[ 'nationality' ];
  $coauth1name = $_POST[ 'coauth1name' ];
  $coauth1nationality = $_POST[ 'coauth1nationality' ];	
  $coauth2name = $_POST[ 'coauth2name' ];
  $coauth2nationality = $_POST[ 'coauth2nationality' ];
  $coauth3name = $_POST[ 'coauth3name' ];
  $coauth3nationality = $_POST[ 'coauth3nationality' ];
  $coauth4name = $_POST[ 'coauth4name' ];
  $coauth4nationality = $_POST[ 'coauth4nationality' ];
  $coauth5name = $_POST[ 'coauth5name' ];
  $coauth5nationality = $_POST[ 'coauth5nationality' ];
  $email = $_POST[ 'email' ];
  $profession = $_POST[ 'profession' ];
  $organization = $_POST[ 'organization' ];
  $category = $_POST[ 'category' ];
  $password = $_POST[ 'password' ];
  $contact = $_POST[ 'contact' ];
  $userip = $_POST[ 'userip' ];
  $companyref = $_POST[ 'companyref' ];
  $paypalref = $_POST[ 'paypalref' ];
  $enc_password = $password;
  $sql = mysqli_query( $con, "select id from users where email='$email'" );
  $row = mysqli_num_rows( $sql );
  if ( $row > 0 )
  {
    echo "<script>alert('Email id already exist with another account. Please try with other email id');</script>";
  } else {


    // Code For Sending Email
    $to = "$email";
    $subject = "Welcome $fname";
    $message = "Welcome $fname
                    You have been Registered Successfully To ICPM Poster Competetion
                    Please go to login page
                    To login to your account
					
                    ---------------
                    Important note:
                    ---------------
                    Poster must be in its place before the opening ceremony .
                    Due to new Health & Safety protocols, there will be NO ON-SITE Registration.
                    Please register through the website before coming to the Event.
                    Please save your registration reference number you can get it by login in at: https://icpm.ae/poster/
                                then select Login
								
                                Use your registerd Email : ( $email )
                                Your password : ( $password )";
	  							


    $headers = "From: ICPM@reg-sys.com";
    mail( $to, $subject, $message, $headers );
    print "Registration Done please check your Email(some times on spam or promotios tab) ";
    $msg = mysqli_query( $con, "insert into users(fname,nationality,coauth1name,coauth1nationality,coauth2name,coauth2nationality,coauth3name,coauth3nationality,coauth4name,coauth4nationality,coauth5name,coauth5nationality,email,profession,organization,category,password,contactno,userip,companyref,paypalref) values('$fname','$nationality','$coauth1name','$coauth1nationality','$coauth2name','$coauth2nationality','$coauth3name','$coauth3nationality','$coauth4name','$coauth4nationality','$coauth5name','$coauth5nationality','$email','$profession','$organization','$category','$enc_password','$contact','$userip','$companyref','$paypalref')" );


    if ( $msg )
    {
      if ( isset( $_POST[ 'signup' ] ) )
      {
        $password = $_POST[ 'password' ];
        $dec_password = $password;
        $useremail = $_POST[ 'email' ];
        $ret = mysqli_query( $con, "SELECT * FROM users WHERE email='$useremail' and password='$dec_password'" );
        $num = mysqli_fetch_array( $ret );

        if ( $num > 0 )
        {
          $extra = "welcome.php";
          $_SESSION[ 'signup' ] = $_POST[ 'email' ];
          $_SESSION[ 'id' ] = $num[ 'id' ];
          $_SESSION[ 'name' ] = $num[ 'fname' ];
		  $_SESSION[ 'snationality' ] = $num[ 'nationality' ];
          $_SESSION[ 'coauth1name' ] = $num[ 'coauth1name' ];
		  $_SESSION[ 'scoauth1nationality' ] = $num[ 'coauth1nationality' ];
          $_SESSION[ 'coauth2name' ] = $num[ 'coauth2name' ];
		  $_SESSION[ 'scoauth2nationality' ] = $num[ 'coauth2nationality' ];
		  $_SESSION[ 'coauth3name' ] = $num[ 'coauth3name' ];
		  $_SESSION[ 'scoauth3nationality' ] = $num[ 'coauth3nationality' ];
          $_SESSION[ 'coauth4name' ] = $num[ 'coauth4name' ];
		  $_SESSION[ 'scoauth4nationality' ] = $num[ 'coauth4nationality' ];
		  $_SESSION[ 'coauth5name' ] = $num[ 'coauth5name' ];
		  $_SESSION[ 'scoauth5nationality' ] = $num[ 'coauth5nationality' ];
          $_SESSION[ 'email' ] = $num[ 'email' ];
          $_SESSION[ 'profession' ] = $num[ 'profession' ];
          $_SESSION[ 'scategory' ] = $num[ 'category' ];
          $_SESSION[ 'contact' ] = $num[ 'contact' ];
		  $_SESSION[ 'userip' ] = $num[ 'userip' ];
          $_SESSION[ 'companyref' ] = $num[ 'companyref' ];
          $_SESSION[ 'paypalref' ] = $num[ 'paypalref' ];
          $host = $_SERVER[ 'HTTP_HOST' ];
          $uri = rtrim( dirname( $_SERVER[ 'PHP_SELF' ] ), '/\\' );
          header( "location:http://$host$uri/$extra" );
          exit();
        } else
        {
          echo "<script>alert('Invalid username or password');</script>";
          $extra = "index.php";
          $host = $_SERVER[ 'HTTP_HOST' ];
          $uri = rtrim( dirname( $_SERVER[ 'PHP_SELF' ] ), '/\\' );
        
			//header("location:http://$host$uri/$extra");
          exit();
        }
      }
    }
  }
}


// Code for login

if ( isset( $_POST[ 'login' ] ) )
{
  $password = $_POST[ 'password' ];
  $dec_password = $password;
  $useremail = $_POST[ 'uemail' ];
  $ret = mysqli_query( $con, "SELECT * FROM users WHERE email='$useremail' and password='$dec_password'" );
  $num = mysqli_fetch_array( $ret );
  if ( $num > 0 )
  {
    $extra = "welcome.php";
    $_SESSION[ 'login' ] = $_POST[ 'uemail' ];
    $_SESSION[ 'id' ] = $num[ 'id' ];
    $_SESSION[ 'name' ] = $num[ 'fname' ];
		  $_SESSION[ 'snationality' ] = $num[ 'nationality' ];
          $_SESSION[ 'coauth1name' ] = $num[ 'coauth1name' ];
		  $_SESSION[ 'scoauth1nationality' ] = $num[ 'coauth1nationality' ];
          $_SESSION[ 'coauth2name' ] = $num[ 'coauth2name' ];
		  $_SESSION[ 'scoauth2nationality' ] = $num[ 'coauth2nationality' ];
	      $_SESSION[ 'coauth3name' ] = $num[ 'coauth3name' ];
		  $_SESSION[ 'scoauth3nationality' ] = $num[ 'coauth3nationality' ];
          $_SESSION[ 'coauth4name' ] = $num[ 'coauth4name' ];
		  $_SESSION[ 'scoauth4nationality' ] = $num[ 'coauth4nationality' ];
		  $_SESSION[ 'coauth5name' ] = $num[ 'coauth5name' ];
		  $_SESSION[ 'scoauth5nationality' ] = $num[ 'coauth5nationality' ];
          $_SESSION[ 'email' ] = $num[ 'email' ];
          $_SESSION[ 'profession' ] = $num[ 'profession' ];
          $_SESSION[ 'scategory' ] = $num[ 'category' ];
          $_SESSION[ 'contact' ] = $num[ 'contact' ];
		  $_SESSION[ 'userip' ] = $num[ 'userip' ];
          $_SESSION[ 'companyref' ] = $num[ 'companyref' ];
          $_SESSION[ 'paypalref' ] = $num[ 'paypalref' ];
    $host = $_SERVER[ 'HTTP_HOST' ];
    $uri = rtrim( dirname( $_SERVER[ 'PHP_SELF' ] ), '/\\' );
    header( "location:http://$host$uri/$extra" );
    exit();
  } else
  {
    echo "<script>alert('Invalid username or password');</script>";
    $extra = "index.php";
    $host = $_SERVER[ 'HTTP_HOST' ];
    $uri = rtrim( dirname( $_SERVER[ 'PHP_SELF' ] ), '/\\' );
	  
    //header("location:http://$host$uri/$extra");
    exit();
  }
}


//Code for Forgot Password


if ( isset( $_POST[ 'send' ] ) )
{
  $femail = $_POST[ 'femail' ];
  $row1 = mysqli_query( $con, "select email,password,id from users where email='$femail'" );
  $row2 = mysqli_fetch_array( $row1 );
  if ( $row2 > 0 )
  {
    $email = $row2[ 'email' ];
    $subject = "Information about your password";
    $password = $row2[ 'password' ];
    $message = "Your password is " . $password;
	$id = $row2[ 'id' ];
    $message = "Your Ref Number is " . $id;
    mail( $email, $subject, $message, "From: ICPM@reg-sys.com" );
	  
	   $to = "$email";
    $subject = "ICPM Password Request for $fname-$lname";
    $message = "ICPM Password Request $fname-$lname

                    Please go to login page
                    To login too your account
                    ---------------
                    Important note:
                    ---------------
                    Due to new Health & Safety protocols, there will be NO ON-SITE Registration.
                    Please register through the website before coming to the Event.
                    All covid-19 instructions should be followed During Exhibition.
                    Please save your registration reference number you can get it by login in at: https://icpm.ae/uae/reg/
                                then select Login
                                Use your registerd Email : ( $email )
                                Your password : ( $password )
								your Registration Number : ( $id )";
	  						
    $headers = "From: ICPM@reg-sys.com";
	mail( $email, $subject, $message, "From: ICPM@reg-sys.com" );
    echo "<script>alert('Your Password has been sent Successfully');</script>";
  } else
  {
    echo "<script>alert('Email not register with us');</script>";
  }
}

?>
<!DOCTYPE html>

<html>
<head>
<title>Login System</title>
<link href="css/style.css" rel='stylesheet' type='text/css' />
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="keywords" content="Elegent Tab Forms,Login Forms,Sign up Forms,Registration Forms,News latter Forms,Elements"./>
<script type="application/x-javascript"> addEventListener("load", function() { setTimeout(hideURLbar, 0); }, false); function hideURLbar(){ window.scrollTo(0,1); } </script> 
</script> 
<script src="js/jquery.min.js"></script> 
<script src="js/easyResponsiveTabs.js" type="text/javascript"></script> 
<script type="text/javascript">

					$(document).ready(function () {

						$('#horizontalTab').easyResponsiveTabs({

							type: 'default',

							width: 'auto',

							fit: true

						});

					});

				   </script>
<link href='http://fonts.googleapis.com/css?family=Source+Sans+Pro:200,400,600,700,200italic,300italic,400italic,600italic|Lora:400,700,400italic,700italic|Raleway:400,500,300,600,700,200,100' rel='stylesheet' type='text/css'>
<script type="text/javascript">

    function ShowHideDiv() {

        var chkYes = document.getElementById("chkYes");

        var dvPaymentType = document.getElementById("dvPaymentType");

        dvPaymentType.style.display = chkYes.checked ? "block" : "none";



        var chkNo = document.getElementById("chkNo");

        var dvPaymentPaypal = document.getElementById("dvPaymentPaypal");

        dvPaymentPaypal.style.display = chkNo.checked ? "block" : "none";

    }

</script>
</head>

<body>
<div class="main">
  <h1>ICPM 2024 Registration and Login System<br>
    Poster Competition Only</h1>
  <div class="sap_tabs">
    <div id="horizontalTab" style="display: block; width: 100%; margin: 0px;">
      <ul class="resp-tabs-list">
        <li class="resp-tab-item" aria-controls="tab_item-2" role="tab">
          <div class="top-img"><img src="images/top-lock.png" alt=""/></div>
          <span>Login</span> </li>
        <li class="resp-tab-item" aria-controls="top-key.png" role="tab">
          <div class="top-img"><img src="images/top-lock.png" alt=""/></div>
          <span>Reset password</span></li>
        <div class="clear"></div>
      </ul>
      <div class="resp-tabs-container">
        <div class="tab-1 resp-tab-content" aria-labelledby="tab_item-0">
          <div class="facts">
            <div class="login">
              <div class="buttons"> </div>
              <form name="login" action="" method="post">
                <input type="text" class="text" name="uemail" value="" placeholder="Enter your registered email"  >
                <a href="#" class=" icon email"></a>
                <input type="password" value="" name="password" placeholder="Enter valid password">
                <a href="#" class=" icon lock"></a>
                <div class="p-container">
                  <div class="submit two">
                    <input type="submit" name="login" value="LOG IN" >
                  </div>
                  <div class="clear"> </div>
                </div>
              </form>
            </div>
          </div>
        </div>
        <div class="tab-2 resp-tab-content" aria-labelledby="tab_item-1">
          <div class="facts">
            <div class="login">
              <div class="buttons"> </div>
              <form name="login" action="" method="post">
                <input type="text" class="text" name="femail" value="" placeholder="Enter your registered email" required  >
                <a href="#" class=" icon email"></a>
                <div class="submit three">
                  <input type="submit" name="send" onClick="myFunction()" value="Send Email" >
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
