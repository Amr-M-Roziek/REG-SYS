<?php
session_start();
require_once('dbconnection.php');
mysqli_set_charset($con, 'utf8mb4');
error_reporting(E_ALL ^ E_WARNING);
//Code for Registration
if(isset($_POST['signup']))
{
	$fname=$_POST['fname'];
	$lname=$_POST['lname'];
	$email=$_POST['email'];
	$profession=$_POST['profession'];
	$organization=$_POST['organization'];
	$category=$_POST['category'];
	$password=$_POST['password'];
	$contact=$_POST['contact'];
	$companyref=$_POST['companyref'];
	$paypalref=$_POST['paypalref'];
	$enc_password=password_hash($password, PASSWORD_DEFAULT);
$stmt=mysqli_prepare($con,"select id from users where email=?");
mysqli_stmt_bind_param($stmt,'s',$email);
mysqli_stmt_execute($stmt);
$res=mysqli_stmt_get_result($stmt);
$row=mysqli_num_rows($res);
if($row>0)
{
	echo "<script>alert('Email id already exist with another account. Please try with other email id');</script>";
} else{

        // Email will be sent after registration ID is available

	$stmt=mysqli_prepare($con,"insert into users(fname,lname,email,profession,organization,category,password,contactno,companyref,paypalref) values(?,?,?,?,?,?,?,?,?,?)");
	mysqli_stmt_bind_param($stmt,'ssssssssss',$fname,$lname,$email,$profession,$organization,$category,$enc_password,$contact,$companyref,$paypalref);
	$msg=mysqli_stmt_execute($stmt);

if($msg)
{
	if(isset($_POST['signup']))
{
$password=$_POST['password'];
$useremail=$_POST['email'];
$stmt=mysqli_prepare($con,"SELECT * FROM users WHERE email=?");
mysqli_stmt_bind_param($stmt,'s',$useremail);
mysqli_stmt_execute($stmt);
$ret=mysqli_stmt_get_result($stmt);
$num=mysqli_fetch_array($ret);
if($num && password_verify($password,$num['password']))
{
$to = $email;
$footerImg = "https://reg-sys.com/icpm2026/images/icpm-logo.png";
$subject = "ICPM Registration Confirmation - Ref #".$num['id'];
$message = '<div style="font-family:Arial,Helvetica,sans-serif;color:#111;line-height:1.6">
<p>Hello ' . htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($lname, ENT_QUOTES, 'UTF-8') . ',</p>
<p>You have been registered successfully.</p>
<p>Please save your registration reference number.</p>
<p><strong>Credentials</strong><br>
Email: ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '<br>
Password: ' . htmlspecialchars($password, ENT_QUOTES, 'UTF-8') . '<br>
Registration Number: ' . htmlspecialchars($num['id'], ENT_QUOTES, 'UTF-8') . '</p>
<p><img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . htmlspecialchars($num['id'], ENT_QUOTES, 'UTF-8') . '" alt="QR ' . htmlspecialchars($num['id'], ENT_QUOTES, 'UTF-8') . '"></p>
<hr style="border:none;border-top:1px solid #eee;margin:16px 0">
<div style="text-align:center">
<img src="' . $footerImg . '" alt="ICPM" width="200" height="78" style="display:inline-block">
</div>
</div>';
$headers = "From: ICPM@reg-sys.com\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=utf-8\r\n";
@mail($to, $subject, $message, $headers);
$sid=session_id();
session_regenerate_id(true);
$extra="welcome.php";
$_SESSION['signup']=$_POST['email'];
$_SESSION['id']=$num['id'];
$_SESSION['name']=$num['fname'];
$_SESSION['lname']=$num['lname'];
$_SESSION['email']=$num['email'];
$_SESSION['profession']=$num['profession'];
$_SESSION['name']=$num['fname'];
$_SESSION['category']=$num['category'];
$_SESSION['contact']=$num['contact'];
$_SESSION['companyref']=$num['companyref'];
$_SESSION['paypalref']=$num['paypalref'];
$host=$_SERVER['HTTP_HOST'];
$uri=rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
header("location:http://$host$uri/$extra");
exit();
}
else
{
echo "<script>alert('Invalid username or password');</script>";
$extra="index.php";
$host  = $_SERVER['HTTP_HOST'];
$uri  = rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
//header("location:http://$host$uri/$extra");
exit();
}
}
}
}
}

// Code for login
if(isset($_POST['login']))
{
$password=$_POST['password'];
$useremail=$_POST['uemail'];
$stmt=mysqli_prepare($con,"SELECT * FROM users WHERE email=?");
mysqli_stmt_bind_param($stmt,'s',$useremail);
mysqli_stmt_execute($stmt);
$ret= mysqli_stmt_get_result($stmt);
$num=mysqli_fetch_array($ret);
if($num && password_verify($password,$num['password']))
{
$sid=session_id();
session_regenerate_id(true);
$extra="welcome.php";
$_SESSION['login']=$_POST['uemail'];
$_SESSION['id']=$num['id'];
$_SESSION['name']=$num['fname'];
$host=$_SERVER['HTTP_HOST'];
$uri=rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
header("location:http://$host$uri/$extra");
exit();
}
else
{
echo "<script>alert('Invalid username or password');</script>";
$extra="index.php";
$host  = $_SERVER['HTTP_HOST'];
$uri  = rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
//header("location:http://$host$uri/$extra");
exit();
}
}

//Code for Forgot Password

if(isset($_POST['send']))
{
$femail=$_POST['femail'];

$stmt=mysqli_prepare($con,"select email from users where email=?");
mysqli_stmt_bind_param($stmt,'s',$femail);
mysqli_stmt_execute($stmt);
$row1=mysqli_stmt_get_result($stmt);
$row2=mysqli_fetch_array($row1);
if($row2)
{
$email = $row2['email'];
$subject = "Information about your password";
$newPassword=bin2hex(random_bytes(4));
$enc=password_hash($newPassword, PASSWORD_DEFAULT);
$stmt=mysqli_prepare($con,"update users set password=? where email=?");
mysqli_stmt_bind_param($stmt,'ss',$enc,$email);
mysqli_stmt_execute($stmt);
$message = "Your temporary password is ".$newPassword;
mail($email, $subject, $message, "From: ICPM@reg-sys.com");
echo  "<script>alert('Your Password has been sent Successfully');</script>";
}
else
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
<?php include '../header.php'; ?>
<div class="main">
		<h1>ICPM 2023 Registration and Login System<br>FACIAL FILLER COURSE<br><span>Dr. MAZEN ARAFEH</span><br>NOSE FILLER<br>JAWLINE FILLER<br>RUSSIAN LIPS</h1>
	 <div class="sap_tabs">
			<div id="horizontalTab" style="display: block; width: 100%; margin: 0px;">
			  <ul class="resp-tabs-list">
			  	  <li class="resp-tab-item" aria-controls="tab_item-0" role="tab"><div class="top-img"><img src="images/top-note.png" alt=""/></div><span>Register</span>

				</li>
				  <li class="resp-tab-item" aria-controls="tab_item-1" role="tab"><div class="top-img"><img src="images/top-lock.png" alt=""/></div><span>Login</span></li>
				  <li class="resp-tab-item lost" aria-controls="tab_item-2" role="tab"><div class="top-img"><img src="images/top-key.png" alt=""/></div><span>Forgot Password</span></li>
				  <div class="clear"></div>
			  </ul>

			<div class="resp-tabs-container">
					<div class="tab-1 resp-tab-content" aria-labelledby="tab_item-0">
					<div class="facts">

						<div class="register">
							<form name="registration" method="post" action="" enctype="multipart/form-data">
								<p>Please Make sure you Enter correct name ,Email,Category and Mobile Number</p>
								<p>First Name </p>
								<input type="text" class="text" value=""  name="fname" required >
								<p>Last Name </p>
								<input type="text" class="text" value="" name="lname"  required >
								<p>Email Address (Can't register twice with same email)</p>
								<input type="text" class="text" value="" name="email"  >

								<p>Profession </p>
					              <select type="text" class="text" name="profession" value="" required>
					              <option></option>
					              <option value="Pharmacist">Pharmacist</option>
					              <option value="Physician">Physician</option>
					              <option value="Nurse">Nurse</option>
					              <option value="Manager">Manager</option>
					              <option value="Director">Director</option>
					              <option value="CEO">CEO</option>
					              <option value="Owner">Owner</option>
					              <option value="Other">Other</option>
					              </select>


					            <p>Category </p>
					              <select type="text" class="text" name=" category" value="" required>
					              <option selected value="Participant">CME PARTICIPANT</option>



					              </select>

					            <p>Organization </p>
								<input type="text" class="text" value="" name="organization"  >

								<p>Password - (Create your own password and keep it for future login) </p>
								<input type="password" value="" name="password" required>
								<p>Mobile/Phone No. </p>
								<input type="text" value="" name="contact"  required>

								<hr />
								<!--
								<p>
									<span>PLease Select Payment Type</span><br>* Plaese Make Payment<br>
* Copy Reference Number<br>
* Paste Reference Number into Requested Field after payment<br>
** STUDENT COLLEGE ID REQUIRED ON ENTERANCE **
-->
								<!--	<div >
<!-- PayPal code
<div id="smart-button-container">
      <div style="text-align: center;">
        <div id="paypal-button-container"></div>
      </div>
    </div>
  <script src="https://www.paypal.com/sdk/js?client-id=sb&enable-funding=venmo&currency=USD" data-sdk-integration-source="button-factory"></script>
  <script>
    function initPayPalButton() {
      paypal.Buttons({
        style: {
          shape: 'rect',
          color: 'blue',
          layout: 'vertical',
          label: 'paypal',

        },

        createOrder: function(data, actions) {
          return actions.order.create({
            purchase_units: [{"description":"FACIAL FILLER COURSE Dr. MAZEN ARAFEH","amount":{"currency_code":"USD","value":350}}]
          });
        },

        onApprove: function(data, actions) {
          return actions.order.capture().then(function(orderData) {

            // Full available details
            console.log('Capture result', orderData, JSON.stringify(orderData, null, 2));

            // Show a success message within this page, e.g.
            const element = document.getElementById('paypal-button-container');
            element.innerHTML = '';
            element.innerHTML = '<h3>Thank you for your payment!</h3>';

            // Or go to another URL:  actions.redirect('thank_you.html');

          });
        },

        onError: function(err) {
          console.log(err);
        }
      }).render('#paypal-button-container');
    }
    initPayPalButton();
  </script>
<!-- end PayPal code
    </div>
-->

                                  <p>Full Name as to be appear on Certificate</p>
								<input type="text" class="text" value="" name="paypalref"  required>
                                  </div>
							  <hr />
								<div class="sign-up">
									<input type="reset" value="Reset">
									<input type="submit" name="signup"  value="Sign Up" >
									<div class="clear"> </div>
								</div>
							</form>

						</div>
					</div>
				</div>
			 <div class="tab-2 resp-tab-content" aria-labelledby="tab_item-1">
					 	<div class="facts">
							 <div class="login">
							<div class="buttons">


							</div>
							<form name="login" action="" method="post">
								<input type="text" class="text" name="uemail" value="" placeholder="Enter your registered email"  ><a href="#" class=" icon email"></a>

								<input type="password" value="" name="password" placeholder="Enter valid password"><a href="#" class=" icon lock"></a>

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
							<div class="buttons">


							</div>
							<form name="login" action="" method="post">
								<input type="text" class="text" name="femail" value="" placeholder="Enter your registered email" required  ><a href="#" class=" icon email"></a>

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
