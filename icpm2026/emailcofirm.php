<?php session_start();
require_once('dbconnection2.php');
mysqli_set_charset($con, 'utf8mb4');
error_reporting(E_ALL ^ E_WARNING);
//Code for Registration
if(isset($_POST['signup']))
{
	$fname=$_POST['fname'];
	$lname=$_POST['lname'];
	$email=$_POST['email'];
	$password=$_POST['password'];

	if(empty($fname) || empty($lname) || empty($email) || empty($password)) {
		echo "<script>alert('Please fill all required fields');</script>";
	} else {

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

	// Code For Sending Email
	$to = "$email";
	$subject = "Welcome $fname-$lname";
	$message = "Welcome $fname-$lname
You have been verified Successfully
Please follow this link to complete Registration
https://reg-sys.com/icpm2026/regnew
		---------------
		Important note:
		---------------
Please complete your registeration through the website before coming to the Event.";

	$headers = "From: ICPM@reg-sys.com";
	mail( $to, $subject, $message, $headers );


//	echo "Registration Done please check your Email(some times on spam or promotios tab) ";

	$stmt=mysqli_prepare($con,"insert into users(fname,lname,nationality,email,profession,organization,category,password,contactno) values(?,?,?,?,?,?,?,?,?)");
	mysqli_stmt_bind_param($stmt,'sssssssss',$fname,$lname,$nationality,$email,$profession,$organization,$category,$enc_password,$contact);
	$msg=mysqli_stmt_execute($stmt);

if($msg)
{
echo "<script>alert('please open your email to confirm follow the instructions');</script>";
$stmt= mysqli_prepare($con,"SELECT * FROM users WHERE email=?");
mysqli_stmt_bind_param($stmt,'s',$email);
mysqli_stmt_execute($stmt);
$ret=mysqli_stmt_get_result($stmt);
$num=mysqli_fetch_array($ret);
if($num)
{
$extra="welcome.php";
$_SESSION['login']=$_POST['email'];
$_SESSION['id']=$num['id'];
$_SESSION['name']=$num['fname'];
$_SESSION['slname']=$num['lname'];
$_SESSION['scategory']=$num['category'];
$host=$_SERVER['HTTP_HOST'];
$uri=rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
header("location:http://$host$uri/$extra");

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
$stmt= mysqli_prepare($con,"SELECT * FROM users WHERE email=?");
mysqli_stmt_bind_param($stmt,'s',$useremail);
mysqli_stmt_execute($stmt);
$ret=mysqli_stmt_get_result($stmt);
$num=mysqli_fetch_array($ret);
if($num && password_verify($password,$num['password']))
{
$sid=session_id();
session_regenerate_id(true);
$extra="welcome.php";
$_SESSION['login']=$_POST['uemail'];
$_SESSION['id']=$num['id'];
$_SESSION['name']=$num['fname'];
$_SESSION['slname']=$num['lname'];
$_SESSION['scategory']=$num['category'];
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
</head>
<body>
<?php include 'header.php'; ?>
<div class="main">
		<h1>Registration and Login System</h1>
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
								<p>First Name </p>
								<input type="text" class="text" value=""  name="fname" required >
								<p>Last Name </p>
								<input type="text" class="text" value="" name="lname"  required >
								<p>Email Address - (Can't register twice with same email)</p>
								<input type="text" class="text" value="" name="email"  >
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
