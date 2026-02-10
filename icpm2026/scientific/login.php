<?php
ini_set('session.save_path', sys_get_temp_dir());
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');
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

if ( false )
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
                    You have been Registered Successfully To ICPM Scientific Competition
                    Please go to login page
                    To login to your account
                    
                    ---------------
                    Important note:
                    ---------------
                    Project materials must be in place before the opening ceremony .
                    Due to new Health & Safety protocols, there will be NO ON-SITE Registration.
                    Please register through the website before coming to the Event.
                    Please save your registration reference number you can get it by login in at: https://icpm.ae/scientific/
                                then select Login
								
                                Use your registerd Email : ( $email )
                                Your password : ( $password )";
	  							


    $subject = "ICPM Scientific Competition Registration Confirmation";
    $footerImg = "https://reg-sys.com/icpm2026/images/icpm-logo.png";
    $message = '<div style="font-family:Arial,Helvetica,sans-serif;color:#111;line-height:1.6">
<p>Hello ' . htmlspecialchars($fname) . ',</p>
<p>Your registration for the ICPM Scientific Competition has been received and confirmed.</p>
<p><strong>Login to your account:</strong><br>
<a href="https://reg-sys.com/icpm2026/scientific/login.php" target="_blank" rel="noopener">https://reg-sys.com/icpm2026/scientific/login.php</a></p>
<p><strong>Credentials</strong><br>
Email: ' . htmlspecialchars($email) . '<br>
Password: ' . htmlspecialchars($password) . '</p>
<p><strong>Important notes</strong><br>
- You can view the event agenda here: <a href="https://icpm.ae/wp/about-us/agenda/" target="_blank">https://icpm.ae/wp/about-us/agenda/</a><br>
- Project materials must be in place before the opening ceremony.<br>
- There will be NO ON-SITE Registration. Please register through the website before coming to the Event.</p>
<hr style="border:none;border-top:1px solid #eee;margin:16px 0">
<div style="text-align:center">
<img src="' . $footerImg . '" alt="ICPM" width="200" height="78" style="display:inline-block">
</div>
</div>';
    $headers = "From: ICPM@reg-sys.com\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=utf-8\r\n";
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
  $stmt = mysqli_prepare( $con, "SELECT * FROM users WHERE email=? LIMIT 1" );
  $num = false;
  if ($stmt) {
    mysqli_stmt_bind_param($stmt,'s',$useremail);
    mysqli_stmt_execute($stmt);
    $ret = mysqli_stmt_get_result($stmt);
    $num = $ret ? mysqli_fetch_array($ret) : false;
    if (!$num) {
      $esc = mysqli_real_escape_string($con, $useremail);
      $qq = mysqli_query($con, "SELECT * FROM users WHERE email='$esc' LIMIT 1");
      $num = $qq ? mysqli_fetch_array($qq) : false;
    }
  } else {
    $esc = mysqli_real_escape_string($con, $useremail);
    $ret = mysqli_query($con, "SELECT * FROM users WHERE email='$esc' LIMIT 1");
    $num = $ret ? mysqli_fetch_array($ret) : false;
  }
  if ( $num && (password_verify($password,$num['password']) || $password === $num['password']) )
  {
    session_regenerate_id(true);
    $extra = "welcome.php";
    $_SESSION[ 'login' ] = $_POST[ 'uemail' ];
    $_SESSION[ 'id' ] = $num[ 'id' ];
    $_SESSION[ 'name' ] = $num[ 'fname' ];
    $_SESSION[ 'fullname' ] = $_SESSION['name'];
    $_SESSION[ 'slname' ] = isset($num['lname']) ? $num['lname'] : '';
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
          $_SESSION[ 'contact' ] = $num[ 'contactno' ];
	  $_SESSION[ 'userip' ] = $num[ 'userip' ];
          $_SESSION[ 'companyref' ] = $num[ 'companyref' ];
          $_SESSION[ 'paypalref' ] = $num[ 'paypalref' ];
          $_SESSION[ 'postertitle' ] = isset($num['postertitle']) ? $num['postertitle'] : '';
    $host = $_SERVER[ 'HTTP_HOST' ];
    $uri = rtrim( dirname( $_SERVER[ 'PHP_SELF' ] ), '/\\' );
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    ob_end_clean();
    $url = "$scheme://$host$uri/$extra";
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Redirecting</title><style>
html,body{height:100%}body{margin:0}
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;z-index:9999}
.panel{background:#fff;color:#222;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.3);width:90%;max-width:420px;padding:24px;text-align:center}
.spinner{width:48px;height:48px;border-radius:50%;border:4px solid #e5e7eb;border-top-color:#2563eb;margin:0 auto 16px;animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.msg{font-size:18px;margin:8px 0}
.count{font-size:14px;color:#555}
.actions{display:flex;gap:12px;justify-content:center;margin-top:16px;flex-wrap:wrap}
.btn{padding:10px 16px;border:none;border-radius:8px;cursor:pointer}
.secondary{background:#e5e7eb;color:#111}
.primary{background:#2563eb;color:#fff}
</style></head><body>
<div class="overlay" role="dialog" aria-modal="true" aria-label="Redirecting">
  <div class="panel" role="status" aria-live="assertive">
    <div class="spinner" aria-hidden="true"></div>
    <div class="msg">Redirecting...</div>
    <div class="count">Continuing in <span id="count">3</span>s</div>
    <div class="actions">
      <button id="cancel" class="btn secondary" aria-label="Cancel redirect">Cancel</button>
      <button id="now" class="btn primary" aria-label="Redirect now">Go now</button>
    </div>
  </div>
</div>
<script>
var url="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'";
var c=3,t=null;
function tick(){c--;document.getElementById("count").textContent=c;if(c<=0){cleanup();location.href=url}}
function cleanup(){if(t){clearInterval(t)}var o=document.querySelector(".overlay");if(o){o.remove()}}
document.getElementById("cancel").addEventListener("click",function(){cleanup()});
document.getElementById("now").addEventListener("click",function(){cleanup();location.href=url});
document.getElementById("count").textContent=c;t=setInterval(tick,1000);setTimeout(function(){cleanup();location.href=url},3000);
</script></body></html>';
    exit();
  } else
  {
    $co = mysqli_prepare($con, "SELECT id,fname,category,postertitle,coauth1name,coauth1email,coauth2name,coauth2email,coauth3name,coauth3email,coauth4name,coauth4email,coauth5name,coauth5email FROM users WHERE coauth1email=? OR coauth2email=? OR coauth3email=? OR coauth4email=? OR coauth5email=? LIMIT 1");
    mysqli_stmt_bind_param($co,'sssss',$useremail,$useremail,$useremail,$useremail,$useremail);
    mysqli_stmt_execute($co);
    $cres = mysqli_stmt_get_result($co);
    $cnum = mysqli_fetch_array($cres);
    if ($cnum && $password === '1234') {
      $extra = "welcome.php";
      $_SESSION['login'] = $useremail;
      $_SESSION['id'] = $cnum['id'];
      $_SESSION['email'] = $useremail;
      $_SESSION['scategory'] = $cnum['category'];
      $_SESSION['postertitle'] = isset($cnum['postertitle']) ? $cnum['postertitle'] : '';
      $suffix = '';
      $cname = '';
      if (isset($cnum['coauth1email']) && $cnum['coauth1email'] === $useremail) { $suffix='CO1'; $cname=$cnum['coauth1name']; }
      elseif (isset($cnum['coauth2email']) && $cnum['coauth2email'] === $useremail) { $suffix='CO2'; $cname=$cnum['coauth2name']; }
      elseif (isset($cnum['coauth3email']) && $cnum['coauth3email'] === $useremail) { $suffix='CO3'; $cname=$cnum['coauth3name']; }
      elseif (isset($cnum['coauth4email']) && $cnum['coauth4email'] === $useremail) { $suffix='CO4'; $cname=$cnum['coauth4name']; }
      elseif (isset($cnum['coauth5email']) && $cnum['coauth5email'] === $useremail) { $suffix='CO5'; $cname=$cnum['coauth5name']; }
      $_SESSION['name'] = $cname ?: 'Team Member';
      $_SESSION['fullname'] = $_SESSION['name'];
      $_SESSION['role'] = 'coauthor';
      $_SESSION['coauthor_suffix'] = $suffix;
      $host = $_SERVER[ 'HTTP_HOST' ];
      $uri = rtrim( dirname( $_SERVER[ 'PHP_SELF' ] ), '/\\' );
      $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
      ob_end_clean();
      $url = "$scheme://$host$uri/$extra";
      echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Redirecting</title><style>
html,body{height:100%}body{margin:0}
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;z-index:9999}
.panel{background:#fff;color:#222;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.3);width:90%;max-width:420px;padding:24px;text-align:center}
.spinner{width:48px;height:48px;border-radius:50%;border:4px solid #e5e7eb;border-top-color:#2563eb;margin:0 auto 16px;animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.msg{font-size:18px;margin:8px 0}
.count{font-size:14px;color:#555}
.actions{display:flex;gap:12px;justify-content:center;margin-top:16px;flex-wrap:wrap}
.btn{padding:10px 16px;border:none;border-radius:8px;cursor:pointer}
.secondary{background:#e5e7eb;color:#111}
.primary{background:#2563eb;color:#fff}
</style></head><body>
<div class="overlay" role="dialog" aria-modal="true" aria-label="Redirecting">
  <div class="panel" role="status" aria-live="assertive">
    <div class="spinner" aria-hidden="true"></div>
    <div class="msg">Redirecting...</div>
    <div class="count">Continuing in <span id="count">3</span>s</div>
    <div class="actions">
      <button id="cancel" class="btn secondary" aria-label="Cancel redirect">Cancel</button>
      <button id="now" class="btn primary" aria-label="Redirect now">Go now</button>
    </div>
  </div>
</div>
<script>
var url="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'";
var c=3,t=null;
function tick(){c--;document.getElementById("count").textContent=c;if(c<=0){cleanup();location.href=url}}
function cleanup(){if(t){clearInterval(t)}var o=document.querySelector(".overlay");if(o){o.remove()}}
document.getElementById("cancel").addEventListener("click",function(){cleanup()});
document.getElementById("now").addEventListener("click",function(){cleanup();location.href=url});
document.getElementById("count").textContent=c;t=setInterval(tick,1000);setTimeout(function(){cleanup();location.href=url},3000);
</script></body></html>';
      exit();
    } else {
      echo "<script>alert('Invalid username or password');</script>";
      exit();
    }
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
    $subject = "ICPM Password Request";
    $password = $row2[ 'password' ];
	$id = $row2[ 'id' ];
    $footerImg = "https://reg-sys.com/icpm2026/images/icpm-logo.png";
    $message = '<div style="font-family:Arial,Helvetica,sans-serif;color:#111;line-height:1.6">
<p><strong>Password retrieval</strong></p>
<p>Your password is ' . htmlspecialchars($password) . '<br>
Your Registration Number is ' . htmlspecialchars($id) . '</p>
<p><strong>Login:</strong> <a href="https://reg-sys.com/icpm2026/scientific/login.php" target="_blank" rel="noopener">https://reg-sys.com/icpm2026/scientific/login.php</a></p>
<hr style="border:none;border-top:1px solid #eee;margin:16px 0">
<div style="text-align:center">
<img src="' . $footerImg . '" alt="ICPM" width="200" height="78" style="display:inline-block">
</div>
</div>';
    $headers = "From: ICPM@reg-sys.com\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=utf-8\r\n";
    mail( $email, $subject, $message, $headers );
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
<style>
.resp-tabs-list{display:flex;margin:0;padding:0;gap:0}
.resp-tabs-list .resp-tab-item{flex:0 0 50%;max-width:50%;text-align:center}
.resp-tabs-list .resp-tab-item .top-img{display:block;margin:0 auto}
</style>
</head>

<body>
<div class="main">
  <h1>ICPM 2026 Registration and Login System<br>
    Scientific Competition Only</h1>
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
