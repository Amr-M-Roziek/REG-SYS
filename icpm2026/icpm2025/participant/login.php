<?php
session_start();
require_once( 'dbconnection.php' );
// stop showing waRNINGs
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
// Function to get the user IP address
function getUserIP() {
  $userIP = '';
  if ( isset( $_SERVER[ 'HTTP_CLIENT_IP' ] ) ) {
    $userIP = $_SERVER[ 'HTTP_CLIENT_IP' ];
  } elseif ( isset( $_SERVER[ 'HTTP_X_FORWARDED_FOR' ] ) ) {
    $userIP = $_SERVER[ 'HTTP_X_FORWARDED_FOR' ];
  } elseif ( isset( $_SERVER[ 'HTTP_X_FORWARDED' ] ) ) {
    $userIP = $_SERVER[ 'HTTP_X_FORWARDED' ];
  } elseif ( isset( $_SERVER[ 'HTTP_X_CLUSTER_CLIENT_IP' ] ) ) {
    $userIP = $_SERVER[ 'HTTP_X_CLUSTER_CLIENT_IP' ];
  } elseif ( isset( $_SERVER[ 'HTTP_FORWARDED_FOR' ] ) ) {
    $userIP = $_SERVER[ 'HTTP_FORWARDED_FOR' ];
  } elseif ( isset( $_SERVER[ 'HTTP_FORWARDED' ] ) ) {
    $userIP = $_SERVER[ 'HTTP_FORWARDED' ];
  } elseif ( isset( $_SERVER[ 'REMOTE_ADDR' ] ) ) {
    $userIP = $_SERVER[ 'REMOTE_ADDR' ];
  } else {
    $userIP = 'UNKNOWN';
  }
  return $userIP;
}


//Code for Registration
if ( isset( $_POST[ 'signup' ] ) ) {
  $fname = $_POST[ 'fname' ];
  $lname = $_POST[ 'lname' ];
  $fullname = $_POST[ 'fullname' ];
  $nationality = $_POST[ 'nationality' ];
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
  if ( $row > 0 ) {
    echo "<script>alert('Email id already exist with another account. Please try with other email id');</script>";
  } else {

    // Code For Sending Email
    $to = "$email";
    $subject = "Welcome $fname-$lname";
    $message = "Welcome $fname-$lname
                    You have been Registered Successfully
                    Please go to login page
                    To login to your account
                    ---------------
                    Important note:
                    ---------------
                    Please save your registration reference number
                    you can get it by login in at:
                    https://reg-sys.com/icpm2025/participant/login.php
                    
                    You can view the agenda here: https://icpm.ae/wp/about-us/agenda/

                                then select Login
                                Use your registerd Email : ( $email )
                                Your password : ( $password )";

    $headers = "From: ICPM@reg-sys.com";
    mail( $to, $subject, $message, $headers );
    print "Registration Done please check your Email(some times on spam or promotios tab) ";

    $msg = mysqli_query( $con, "insert into users(fname,lname,fullname,nationality,email,profession,organization,category,password,contactno,userip,companyref,paypalref) values('$fname','$lname','$fullname','$nationality','$email','$profession','$organization','$category','$enc_password','$contact','$userip','$companyref','$paypalref')" );

    if ( $msg ) {
      if ( isset( $_POST[ 'signup' ] ) ) {
        $password = $_POST[ 'password' ];
        $dec_password = $password;
        $useremail = $_POST[ 'email' ];
        $ret = mysqli_query( $con, "SELECT * FROM users WHERE email='$useremail' and password='$dec_password'" );
        $num = mysqli_fetch_array( $ret );
        if ( $num > 0 ) {
          $extra = "welcome.php";
          $_SESSION[ 'signup' ] = $_POST[ 'email' ];
          $_SESSION[ 'id' ] = $num[ 'id' ];
          $_SESSION[ 'name' ] = $num[ 'fname' ];
          $_SESSION[ 'slname' ] = $num[ 'lname' ];
          $_SESSION[ 'sfullname' ] = $num[ 'fullname' ];
          $_SESSION[ 'snationality' ] = $num[ 'nationality' ];
          $_SESSION[ 'semail' ] = $num[ 'email' ];
          $_SESSION[ 'sprofession' ] = $num[ 'profession' ];
          $_SESSION[ 'sname' ] = $num[ 'fname' ];
          $_SESSION[ 'scategory' ] = $num[ 'category' ];
          $_SESSION[ 'scontact' ] = $num[ 'contact' ];
          $_SESSION[ 'suserip' ] = $num[ 'userip' ];
          $_SESSION[ 'spassword' ] = $num[ 'password' ];
          $_SESSION[ 'scompanyref' ] = $num[ 'companyref' ];
          $_SESSION[ 'spaypalref' ] = $num[ 'paypalref' ];
          $host = $_SERVER[ 'HTTP_HOST' ];
          $uri = rtrim( dirname( $_SERVER[ 'PHP_SELF' ] ), '/\\' );
          header( "location:http://$host$uri/$extra" );
          exit();
        } else {
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
if ( isset( $_POST[ 'login' ] ) ) {
  $password = $_POST[ 'password' ];
  $dec_password = $password;
  $useremail = $_POST[ 'uemail' ];
  $ret = mysqli_query( $con, "SELECT * FROM users WHERE email='$useremail' and password='$dec_password'" );
  $num = mysqli_fetch_array( $ret );
  if ( $num > 0 ) {
    $extra = "welcome.php";
    $_SESSION[ 'login' ] = $_POST[ 'uemail' ];
    $_SESSION[ 'id' ] = $num[ 'id' ];
    $_SESSION[ 'name' ] = $num[ 'fname' ];
    $_SESSION[ 'slname' ] = $num[ 'lname' ];
    $_SESSION[ 'sfullname' ] = $num[ 'fullname' ];
    $_SESSION[ 'snationality' ] = $num[ 'nationality' ];
    $_SESSION[ 'semail' ] = $num[ 'email' ];
    $_SESSION[ 'sprofession' ] = $num[ 'profession' ];
    $_SESSION[ 'sname' ] = $num[ 'fname' ];
    $_SESSION[ 'scategory' ] = $num[ 'category' ];
    $_SESSION[ 'scontact' ] = $num[ 'contact' ];
    $_SESSION[ 'suserip' ] = $num[ 'userip' ];
    $_SESSION[ 'scompanyref' ] = $num[ 'companyref' ];
    $_SESSION[ 'spaypalref' ] = $num[ 'paypalref' ];
    $_SESSION[ 'spassword' ] = $num[ 'password' ];
    $host = $_SERVER[ 'HTTP_HOST' ];
    $uri = rtrim( dirname( $_SERVER[ 'PHP_SELF' ] ), '/\\' );
    header( "location:http://$host$uri/$extra" );
    exit();
  } else {
    echo "<script>alert('Invalid username or password');</script>";
    $extra = "index.php";
    $host = $_SERVER[ 'HTTP_HOST' ];
    $uri = rtrim( dirname( $_SERVER[ 'PHP_SELF' ] ), '/\\' );
    //header("location:http://$host$uri/$extra");
    exit();
  }
}

//Code for Forgot Password

if ( isset( $_POST[ 'send' ] ) ) {
  $femail = $_POST[ 'femail' ];

  $row1 = mysqli_query( $con, "select * from users where email='$femail'" );
  $row2 = mysqli_fetch_array( $row1 );
  if ( $row2 > 0 )
	{
    $email = $row2[ 'email' ];
    $subject = "Information about your password";
    $password = $row2[ 'password' ];
    $message = "Your password is " . $password;
    mail( $email, $subject, $message, "From: ICPM@reg-sys.com" );

    $to = "$email";
    $subject = "ICPM Password Request for $fname-$lname";
    $message = "ICPM Password Request $fname-$lname

                    Please go to login page
                    To login too your account
                    ---------------
                    Important note:
                    ---------------
                    Please save your registration reference number you can get it by login in at: https://icpm.ae/reg/
                                then select Login
                                Use your registerd Email : ( $email )
                                Your password : ( $password )";

    $headers = "From: ICPM@reg-sys.com";
    mail( $to, $subject, $message, $headers );
    echo "<script>alert('Your Password has been sent Successfully');</script>";
  } else {
    echo "<script>alert('Email not registered with us');</script>";
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
  <h1>ICPM 2025 Participant Login System<br>
    <p>Participant Only (CME ONLY) non student</p></h1>
  <div class="sap_tabs">
    <div id="horizontalTab" style="display: block; width: 100%; margin: 0px;">
      <ul class="resp-tabs-list2">
        <li class="resp-tab-item" aria-controls="tab_item-1" role="tab">
          <div class="top-img"><img src="images/top-lock.png" alt=""/></div>
          <span>Login</span></li>
        <li class="resp-tab-item lost" aria-controls="tab_item-2" role="tab">
          <div class="top-img"><img src="images/top-key.png" alt=""/></div>
          <span>Forgot Password</span></li>
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
        <div class="tab-1 resp-tab-content" aria-labelledby="tab_item-1">
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
<script>
        function detailssubmit() {
            alert("Your details were Submitted");
        }
        function onlyNumberKey(evt) {

            // Only ASCII character in that range allowed
            let ASCIICode = (evt.which) ? evt.which : evt.keyCode
            if (ASCIICode > 31 && (ASCIICode < 48 || ASCIICode > 57))
                return false;
            return true;
        }
    </script>
</body>
</html>
