<?php
session_start();
require_once('dbconnection.php');
require_once('session_manager.php');

// Helper functions for email
function log_submission($email, $status, $message, $filename, $refId) {
    $line = date('c') . " | $status | $email | $refId | $filename | $message\n";
    @file_put_contents(__DIR__ . '/submissions.log', $line, FILE_APPEND);
}

function send_mail_with_attachment_ex($to, $subject, $bodyText, $filePath, $fileName, $from, $replyTo=null, $isHtml=false) {
    $transport = 'mail';
    $error = '';
    $ok = false;
    $ts = date('c');
    $linePrefix = $ts . " | EMAIL_ATTEMPT | $to | " . ($fileName ?: '') . " | ";
    @file_put_contents(__DIR__ . '/submissions.log', $linePrefix . "transport=$transport; subject=" . $subject . "\n", FILE_APPEND);
    
    // SMTP Configuration
    $host = getenv('SMTP_HOST');
    $user = getenv('SMTP_USER');
    $pass = getenv('SMTP_PASS');
    $port = getenv('SMTP_PORT');
    $secure = getenv('SMTP_SECURE') ?: 'tls';
    $fromAddr = getenv('SMTP_FROM') ?: 'ICPM@reg-sys.com';
    $fromName = getenv('SMTP_FROM_NAME') ?: 'ICPM';
    
    $useSmtp = ($host && $user && $pass);
    
    if ($useSmtp && file_exists(__DIR__ . '/smtp/PHPMailerAutoload.php')) {
        require_once(__DIR__ . '/smtp/PHPMailerAutoload.php');
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->SMTPAuth = true;
        if ($secure) { $mail->SMTPSecure = $secure; }
        $mail->Host = $host;
        $mail->Port = $port ? intval($port) : 587;
        $mail->IsHTML($isHtml);
        $mail->CharSet = 'UTF-8';
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->SetFrom($fromAddr, $fromName);
        $mail->Subject = $subject;
        $mail->Body = $bodyText;
        if (!$isHtml) {
             // If text mode, ensure newlines are handled if needed, but usually Body is enough
        } else {
             $mail->AltBody = strip_tags($bodyText);
        }
        
        if ($replyTo) { $mail->addReplyTo($replyTo); }
        $mail->AddAddress($to);
        if ($filePath && is_file($filePath)) {
            $mail->AddAttachment($filePath, $fileName ?: basename($filePath));
        }
        $transport = 'smtp';
        try {
            $ok = $mail->Send();
        } catch (Exception $e) {
            $ok = false;
            $error = $e->getMessage();
        }
        if (!$ok && empty($error)) { $error = $mail->ErrorInfo; }
    } else {
        // Fallback to mail()
        $headers = "From: " . ($from ?: 'ICPM@reg-sys.com') . "\r\n";
        if ($replyTo) $headers .= "Reply-To: $replyTo\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $boundary = md5(uniqid(time(), true));
        
        if ($isHtml || ($filePath && is_file($filePath))) {
             $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
             $body = "--$boundary\r\n";
             $body .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=\"utf-8\"\r\n";
             $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
             $body .= $bodyText . "\r\n";
             
             if ($filePath && is_file($filePath)) {
                $data = chunk_split(base64_encode(file_get_contents($filePath)));
                $body .= "--$boundary\r\n";
                $body .= "Content-Type: application/octet-stream; name=\"$fileName\"\r\n";
                $body .= "Content-Transfer-Encoding: base64\r\n";
                $body .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n";
                $body .= $data . "\r\n";
             }
             $body .= "--$boundary--";
        } else {
             $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
             $body = $bodyText;
        }
        
        $ok = mail($to, $subject, $body, $headers);
        if (!$ok) { $error = 'mail() returned false'; }
    }
    
    $status = $ok ? 'EMAIL_SENT' : 'EMAIL_FAIL';
    $msg = $ok ? "transport=$transport" : ("transport=$transport; error=" . $error);
    @file_put_contents(__DIR__ . '/submissions.log', $ts . " | $status | $to | " . ($fileName ?: '') . " | " . $msg . "\n", FILE_APPEND);
    return array($ok, $transport, $error);
}

// stop showing waRNINGs
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
mysqli_set_charset($con, 'utf8mb4');

function log_deduplication($email, $reason, $details = '') {
    $line = date('c') . " | DEDUPLICATE | $email | $reason | $details\n";
    @file_put_contents(__DIR__ . '/deduplication.log', $line, FILE_APPEND);
}

//Code for Registration
if(isset($_POST['signup']))
{
	$fname=trim($_POST['fname']);
	$lname=trim($_POST['lname']);
	$nationality = $_POST[ 'nationality' ];
	$email=trim($_POST['email']);
	$profession = $_POST[ 'profession' ];
  $organization = $_POST[ 'organization' ];
 	$category = $_POST[ 'category' ];
	$password=$_POST['password'];
	$contact=trim($_POST['contact']);
	
	if(empty($fname) || empty($lname) || empty($email) || empty($password) || empty($category)) {
		echo "<script>alert('Please fill all required fields');</script>";
		exit();
	}

	$enc_password=password_hash($password, PASSWORD_DEFAULT);
	
	// 1. Check for Duplicate Email
	$stmt=mysqli_prepare($con,"select id from users where email=?");
	mysqli_stmt_bind_param($stmt,'s',$email);
	mysqli_stmt_execute($stmt);
	$res=mysqli_stmt_get_result($stmt);
	$row=mysqli_num_rows($res);
if($row>0)
{
	log_deduplication($email, "DUPLICATE_EMAIL", "Attempted registration with existing email.");
	echo "<script>alert('Email id already exist with another account. Please try with other email id');</script>";
} else{

	// 2. Check for Partial Duplicate (Same Name + Contact)
	$stmt2 = mysqli_prepare($con, "SELECT id, email FROM users WHERE fname=? AND lname=? AND contactno=?");
	mysqli_stmt_bind_param($stmt2, 'sss', $fname, $lname, $contact);
	mysqli_stmt_execute($stmt2);
	$res2 = mysqli_stmt_get_result($stmt2);
	if (mysqli_num_rows($res2) > 0) {
		$dupRow = mysqli_fetch_assoc($res2);
		log_deduplication($email, "PARTIAL_DUPLICATE_BLOCKED", "Match on Name+Contact with existing user ID " . $dupRow['id']);
		echo "<script>alert('Registration Failed: A user with the same First Name, Last Name, and Contact Number already exists.');</script>";
	} else {

	// Code For Sending Email
	$to = "$email";
	$subject = "Welcome $fname-$lname";
	$message = "Welcome $fname-$lname
									You have been Registered Successfully
									Please go to login page
									To login too your account
									---------------
									Important note:
									---------------
									Please register through the website before coming to the Event.
									Please save your registration reference number you can get it by login in at: https://icpm.ae/regnew/
															then select Login
															Use your registerd Email : ( $email )
															Your password : ( $password )
															Please use your credintal to sign in and get your QR ";

	// Email will be sent after registration ID is available

//	echo "Registration Done please check your Email(some times on spam or promotios tab) ";
	$stmt=mysqli_prepare($con,"insert into users(fname,lname,nationality,email,profession,organization,category,password,contactno) values(?,?,?,?,?,?,?,?,?)");
	mysqli_stmt_bind_param($stmt,'sssssssss',$fname,$lname,$nationality,$email,$profession,$organization,$category,$enc_password,$contact);
	$msg=mysqli_stmt_execute($stmt);
if($msg)
{
log_deduplication($email, "SUCCESS", "New user registered.");
//	echo "<script>alert('Registered successfully please check either email or spam if you didn't get email use forget password to get new email');</script>";
$stmt= mysqli_prepare($con,"SELECT * FROM users WHERE email=?");
mysqli_stmt_bind_param($stmt,'s',$email);
mysqli_stmt_execute($stmt);
$ret=mysqli_stmt_get_result($stmt);
$num=mysqli_fetch_array($ret);
if($num)
{
	$to = $email;
	$footerImg = "https://reg-sys.com/icpm2026/images/icpm-logo.png";
	$subject = "ICPM Registration Confirmation - Ref #".$num['id'];
	$message = '<div style="font-family:Arial,Helvetica,sans-serif;color:#111;line-height:1.6">
<p>Hello ' . htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($lname, ENT_QUOTES, 'UTF-8') . ',</p>
<p>You have been registered successfully.</p>
<p>You can view the event agenda here: <a href="https://icpm.ae/wp/about-us/agenda/" target="_blank">https://icpm.ae/wp/about-us/agenda/</a></p>
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
	send_mail_with_attachment_ex($to, $subject, $message, null, null, "ICPM@reg-sys.com", null, true);
$extra="welcome.php";
$_SESSION['login']=$_POST['email'];
$_SESSION['id']=$num['id'];
$_SESSION['name']=$num['fname'];
$_SESSION['slname']=$num['lname'];
$_SESSION['scategory']=$num['category'];

// Register session in DB to prevent "Session expired" error
login_user_session($con, $num['id']);

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

// Register session in DB
login_user_session($con, $num['id']);

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
send_mail_with_attachment_ex($email, $subject, $message, null, null, "ICPM <ICPM@reg-sys.com>");
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

            // Prevent double submission
            $('form[name="registration"]').on('submit', function() {
                var $btn = $(this).find('input[type="submit"]');
                if ($btn.data('submitted') === true) {
                    return false;
                }
                // Basic client-side validation check (if required fields are empty, browser handles it, but we double check)
                if (!this.checkValidity()) {
                    return false; 
                }
                
                $btn.data('submitted', true);
                $btn.css('opacity', '0.5').val('Processing...');
                return true;
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
								<p>Nationality </p>
                <select name="nationality" class="text" required>
                    <?php
                    // Use countries_helper.php for the country list
                    $helperPath = __DIR__ . '/countries_helper.php';
                    if (file_exists($helperPath)) {
                        require_once($helperPath);
                        // If there is a POST value for nationality, use it to keep selection selected
                        $selectedCountry = isset($_POST['nationality']) ? $_POST['nationality'] : '';
                        if (function_exists('getCountryOptions')) {
                            echo getCountryOptions($selectedCountry);
                        } else {
                            echo '<option value="">Error: Helper function not found</option>';
                        }
                    } else {
                         echo '<option value="">Error: Country list not available</option>';
                    }
                    ?>
                </select>
								<p>Email Address - (Can't register twice with same email)</p>
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

				<p>Category</p>
                <select type="text" class="text" name=" category" value="" required>
                <option value="Visitor" selected>Visitor</option>
                </select>

                <p>Organization </p>
                <input type="text" class="text" value="" name="organization" required>
									<p><span style="color: black">Password ( Write your owan password example: 123)</span></p>
								<input type="password" value="" name="password" required>
										<p>Mobile/Phone/Contact No. </p>
								<input type="text" value="" name="contact"  required>

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
