<?php
session_start();
require_once('dbconnection.php');
require_once('session_manager.php');
require_once('logging_helper.php');
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_set_charset($con, 'utf8mb4');

//Code for Registration
if($con && isset($_POST['signup']))
{
	$fname=$_POST['fname'];
	$lname=$_POST['lname'];
	$nationality = $_POST[ 'nationality' ];
	$email=$_POST['email'];
	$profession = $_POST[ 'profession' ];
  $organization = $_POST[ 'organization' ];
 	$category = $_POST[ 'category' ];
	$password=$_POST['password'];
	$contact=$_POST['contact'];

	if(empty($fname) || empty($lname) || empty($email) || empty($password) || empty($category)) {
		log_error($email, 'VALIDATION_FAIL', 'Required fields missing');
		echo "<script>alert('Please fill all required fields');</script>";
		exit();
	}

	$enc_password=password_hash($password, PASSWORD_DEFAULT);
	$stmt=mysqli_prepare($con,"select id from users where email=?");
	mysqli_stmt_bind_param($stmt,'s',$email);
	mysqli_stmt_execute($stmt);
	$res=mysqli_stmt_get_result($stmt);
	$row=mysqli_num_rows($res);
if($row>0)
{
	log_deduplication($email, 'DUPLICATE_EMAIL_ATTEMPT', "User tried to register with existing email: $email");
	echo "<script>alert('Email id already exist with another account. Please try with other email id');</script>";
} else{

	// Email will be sent after registration ID is available


//	echo "Registration Done please check your Email(some times on spam or promotios tab) ";

	$stmt=mysqli_prepare($con,"insert into users(fname,lname,nationality,email,profession,organization,category,password,contactno) values(?,?,?,?,?,?,?,?,?)");
	mysqli_stmt_bind_param($stmt,'sssssssss',$fname,$lname,$nationality,$email,$profession,$organization,$category,$enc_password,$contact);
	$msg=mysqli_stmt_execute($stmt);

if($msg)
{
//	echo "<script>alert('Registered successfully please check either email or spam if you didn't get email use forget password to get new email');</script>";
$stmt= mysqli_prepare($con,"SELECT * FROM users WHERE email=?");
mysqli_stmt_bind_param($stmt,'s',$email);
mysqli_stmt_execute($stmt);
$ret=mysqli_stmt_get_result($stmt);
$num=mysqli_fetch_array($ret);
if($num)
{
// Send HTML confirmation email including QR and ICPM logo
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
$headers = "From: ICPM@reg-sys.com\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=utf-8\r\n";
// @mail($to, $subject, $message, $headers);
send_mail_with_attachment_ex($to, $subject, $message, null, null, "ICPM@reg-sys.com", null, true);
$extra="welcome.php";
$_SESSION['login']=$_POST['email'];
$_SESSION['id']=$num['id'];
$_SESSION['name']=$num['fname'];
$_SESSION['slname']=$num['lname'];
$_SESSION['scategory']=$num['category'];
login_user_session($con, $num['id']);
$host=$_SERVER['HTTP_HOST'];
$uri=rtrim(dirname($_SERVER['PHP_SELF']),'/\\');
header("location:http://$host$uri/$extra");

exit();
} else {
	log_error($email, 'DB_INSERT_FAIL', "Insert user failed: " . mysqli_stmt_error($stmt));
	echo "<script>alert('Registration failed. Please try again.');</script>";
}
}
}
}

// Code for login
if($con && isset($_POST['login']))
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

if($con && isset($_POST['send']))
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
<?php if(!$con){ echo '<div style="background:#ffe8e8;color:#900;padding:12px;text-align:center;">Service temporarily unavailable. Please try again later.</div><script>document.addEventListener(\"DOMContentLoaded\",function(){var f=document.getElementsByTagName(\"form\");for(var i=0;i<f.length;i++){f[i].addEventListener(\"submit\",function(e){e.preventDefault();alert(\"Service temporarily unavailable. Please try again later.\");});}});</script>'; } ?>
<div class="main">
		<h1>ICPM Registration and Login System</h1>
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
                <select name="nationality" class="text" name="nationality" required>
	                <option value=""></option>
	                <option value="Afghanistan">Afghanistan</option>
	                <option value="Aland Islands">Aland Islands</option>
	                <option value="Albania">Albania</option>
	                <option value="Algeria">Algeria</option>
	                <option value="American Samoa">American Samoa</option>
	                <option value="Andorra">Andorra</option>
	                <option value="Angola">Angola</option>
	                <option value="Anguilla">Anguilla</option>
	                <option value="Antarctica">Antarctica</option>
	                <option value="Antigua and Barbuda">Antigua and Barbuda</option>
	                <option value="Argentina">Argentina</option>
	                <option value="Armenia">Armenia</option>
	                <option value="Aruba">Aruba</option>
	                <option value="Australia">Australia</option>
	                <option value="Austria">Austria</option>
	                <option value="Azerbaijan">Azerbaijan</option>
	                <option value="Bahamas">Bahamas</option>
	                <option value="Bahrain">Bahrain</option>
	                <option value="Bangladesh">Bangladesh</option>
	                <option value="Barbados">Barbados</option>
	                <option value="Belarus">Belarus</option>
	                <option value="Belgium">Belgium</option>
	                <option value="Belize">Belize</option>
	                <option value="Benin">Benin</option>
	                <option value="Bermuda">Bermuda</option>
	                <option value="Bhutan">Bhutan</option>
	                <option value="Bolivia">Bolivia</option>
	                <option value="Bonaire, Sint Eustatius and Saba">Bonaire, Sint Eustatius and Saba</option>
	                <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
	                <option value="Botswana">Botswana</option>
	                <option value="Bouvet Island">Bouvet Island</option>
	                <option value="Brazil">Brazil</option>
	                <option value="British Indian Ocean Territory">British Indian Ocean Territory</option>
	                <option value="Brunei Darussalam">Brunei Darussalam</option>
	                <option value="Bulgaria">Bulgaria</option>
	                <option value="Burkina Faso">Burkina Faso</option>
	                <option value="Burundi">Burundi</option>
	                <option value="Cambodia">Cambodia</option>
	                <option value="Cameroon">Cameroon</option>
	                <option value="Canada">Canada</option>
	                <option value="Cape Verde">Cape Verde</option>
	                <option value="Cayman Islands">Cayman Islands</option>
	                <option value="Central African Republic">Central African Republic</option>
	                <option value="Chad">Chad</option>
	                <option value="Chile">Chile</option>
	                <option value="China">China</option>
	                <option value="Christmas Island">Christmas Island</option>
	                <option value="Cocos (Keeling) Islands">Cocos (Keeling) Islands</option>
	                <option value="Colombia">Colombia</option>
	                <option value="Comoros">Comoros</option>
	                <option value="Congo">Congo</option>
	                <option value="Congo, Democratic Republic of the Congo">Congo, Democratic Republic of the Congo</option>
	                <option value="Cook Islands">Cook Islands</option>
	                <option value="Costa Rica">Costa Rica</option>
	                <option value="Cote D'Ivoire">Cote D'Ivoire</option>
	                <option value="Croatia">Croatia</option>
	                <option value="Cuba">Cuba</option>
	                <option value="Curacao">Curacao</option>
	                <option value="Cyprus">Cyprus</option>
	                <option value="Czech Republic">Czech Republic</option>
	                <option value="Denmark">Denmark</option>
	                <option value="Djibouti">Djibouti</option>
	                <option value="Dominica">Dominica</option>
	                <option value="Dominican Republic">Dominican Republic</option>
	                <option value="Ecuador">Ecuador</option>
	                <option value="Egypt">Egypt</option>
	                <option value="El Salvador">El Salvador</option>
	                <option value="Equatorial Guinea">Equatorial Guinea</option>
	                <option value="Eritrea">Eritrea</option>
	                <option value="Estonia">Estonia</option>
	                <option value="Ethiopia">Ethiopia</option>
	                <option value="Falkland Islands (Malvinas)">Falkland Islands (Malvinas)</option>
	                <option value="Faroe Islands">Faroe Islands</option>
	                <option value="Fiji">Fiji</option>
	                <option value="Finland">Finland</option>
	                <option value="France">France</option>
	                <option value="French Guiana">French Guiana</option>
	                <option value="French Polynesia">French Polynesia</option>
	                <option value="French Southern Territories">French Southern Territories</option>
	                <option value="Gabon">Gabon</option>
	                <option value="Gambia">Gambia</option>
	                <option value="Georgia">Georgia</option>
	                <option value="Germany">Germany</option>
	                <option value="Ghana">Ghana</option>
	                <option value="Gibraltar">Gibraltar</option>
	                <option value="Greece">Greece</option>
	                <option value="Greenland">Greenland</option>
	                <option value="Grenada">Grenada</option>
	                <option value="Guadeloupe">Guadeloupe</option>
	                <option value="Guam">Guam</option>
	                <option value="Guatemala">Guatemala</option>
	                <option value="Guernsey">Guernsey</option>
	                <option value="Guinea">Guinea</option>
	                <option value="Guinea-Bissau">Guinea-Bissau</option>
	                <option value="Guyana">Guyana</option>
	                <option value="Haiti">Haiti</option>
	                <option value="Heard Island and Mcdonald Islands">Heard Island and Mcdonald Islands</option>
	                <option value="Holy See (Vatican City State)">Holy See (Vatican City State)</option>
	                <option value="Honduras">Honduras</option>
	                <option value="Hong Kong">Hong Kong</option>
	                <option value="Hungary">Hungary</option>
	                <option value="Iceland">Iceland</option>
	                <option value="India">India</option>
	                <option value="Indonesia">Indonesia</option>
	                <option value="Iran, Islamic Republic of">Iran, Islamic Republic of</option>
	                <option value="Iraq">Iraq</option>
	                <option value="Ireland">Ireland</option>
	                <option value="Isle of Man">Isle of Man</option>
	                <option value="Israel">Israel</option>
	                <option value="Italy">Italy</option>
	                <option value="Jamaica">Jamaica</option>
	                <option value="Japan">Japan</option>
	                <option value="Jersey">Jersey</option>
	                <option value="Jordan">Jordan</option>
	                <option value="Kazakhstan">Kazakhstan</option>
	                <option value="Kenya">Kenya</option>
	                <option value="Kiribati">Kiribati</option>
	                <option value="Korea, Democratic People's Republic of">Korea, Democratic People's Republic of</option>
	                <option value="Korea, Republic of">Korea, Republic of</option>
	                <option value="Kosovo">Kosovo</option>
	                <option value="Kuwait">Kuwait</option>
	                <option value="Kyrgyzstan">Kyrgyzstan</option>
	                <option value="Lao People's Democratic Republic">Lao People's Democratic Republic</option>
	                <option value="Latvia">Latvia</option>
	                <option value="Lebanon">Lebanon</option>
	                <option value="Lesotho">Lesotho</option>
	                <option value="Liberia">Liberia</option>
	                <option value="Libyan Arab Jamahiriya">Libyan Arab Jamahiriya</option>
	                <option value="Liechtenstein">Liechtenstein</option>
	                <option value="Lithuania">Lithuania</option>
	                <option value="Luxembourg">Luxembourg</option>
	                <option value="Macao">Macao</option>
	                <option value="Macedonia, the Former Yugoslav Republic of">Macedonia, the Former Yugoslav Republic of</option>
	                <option value="Madagascar">Madagascar</option>
	                <option value="Malawi">Malawi</option>
	                <option value="Malaysia">Malaysia</option>
	                <option value="Maldives">Maldives</option>
	                <option value="Mali">Mali</option>
	                <option value="Malta">Malta</option>
	                <option value="Marshall Islands">Marshall Islands</option>
	                <option value="Martinique">Martinique</option>
	                <option value="Mauritania">Mauritania</option>
	                <option value="Mauritius">Mauritius</option>
	                <option value="Mayotte">Mayotte</option>
	                <option value="Mexico">Mexico</option>
	                <option value="Micronesia, Federated States of">Micronesia, Federated States of</option>
	                <option value="Moldova, Republic of">Moldova, Republic of</option>
	                <option value="Monaco">Monaco</option>
	                <option value="Mongolia">Mongolia</option>
	                <option value="Montenegro">Montenegro</option>
	                <option value="Montserrat">Montserrat</option>
	                <option value="Morocco">Morocco</option>
	                <option value="Mozambique">Mozambique</option>
	                <option value="Myanmar">Myanmar</option>
	                <option value="Namibia">Namibia</option>
	                <option value="Nauru">Nauru</option>
	                <option value="Nepal">Nepal</option>
	                <option value="Netherlands">Netherlands</option>
	                <option value="Netherlands Antilles">Netherlands Antilles</option>
	                <option value="New Caledonia">New Caledonia</option>
	                <option value="New Zealand">New Zealand</option>
	                <option value="Nicaragua">Nicaragua</option>
	                <option value="Niger">Niger</option>
	                <option value="Nigeria">Nigeria</option>
	                <option value="Niue">Niue</option>
	                <option value="Norfolk Island">Norfolk Island</option>
	                <option value="Northern Mariana Islands">Northern Mariana Islands</option>
	                <option value="Norway">Norway</option>
	                <option value="Oman">Oman</option>
	                <option value="Pakistan">Pakistan</option>
	                <option value="Palau">Palau</option>
	                <option value="Palestinian Territory, Occupied">Palestinian Territory, Occupied</option>
	                <option value="Panama">Panama</option>
	                <option value="Papua New Guinea">Papua New Guinea</option>
	                <option value="Paraguay">Paraguay</option>
	                <option value="Peru">Peru</option>
	                <option value="Philippines">Philippines</option>
	                <option value="Pitcairn">Pitcairn</option>
	                <option value="Poland">Poland</option>
	                <option value="Portugal">Portugal</option>
	                <option value="Puerto Rico">Puerto Rico</option>
	                <option value="Qatar">Qatar</option>
	                <option value="Reunion">Reunion</option>
	                <option value="Romania">Romania</option>
	                <option value="Russian Federation">Russian Federation</option>
	                <option value="Rwanda">Rwanda</option>
	                <option value="Saint Barthelemy">Saint Barthelemy</option>
	                <option value="Saint Helena">Saint Helena</option>
	                <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
	                <option value="Saint Lucia">Saint Lucia</option>
	                <option value="Saint Martin">Saint Martin</option>
	                <option value="Saint Pierre and Miquelon">Saint Pierre and Miquelon</option>
	                <option value="Saint Vincent and the Grenadines">Saint Vincent and the Grenadines</option>
	                <option value="Samoa">Samoa</option>
	                <option value="San Marino">San Marino</option>
	                <option value="Sao Tome and Principe">Sao Tome and Principe</option>
	                <option value="Saudi Arabia">Saudi Arabia</option>
	                <option value="Senegal">Senegal</option>
	                <option value="Serbia">Serbia</option>
	                <option value="Serbia and Montenegro">Serbia and Montenegro</option>
	                <option value="Seychelles">Seychelles</option>
	                <option value="Sierra Leone">Sierra Leone</option>
	                <option value="Singapore">Singapore</option>
	                <option value="Sint Maarten">Sint Maarten</option>
	                <option value="Slovakia">Slovakia</option>
	                <option value="Slovenia">Slovenia</option>
	                <option value="Solomon Islands">Solomon Islands</option>
	                <option value="Somalia">Somalia</option>
	                <option value="South Africa">South Africa</option>
	                <option value="South Georgia and the South Sandwich Islands">South Georgia and the South Sandwich Islands</option>
	                <option value="South Sudan">South Sudan</option>
	                <option value="Spain">Spain</option>
	                <option value="Sri Lanka">Sri Lanka</option>
	                <option value="Sudan">Sudan</option>
	                <option value="Suriname">Suriname</option>
	                <option value="Svalbard and Jan Mayen">Svalbard and Jan Mayen</option>
	                <option value="Swaziland">Swaziland</option>
	                <option value="Sweden">Sweden</option>
	                <option value="Switzerland">Switzerland</option>
	                <option value="Syrian Arab Republic">Syrian Arab Republic</option>
	                <option value="Taiwan, Province of China">Taiwan, Province of China</option>
	                <option value="Tajikistan">Tajikistan</option>
	                <option value="Tanzania, United Republic of">Tanzania, United Republic of</option>
	                <option value="Thailand">Thailand</option>
	                <option value="Timor-Leste">Timor-Leste</option>
	                <option value="Togo">Togo</option>
	                <option value="Tokelau">Tokelau</option>
	                <option value="Tonga">Tonga</option>
	                <option value="Trinidad and Tobago">Trinidad and Tobago</option>
	                <option value="Tunisia">Tunisia</option>
	                <option value="Turkey">Turkey</option>
	                <option value="Turkmenistan">Turkmenistan</option>
	                <option value="Turks and Caicos Islands">Turks and Caicos Islands</option>
	                <option value="Tuvalu">Tuvalu</option>
	                <option value="Uganda">Uganda</option>
	                <option value="Ukraine">Ukraine</option>
	                <option value="United Arab Emirates">United Arab Emirates</option>
	                <option value="United Kingdom">United Kingdom</option>
	                <option value="United States">United States</option>
	                <option value="United States Minor Outlying Islands">United States Minor Outlying Islands</option>
	                <option value="Uruguay">Uruguay</option>
	                <option value="Uzbekistan">Uzbekistan</option>
	                <option value="Vanuatu">Vanuatu</option>
	                <option value="Venezuela">Venezuela</option>
	                <option value="Viet Nam">Viet Nam</option>
	                <option value="Virgin Islands, British">Virgin Islands, British</option>
	                <option value="Virgin Islands, U.s.">Virgin Islands, U.s.</option>
	                <option value="Wallis and Futuna">Wallis and Futuna</option>
	                <option value="Western Sahara">Western Sahara</option>
	                <option value="Yemen">Yemen</option>
	                <option value="Zambia">Zambia</option>
	                <option value="Zimbabwe">Zimbabwe</option>
									<option value="Unknown">Unknown</option>
							</select>
								<p>Email Address - (Can't register twice with same email)</p>
								<input type="text" class="text" value="" name="email"  >
								<p>Profession </p>
								<select type="text" class="text" name="profession" value="" required>
									<option></option>
									<option value="Pharmacist">Journalist</option>
									<option value="Physician">Photographer</option>
									<option value="Nurse">blogger</option>
									<option value="Manager">Influencer</option>
									<option value="Director">Director</option>
									<option value="CEO">CEO</option>
									<option value="Owner">Owner</option>
									<option value="Other">Other</option>
								</select>

				<p>Category</p>
                <select type="text" class="text" name="category" value="" required>
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
