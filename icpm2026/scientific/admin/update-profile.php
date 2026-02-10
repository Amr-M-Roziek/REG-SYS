<?php
require_once 'session_setup.php';
include'dbconnection.php';
require_once 'permission_helper.php';
include '../phone_codes_helper.php';
mysqli_set_charset($con, 'utf8mb4');
//Checking session is valid or not
if (!isset($_SESSION['id']) || strlen($_SESSION['id'])==0) {
  header('location:logout.php');
  exit();
}
if (!has_permission($con, 'edit_users')) {
    die("Permission denied");
}

// for updating user info
if(isset($_POST['Submit']))
{
  $id=$_POST['id'];
  $fname=$_POST['fname'];
  $lname=isset($_POST['lname']) ? $_POST['lname'] : '';
  $nationality=isset($_POST['nationality']) ? $_POST['nationality'] : '';
  $coauth1name=isset($_POST['coauth1name']) ? $_POST['coauth1name'] : '';
  $coauth2name=isset($_POST['coauth2name']) ? $_POST['coauth2name'] : '';
  $coauth3name=isset($_POST['coauth3name']) ? $_POST['coauth3name'] : '';
  $coauth4name=isset($_POST['coauth4name']) ? $_POST['coauth4name'] : '';
  $coauth5name=isset($_POST['coauth5name']) ? $_POST['coauth5name'] : '';
  $coauth1nationality=isset($_POST['coauth1nationality']) ? $_POST['coauth1nationality'] : '';
  $coauth2nationality=isset($_POST['coauth2nationality']) ? $_POST['coauth2nationality'] : '';
  $coauth3nationality=isset($_POST['coauth3nationality']) ? $_POST['coauth3nationality'] : '';
  $coauth4nationality=isset($_POST['coauth4nationality']) ? $_POST['coauth4nationality'] : '';
  $coauth5nationality=isset($_POST['coauth5nationality']) ? $_POST['coauth5nationality'] : '';
  $contact=$_POST['contact'];
  $email=$_POST['email'];
  $supervisor_choice = isset($_POST['supervisor_choice']) ? $_POST['supervisor_choice'] : 'no';
  $supervisor_name = isset($_POST['supervisor_name']) ? $_POST['supervisor_name'] : '';
  $supervisor_nationality = isset($_POST['supervisor_nationality']) ? $_POST['supervisor_nationality'] : '';
  $supervisor_contact = isset($_POST['supervisor_contact']) ? $_POST['supervisor_contact'] : '';
  $supervisor_email = isset($_POST['supervisor_email']) ? trim($_POST['supervisor_email']) : '';
  $coauth1email=isset($_POST['coauth1email']) ? trim($_POST['coauth1email']) : '';
  $coauth2email=isset($_POST['coauth2email']) ? trim($_POST['coauth2email']) : '';
  $coauth3email=isset($_POST['coauth3email']) ? trim($_POST['coauth3email']) : '';
  $coauth4email=isset($_POST['coauth4email']) ? trim($_POST['coauth4email']) : '';
  $coauth5email=isset($_POST['coauth5email']) ? trim($_POST['coauth5email']) : '';
  $coauth1mobile=isset($_POST['coauth1mobile']) ? trim($_POST['coauth1mobile']) : '';
  $coauth2mobile=isset($_POST['coauth2mobile']) ? trim($_POST['coauth2mobile']) : '';
  $coauth3mobile=isset($_POST['coauth3mobile']) ? trim($_POST['coauth3mobile']) : '';
  $coauth4mobile=isset($_POST['coauth4mobile']) ? trim($_POST['coauth4mobile']) : '';
  $coauth5mobile=isset($_POST['coauth5mobile']) ? trim($_POST['coauth5mobile']) : '';
  $profession=$_POST['profession'];
  $category=$_POST['category'];
  $organization=$_POST['organization'];
  $companyref=$_POST['companyref'];
  $paypalref=$_POST['paypalref'];
  $password=isset($_POST['password']) ? $_POST['password'] : '';
  $uid=intval($_GET['uid']);
  $enc = $password;
  if ($enc !== '' && strpos($enc, '$2y$') !== 0) {
    $enc = password_hash($enc, PASSWORD_DEFAULT);
  }
  $errors = [];
  $emailsToCheck = [];
  
  // Co-author Validation Logic
  for ($i = 1; $i <= 5; $i++) {
      $nameVar = "coauth{$i}name";
      $emailVar = "coauth{$i}email";
      $nameVal = $$nameVar;
      $emailVal = $$emailVar;
      
      // If name is present, email is required
      if (!empty($nameVal) && empty($emailVal)) {
          $errors[] = "Team Member $i Email is required";
          error_log("Validation Failure: Team Member $i ($nameVal) missing email for User ID $id");
      }
  }

  foreach ([
    ['label' => 'Team Leader', 'value' => $email],
    ['label' => 'Supervisor', 'value' => $supervisor_email],
    ['label' => 'Team Member 1', 'value' => $coauth1email],
    ['label' => 'Team Member 2', 'value' => $coauth2email],
    ['label' => 'Team Member 3', 'value' => $coauth3email],
    ['label' => 'Team Member 4', 'value' => $coauth4email],
    ['label' => 'Team Member 5', 'value' => $coauth5email],
  ] as $item) {
    $val = $item['value'];
    if ($val !== '' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
      $errors[] = $item['label']." email is invalid";
    }
    if ($val !== '') {
      $emailsToCheck[] = strtolower($val);
    }
  }

  // Abstract File Validation
  $abstract_updated = false;
  if (isset($_FILES['abstract_file']) && $_FILES['abstract_file']['error'] == UPLOAD_ERR_OK) {
      $fileTmpPath = $_FILES['abstract_file']['tmp_name'];
      $fileName = $_FILES['abstract_file']['name'];
      $fileSize = $_FILES['abstract_file']['size'];
      $fileType = $_FILES['abstract_file']['type'];
      $fileNameCmps = explode(".", $fileName);
      $fileExtension = strtolower(end($fileNameCmps));

      $allowedfileExtensions = array('pdf', 'doc', 'docx');
      if (!in_array($fileExtension, $allowedfileExtensions)) {
          $errors[] = "Upload failed. Allowed file types: " . implode(', ', $allowedfileExtensions);
      }
      
      if ($fileSize > 5 * 1024 * 1024) { // 5MB
          $errors[] = "File is too large. Max size is 5MB.";
      }

      if (empty($errors)) {
          $newFileContent = file_get_contents($fileTmpPath);
          $abstract_updated = true;
      }
  } elseif (isset($_FILES['abstract_file']) && $_FILES['abstract_file']['error'] != UPLOAD_ERR_NO_FILE) {
      $errors[] = "File upload error code: " . $_FILES['abstract_file']['error'];
  }

  if (count($errors) > 0) {
    $_SESSION['msg'] = implode('. ', $errors);
  } else {
    $stmt=mysqli_prepare($con,"update users set id=?, fname=?, lname=?, nationality=?, profession=?, category=?, email=?, organization=?, password=?, contactno=?, companyref=?, paypalref=?, coauth1name=?, coauth1nationality=?, coauth2name=?, coauth2nationality=?, coauth3name=?, coauth3nationality=?, coauth4name=?, coauth4nationality=?, coauth5name=?, coauth5nationality=?, coauth1email=?, coauth2email=?, coauth3email=?, coauth4email=?, coauth5email=?, coauth1mobile=?, coauth2mobile=?, coauth3mobile=?, coauth4mobile=?, coauth5mobile=?, supervisor_choice=?, supervisor_name=?, supervisor_nationality=?, supervisor_contact=?, supervisor_email=? where id=?");
    mysqli_stmt_bind_param($stmt,'issssssssssssssssssssssssssssssssssssi',$id,$fname,$lname,$nationality,$profession,$category,$email,$organization,$enc,$contact,$companyref,$paypalref,$coauth1name,$coauth1nationality,$coauth2name,$coauth2nationality,$coauth3name,$coauth3nationality,$coauth4name,$coauth4nationality,$coauth5name,$coauth5nationality,$coauth1email,$coauth2email,$coauth3email,$coauth4email,$coauth5email,$coauth1mobile,$coauth2mobile,$coauth3mobile,$coauth4mobile,$coauth5mobile,$supervisor_choice,$supervisor_name,$supervisor_nationality,$supervisor_contact,$supervisor_email,$uid);
    mysqli_stmt_execute($stmt);
    
    // Update Abstract if provided
    if ($abstract_updated && isset($newFileContent)) {
        // Use a separate query for BLOB to keep things clean
        $null = NULL; // For bind_param if needed, but we send string
        $stmt_abs = mysqli_prepare($con, "UPDATE users SET abstract_filename=?, abstract_mime=?, abstract_blob=? WHERE id=?");
        // ssb(blob)i
        // But mysqli_stmt_bind_param 'b' expects send_long_data. 
        // For < 16MB (max_allowed_packet usually), 's' works fine for binary data in many drivers, 
        // but 'b' with send_long_data is safer for large blobs.
        // Let's try standard binding first. If content is large, we might need send_long_data.
        // For 5MB, send_long_data is recommended.
        
        mysqli_stmt_bind_param($stmt_abs, 'ssbi', $fileName, $fileType, $null, $uid);
        mysqli_stmt_send_long_data($stmt_abs, 2, $newFileContent);
        mysqli_stmt_execute($stmt_abs);
        mysqli_stmt_close($stmt_abs);
    }

    $_SESSION['msg']="Profile Updated successfully";
  }
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

    <title>Admin | Update Profile</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
  </head>

  <body>

  <section id="container" >
      <header class="header black-bg">
              <div class="sidebar-toggle-box">
                  <div class="fa fa-bars tooltips" data-placement="right" data-original-title="Toggle Navigation"></div>
              </div>
            <a href="#" class="logo"><b>Admin Dashboard</b></a>
            <div class="nav notify-row" id="top_menu">
            </div>
            <div class="top-menu">
              <ul class="nav pull-right top-menu">
                    <li><a class="logout" href="logout.php">Logout</a></li>
              </ul>
            </div>
        </header>
      <aside>
          <div id="sidebar"  class="nav-collapse ">
              <ul class="sidebar-menu" id="nav-accordion">

                  <p class="centered"><a href="#"><img src="assets/img/ui-sam.jpg" class="img-circle" width="60"></a></p>
                  <h5 class="centered"><?php echo $_SESSION['login'];?></h5>

                  <li class="mt">
                      <a href="manage-users.php">
                          <i class="fa fa-users"></i>
                          <span>Manage Users</span>
                      </a>
                  </li>
                  <?php if(has_permission($con, 'manage_admins')): ?>
                  <li class="sub-menu">
                      <a href="manage-admins.php">
                          <i class="fa fa-user-md"></i>
                          <span>Manage Admins</span>
                      </a>
                  </li>
                  <?php endif; ?>
                  <?php if(has_permission($con, 'manage_roles')): ?>
                  <li class="sub-menu">
                      <a href="manage-roles.php">
                          <i class="fa fa-lock"></i>
                          <span>Manage Roles</span>
                      </a>
                  </li>
                  <?php endif; ?>

              </ul>
          </div>
      </aside>

      <?php
      $uid=intval($_GET['uid']);
      $query=mysqli_query($con,"select * from users where id='$uid'");
      $rw=mysqli_fetch_array($query);
      ?>

      <section id="main-content">
          <section class="wrapper">
            <h3><i class="fa fa-angle-right"></i> <?php echo htmlspecialchars($rw['fname']);?>'s Information</h3>

        <div class="row">
                  <div class="col-md-12">
                      <div class="content-panel">
                      <p align="center" style="color:#F00;"><?php if(isset($_SESSION['msg']) && $_SESSION['msg']!==''){ echo htmlspecialchars($_SESSION['msg']); $_SESSION['msg']=""; } ?></p>
                           <form class="form-horizontal style-form" id="updateProfileForm" name="form1" method="post" action="" enctype="multipart/form-data">
                           
                           <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Ref Num </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="id" value="<?php echo htmlspecialchars($rw['id']);?>" readonly >
                              </div>
                          </div>

                           <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">First Name </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="fname" value="<?php echo htmlspecialchars($rw['fname']);?>" >
                              </div>
                          </div>

                              <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Last Name</label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="lname" value="<?php echo isset($rw['lname']) ? htmlspecialchars($rw['lname']) : '';?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Nationality </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="nationality" value="<?php echo htmlspecialchars(isset($rw['nationality']) ? $rw['nationality'] : '');?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Email </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="email" value="<?php echo htmlspecialchars($rw['email']);?>" >
                              </div>
                          </div>

                          <?php for($i=1; $i<=5; $i++): 
                            $name = isset($rw["coauth{$i}name"]) ? $rw["coauth{$i}name"] : '';
                            $email = isset($rw["coauth{$i}email"]) ? $rw["coauth{$i}email"] : '';
                            $nat = isset($rw["coauth{$i}nationality"]) ? $rw["coauth{$i}nationality"] : '';
                            $mob = isset($rw["coauth{$i}mobile"]) ? $rw["coauth{$i}mobile"] : '';
                          ?>
                          <div class="co-author-wrapper">
                              <h4 style="padding-left:40px;">Team Member <?php echo $i; ?></h4>
                              <div class="form-group">
                                  <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Name </label>
                                  <div class="col-sm-10">
                                      <input type="text" class="form-control coauth-name" name="coauth<?php echo $i; ?>name" value="<?php echo htmlspecialchars($name);?>">
                                  </div>
                              </div>
                              <div class="form-group">
                                  <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Nationality </label>
                                  <div class="col-sm-10">
                                      <input type="text" class="form-control" name="coauth<?php echo $i; ?>nationality" value="<?php echo htmlspecialchars($nat);?>">
                                  </div>
                              </div>
                              <div class="form-group">
                                  <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Email </label>
                                  <div class="col-sm-10">
                                      <input type="text" class="form-control coauth-email" name="coauth<?php echo $i; ?>email" value="<?php echo htmlspecialchars($email);?>">
                                      <span class="error-msg" style="color:red; display:none;"></span>
                                  </div>
                              </div>
                              <div class="form-group">
                                  <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Mobile </label>
                                  <div class="col-sm-10">
                                      <input type="text" class="form-control" name="coauth<?php echo $i; ?>mobile" value="<?php echo htmlspecialchars($mob);?>">
                                  </div>
                              </div>
                          </div>
                          <?php endfor; ?>

                          <!-- Supervisor -->
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Supervisor Choice</label>
                              <div class="col-sm-10">
                                  <select name="supervisor_choice" class="form-control">
                                      <option value="no" <?php if(isset($rw['supervisor_choice']) && $rw['supervisor_choice']=='no') echo 'selected'; ?>>No</option>
                                      <option value="yes" <?php if(isset($rw['supervisor_choice']) && $rw['supervisor_choice']=='yes') echo 'selected'; ?>>Yes</option>
                                  </select>
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Supervisor Name</label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="supervisor_name" value="<?php echo htmlspecialchars(isset($rw['supervisor_name']) ? $rw['supervisor_name'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Supervisor Nationality</label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="supervisor_nationality" value="<?php echo htmlspecialchars(isset($rw['supervisor_nationality']) ? $rw['supervisor_nationality'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Supervisor Email</label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="supervisor_email" value="<?php echo htmlspecialchars(isset($rw['supervisor_email']) ? $rw['supervisor_email'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Supervisor Contact</label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="supervisor_contact" value="<?php echo htmlspecialchars(isset($rw['supervisor_contact']) ? $rw['supervisor_contact'] : '');?>">
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Profession. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="profession" value="<?php echo htmlspecialchars($rw['profession']);?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Category. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="category" value="<?php echo htmlspecialchars($rw['category']);?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Organization. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="organization" value="<?php echo htmlspecialchars($rw['organization']);?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Contact no. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($rw['contactno']);?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Company Ref. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="companyref" value="<?php echo htmlspecialchars($rw['companyref']);?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">PayPal Ref. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="paypalref" value="<?php echo htmlspecialchars($rw['paypalref']);?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Registration Date </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="regdate" value="<?php echo htmlspecialchars($rw['posting_date']);?>" readonly >
                              </div>
                          </div>

                          <!-- Abstract Management Section -->
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Abstract Management</label>
                              <div class="col-sm-10">
                                  <?php if (!empty($rw['abstract_filename'])): ?>
                                      <div style="margin-bottom: 10px;">
                                          <strong>Current File:</strong> 
                                          <a href="download-abstract.php?id=<?php echo $uid; ?>" target="_blank">
                                              <?php echo htmlspecialchars($rw['abstract_filename']); ?>
                                          </a>
                                          <span class="label label-success">Uploaded</span>
                                      </div>
                                      <p class="help-block">To update, upload a new file below (replaces existing).</p>
                                  <?php else: ?>
                                      <div style="margin-bottom: 10px;">
                                          <span class="label label-warning">No abstract uploaded</span>
                                      </div>
                                  <?php endif; ?>
                                  
                                  <input type="file" name="abstract_file" class="form-control" accept=".pdf,.doc,.docx">
                                  <p class="help-block">Allowed formats: PDF, DOC, DOCX. Max size: 5MB.</p>
                              </div>
                          </div>


                            <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Password </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="password" value="<?php echo htmlspecialchars(isset($rw['password']) ? $rw['password'] : '');?>" readonly >
                              </div>
                          </div>

                          <div style="margin-left:100px;">
                          <input type="submit" name="Submit" value="Update" class="btn btn-theme"></div>
                          </form>
                      </div>
                  </div>
              </div>
    </section>
      </section>
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

      // Client-side Validation on Submit
      $(document).ready(function() {
          $('#updateProfileForm').submit(function(e) {
              var isValid = true;
              
              $('.co-author-wrapper:visible').each(function() {
                  var nameInput = $(this).find('.coauth-name');
                  var emailInput = $(this).find('.coauth-email');
                  var errorMsg = $(this).find('.error-msg');
                  
                  var nameVal = $.trim(nameInput.val());
                  var emailVal = $.trim(emailInput.val());

                  // Reset error state
                  errorMsg.hide();
                  emailInput.css('border', '');
                  emailInput.removeAttr('aria-invalid');

                  if (nameVal !== '') {
                      if (emailVal === '') {
                          isValid = false;
                          errorMsg.html('<i class="fa fa-exclamation-circle"></i> Email is required when name is provided.');
                          errorMsg.show();
                          emailInput.css('border', '1px solid #a94442');
                          emailInput.attr('aria-invalid', 'true');
                      } else if (!isValidEmail(emailVal)) {
                          isValid = false;
                          errorMsg.html('<i class="fa fa-exclamation-circle"></i> Please enter a valid email address.');
                          errorMsg.show();
                          emailInput.css('border', '1px solid #a94442');
                          emailInput.attr('aria-invalid', 'true');
                      }
                  }
              });

              if (!isValid) {
                  e.preventDefault();
                  // Scroll to first error
                  var firstError = $('.error-msg:visible').first();
                  if (firstError.length > 0) {
                      $('html, body').animate({
                          scrollTop: firstError.closest('.co-author-wrapper').offset().top - 100
                      }, 500);
                  }
                  return false;
              }
          });

          function isValidEmail(email) {
              var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
              return regex.test(email);
          }
      });
  </script>

  </body>
</html>
