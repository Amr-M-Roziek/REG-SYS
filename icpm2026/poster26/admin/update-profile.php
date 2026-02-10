<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'session_setup.php';
include'dbconnection.php';
require_once 'permission_helper.php';
require_once __DIR__ . '/../../logging_helper.php';

mysqli_set_charset($con, 'utf8mb4');

// Auth Check
if (empty($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Permission Check (Edit Users)
if (!has_permission($con, 'edit_users')) {
    die("You do not have permission to edit users.");
}

// for updating user info
if(isset($_POST['Submit']))
{
  $id=$_POST['id'];
  $fname=$_POST['fname'];
  $postertitle=isset($_POST['postertitle']) ? $_POST['postertitle'] : '';
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
  $profession=$_POST['profession'];
  $categoryInput=isset($_POST['category']) ? $_POST['category'] : '';
  $category = is_array($categoryInput) ? implode(', ', $categoryInput) : $categoryInput;
  $organization=$_POST['organization'];
  $companyref=$_POST['companyref'];
  $paypalref=$_POST['paypalref'];
  $password=isset($_POST['password']) ? $_POST['password'] : '';
  $uid=intval($_GET['uid']);
  $enc = $password;
  if ($enc !== '' && strpos($enc, '$2y$') !== 0) {
    $enc = password_hash($enc, PASSWORD_DEFAULT);
  }

  // Fetch current co-authors before update to detect new ones
  $current_coauth_emails = [];
  $stmt_curr = mysqli_prepare($con, "SELECT coauth1email, coauth2email, coauth3email, coauth4email, coauth5email FROM users WHERE id=?");
  mysqli_stmt_bind_param($stmt_curr, 'i', $id);
  mysqli_stmt_execute($stmt_curr);
  $res_curr = mysqli_stmt_get_result($stmt_curr);
  if ($row_curr = mysqli_fetch_assoc($res_curr)) {
      foreach ($row_curr as $ce) {
          if (!empty($ce)) {
              $current_coauth_emails[] = strtolower(trim($ce));
          }
      }
  }
  mysqli_stmt_close($stmt_curr);

  $errors = [];
  $emailsToCheck = [];
  $overrides = [];
  $allowedMissingEmailIndices = [1, 2, 3, 4, 5];
  
  // Co-author Validation Logic
  for ($i = 1; $i <= 5; $i++) {
      $nameVar = "coauth{$i}name";
      $emailVar = "coauth{$i}email";
      $nameVal = $$nameVar;
      $emailVal = $$emailVar;
      
      // If name is present, email is required
      if (!empty($nameVal) && empty($emailVal)) {
          if (in_array($i, $allowedMissingEmailIndices)) {
             // Allow missing email for Co-Author 1-5 but log it
             $overrides[] = "Co-Author $i Email";
          } else {
             $errors[] = "Co-Author $i Email is required";
             error_log("Validation Failure: Co-Author $i ($nameVal) missing email for User ID $id");
          }
      }
  }

  foreach ([
    ['label' => 'Main author', 'value' => $email],
    ['label' => 'Supervisor', 'value' => $supervisor_email],
    ['label' => 'Co-Author 1', 'value' => $coauth1email],
    ['label' => 'Co-Author 2', 'value' => $coauth2email],
    ['label' => 'Co-Author 3', 'value' => $coauth3email],
    ['label' => 'Co-Author 4', 'value' => $coauth4email],
    ['label' => 'Co-Author 5', 'value' => $coauth5email],
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

      $allowedfileExtensions = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif');
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
    $stmt=mysqli_prepare($con,"update users set id=?, fname=?, postertitle=?, nationality=?, profession=?, category=?, email=?, organization=?, password=?, contactno=?, companyref=?, paypalref=?, coauth1name=?, coauth1nationality=?, coauth2name=?, coauth2nationality=?, coauth3name=?, coauth3nationality=?, coauth4name=?, coauth4nationality=?, coauth5name=?, coauth5nationality=?, coauth1email=?, coauth2email=?, coauth3email=?, coauth4email=?, coauth5email=?, supervisor_choice=?, supervisor_name=?, supervisor_nationality=?, supervisor_contact=?, supervisor_email=? where id=?");
    mysqli_stmt_bind_param($stmt,'isssssssssssssssssssssssssssssssi',$id,$fname,$postertitle,$nationality,$profession,$category,$email,$organization,$enc,$contact,$companyref,$paypalref,$coauth1name,$coauth1nationality,$coauth2name,$coauth2nationality,$coauth3name,$coauth3nationality,$coauth4name,$coauth4nationality,$coauth5name,$coauth5nationality,$coauth1email,$coauth2email,$coauth3email,$coauth4email,$coauth5email,$supervisor_choice,$supervisor_name,$supervisor_nationality,$supervisor_contact,$supervisor_email,$uid);
    mysqli_stmt_execute($stmt);
    
    // Log overrides if any
    if (!empty($overrides)) {
        $admin_id = $_SESSION['id'];
        $action = 'profile_update_override';
        $details = "Overridden fields: " . implode(', ', $overrides) . " for User ID $id";
        $ip = $_SERVER['REMOTE_ADDR'];
        
        $log_stmt = mysqli_prepare($con, "INSERT INTO admin_audit_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($log_stmt, 'isss', $admin_id, $action, $details, $ip);
        mysqli_stmt_execute($log_stmt);
        mysqli_stmt_close($log_stmt);
    }

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

    // Check for new co-authors and send welcome email
    $submitted_coauths = [
        ['email' => $coauth1email, 'name' => $coauth1name],
        ['email' => $coauth2email, 'name' => $coauth2name],
        ['email' => $coauth3email, 'name' => $coauth3name],
        ['email' => $coauth4email, 'name' => $coauth4name],
        ['email' => $coauth5email, 'name' => $coauth5name]
    ];

    foreach ($submitted_coauths as $sc) {
        $sc_email = strtolower(trim($sc['email']));
        if (!empty($sc_email) && !in_array($sc_email, $current_coauth_emails)) {
            // This is a new co-author
            $to = $sc['email'];
            $subject = "Added as Co-Author - ICPM 2026";
            $body = "Dear " . ($sc['name'] ?: 'Co-Author') . ",\n\n";
            $body .= "You have been added as a co-author for the poster titled \"" . $postertitle . "\".\n";
            $body .= "Main Author: " . $fname . "\n\n";
            $body .= "Best regards,\nICPM 2026 Team";
            
            // Use existing helper function
            send_mail_with_attachment_ex($to, $subject, $body, null, null, null);
            
            // Log the action
            error_log("Sent welcome email to new co-author: $to for User ID $id");
        }
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



                </ul>
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
                  <h5 class="centered"><?php echo htmlspecialchars($_SESSION['login'] ?? 'Admin'); ?></h5>

                  <li class="mt">
                      <a href="change-password.php">
                          <i class="fa fa-file"></i>
                          <span>Change Password</span>
                      </a>
                  </li>

                  <li class="sub-menu">
                      <a href="manage-users.php" >
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
      <?php $stmt=mysqli_prepare($con,"select * from users where id=?"); $uid=intval($_GET['uid']); mysqli_stmt_bind_param($stmt,'i',$uid); mysqli_stmt_execute($stmt); $ret=mysqli_stmt_get_result($stmt);
    while($row=mysqli_fetch_array($ret))

    {?>
      <section id="main-content">
          <section class="wrapper">
            <h3><i class="fa fa-angle-right"></i> <?php echo $row['fname'];?>'s Information</h3>

        <div class="row">



                  <div class="col-md-12">
                      <div class="content-panel">
                      <p align="center" style="color:#F00;"><?php if(isset($_SESSION['msg']) && $_SESSION['msg']!==''){ echo htmlspecialchars($_SESSION['msg']); $_SESSION['msg']=""; } ?></p>
                           <form class="form-horizontal style-form" id="updateProfileForm" name="form1" method="post" action="" enctype="multipart/form-data">
                           <p style="color:#F00"><?php if(isset($_SESSION['msg']) && $_SESSION['msg']!==''){ echo htmlspecialchars($_SESSION['msg']); $_SESSION['msg']=""; }?></p>

                           <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Ref Num </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="id" value="<?php echo htmlspecialchars($row['id']);?>" readonly >
                              </div>
                          </div>

                           <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">First Name </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="fname" value="<?php echo htmlspecialchars($row['fname']);?>" >
                              </div>
                          </div>

                              <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Poster Title</label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="postertitle" value="<?php echo isset($row['postertitle']) ? htmlspecialchars($row['postertitle']) : '';?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Nationality </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="nationality" value="<?php echo htmlspecialchars(isset($row['nationality']) ? $row['nationality'] : '');?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Email </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="email" value="<?php echo htmlspecialchars($row['email']);?>" >
                              </div>
                          </div>
                          
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Has Supervisor?</label>
                              <div class="col-sm-10">
                                  <select class="form-control" name="supervisor_choice">
                                      <option value="no" <?php if(isset($row['supervisor_choice']) && $row['supervisor_choice']!='yes') echo 'selected'; ?>>No</option>
                                      <option value="yes" <?php if(isset($row['supervisor_choice']) && $row['supervisor_choice']=='yes') echo 'selected'; ?>>Yes</option>
                                  </select>
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Supervisor Name</label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="supervisor_name" value="<?php echo htmlspecialchars(isset($row['supervisor_name']) ? $row['supervisor_name'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Supervisor Nationality</label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="supervisor_nationality" value="<?php echo htmlspecialchars(isset($row['supervisor_nationality']) ? $row['supervisor_nationality'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Supervisor Contact</label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="supervisor_contact" value="<?php echo htmlspecialchars(isset($row['supervisor_contact']) ? $row['supervisor_contact'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Supervisor Email</label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="supervisor_email" value="<?php echo htmlspecialchars(isset($row['supervisor_email']) ? $row['supervisor_email'] : '');?>">
                              </div>
                          </div>

                          <div id="co-authors-section">
                              <h4 style="margin-left: 40px; margin-bottom: 20px; border-bottom: 1px solid #eff2f7; padding-bottom: 10px;">Co-Authors Management</h4>
                              
                              <?php for($i=1; $i<=5; $i++): 
                                  $nKey = "coauth{$i}name";
                                  $natKey = "coauth{$i}nationality";
                                  $eKey = "coauth{$i}email";
                                  
                                  $nameVal = isset($row[$nKey]) ? $row[$nKey] : '';
                                  $natVal = isset($row[$natKey]) ? $row[$natKey] : '';
                                  $emailVal = isset($row[$eKey]) ? $row[$eKey] : '';
                                  
                                  // Determine visibility: Always show 1, or if name has value
                                  $isVisible = ($i === 1) || !empty($nameVal);
                              ?>
                              <div class="co-author-wrapper" id="coauth-wrapper-<?php echo $i; ?>" style="<?php echo $isVisible ? '' : 'display:none;'; ?> background-color: #fcfcfc; border: 1px solid #f0f0f0; padding: 15px; margin-bottom: 15px; border-radius: 4px;">
                                  <div class="row" style="margin-bottom: 10px;">
                                      <div class="col-sm-12">
                                          <strong style="margin-left: 15px; color: #797979;">Co-Author <?php echo $i; ?></strong>
                                          <?php if($i > 1): ?>
                                          <button type="button" class="btn btn-danger btn-xs pull-right remove-coauth" data-id="<?php echo $i; ?>" style="margin-right: 15px;" title="Remove this co-author"><i class="fa fa-times"></i></button>
                                          <?php endif; ?>
                                      </div>
                                  </div>

                                  <div class="form-group">
                                      <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Name</label>
                                      <div class="col-sm-10">
                                          <input type="text" class="form-control coauth-name" data-id="<?php echo $i; ?>" name="<?php echo $nKey; ?>" value="<?php echo htmlspecialchars($nameVal);?>">
                                      </div>
                                  </div>
                                  <div class="form-group">
                                      <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Nationality</label>
                                      <div class="col-sm-10">
                                          <input type="text" class="form-control coauth-nat" data-id="<?php echo $i; ?>" name="<?php echo $natKey; ?>" value="<?php echo htmlspecialchars($natVal);?>">
                                      </div>
                                  </div>
                                  <div class="form-group">
                                      <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Email</label>
                                      <div class="col-sm-10">
                                          <input type="email" class="form-control coauth-email" data-id="<?php echo $i; ?>" name="<?php echo $eKey; ?>" value="<?php echo htmlspecialchars($emailVal);?>">
                                          <span class="help-block error-msg" style="color: #a94442; display: none;"><i class="fa fa-exclamation-circle"></i> Email is required when name is provided.</span>
                                      </div>
                                  </div>
                              </div>
                              <?php endfor; ?>
                              
                              <div class="form-group">
                                  <div class="col-sm-10 col-sm-offset-2">
                                      <button type="button" id="add-coauth-btn" class="btn btn-info btn-sm"><i class="fa fa-plus"></i> Add Co-Author</button>
                                      <span id="max-coauth-msg" class="text-muted" style="display:none; margin-left: 10px;">Maximum of 5 co-authors reached.</span>
                                  </div>
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Profession. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="profession" value="<?php echo htmlspecialchars($row['profession']);?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Category</label>
                              <div class="col-sm-10">
                                  <?php 
                                    $currentCats = array_map('trim', explode(',', $row['category']));
                                    $allCats = ['Speaker', 'Organizer', 'Attendee', 'Committee Member', 'Sponsor', 'Exhibitor', 'Volunteer', 'Visitor'];
                                  ?>
                                  <select class="form-control" name="category[]" multiple size="8">
                                      <?php foreach($allCats as $cat): ?>
                                          <option value="<?php echo $cat; ?>" <?php if(in_array($cat, $currentCats)) echo 'selected'; ?>><?php echo $cat; ?></option>
                                      <?php endforeach; ?>
                                  </select>
                                  <p class="help-block">Hold Ctrl to select multiple</p>
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Organization. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="organization" value="<?php echo htmlspecialchars($row['organization']);?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Contact no. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="contact" value="<?php echo htmlspecialchars($row['contactno']);?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Company Ref. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="companyref" value="<?php echo htmlspecialchars($row['companyref']);?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">PayPal Ref. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="paypalref" value="<?php echo htmlspecialchars($row['paypalref']);?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Registration Date </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="regdate" value="<?php echo htmlspecialchars($row['posting_date']);?>" readonly >
                              </div>
                          </div>

                          <!-- Abstract Management Section -->
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Abstract Management</label>
                              <div class="col-sm-10">
                                  <?php if (!empty($row['abstract_filename'])): ?>
                                      <div style="margin-bottom: 10px;">
                                          <strong>Current File:</strong> 
                                          <a href="download-abstract.php?id=<?php echo $uid; ?>" target="_blank">
                                              <?php echo htmlspecialchars($row['abstract_filename']); ?>
                                          </a>
                                          <span class="label label-success">Uploaded</span>
                                      </div>
                                      <p class="help-block">To update, upload a new file below (replaces existing).</p>
                                  <?php else: ?>
                                      <div style="margin-bottom: 10px;">
                                          <span class="label label-warning">No abstract uploaded</span>
                                      </div>
                                  <?php endif; ?>
                                  
                                  <input type="file" name="abstract_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif" id="abstract_file_input">
                                  <p class="help-block">Allowed formats: PDF, DOC, DOCX, JPG, PNG, GIF. Max size: 5MB.</p>
                                  <div id="abstract-preview" style="margin-top: 10px; display: none;">
                                      <img id="preview-image" src="#" alt="Abstract Preview" style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; padding: 5px;">
                                  </div>
                              </div>
                          </div>

                            <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Password </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="password" value="<?php echo htmlspecialchars(isset($row['password']) ? $row['password'] : '');?>" readonly >
                              </div>
                          </div>

                          <div style="margin-left:100px;">
                          <input type="submit" name="Submit" value="Update" class="btn btn-theme"></div>
                          </form>
                      </div>
                  </div>
              </div>
    </section>
        <?php } ?>
      </section></section>
    <script src="assets/js/jquery.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script class="include" type="text/javascript" src="assets/js/jquery.dcjqaccordion.2.7.js"></script>
    <script src="assets/js/jquery.scrollTo.min.js"></script>
    <script src="assets/js/common-scripts.js"></script>
  <script>
      $(document).ready(function() {
          // Dynamic Add Co-Author
          $('#add-coauth-btn').click(function(e) {
              e.preventDefault();
              var hiddenWrappers = $('.co-author-wrapper:hidden');
              if (hiddenWrappers.length > 0) {
                  hiddenWrappers.first().fadeIn();
                  checkCoAuthorLimit();
              }
          });

          // Dynamic Remove Co-Author
          $('.remove-coauth').click(function() {
              var wrapper = $(this).closest('.co-author-wrapper');
              wrapper.fadeOut(function() {
                  // Clear inputs
                  wrapper.find('input').val('');
                  wrapper.find('.error-msg').hide();
                  wrapper.find('.form-group').removeClass('has-error');
                  wrapper.find('input').css('border', '');
                  checkCoAuthorLimit();
              });
          });

          function checkCoAuthorLimit() {
              var visibleCount = $('.co-author-wrapper:visible').length;
              if (visibleCount >= 5) {
                  $('#add-coauth-btn').hide();
                  $('#max-coauth-msg').show();
              } else {
                  $('#add-coauth-btn').show();
                  $('#max-coauth-msg').hide();
              }
          }
          
          // Initial check
          checkCoAuthorLimit();

          // Abstract Preview
          $('#abstract_file_input').change(function() {
              var file = this.files[0];
              if (file) {
                  var fileType = file.type;
                  var match = ['image/jpeg', 'image/png', 'image/gif'];
                  if ($.inArray(fileType, match) !== -1) {
                      var reader = new FileReader();
                      reader.onload = function(e) {
                          $('#preview-image').attr('src', e.target.result);
                          $('#abstract-preview').show();
                      }
                      reader.readAsDataURL(file);
                  } else {
                      $('#abstract-preview').hide();
                  }
              }
          });

          // Client-side Validation on Submit
          $('#updateProfileForm').submit(function(e) {
              var isValid = true;
              
              // Run legacy validation if it exists
              if (typeof valid === 'function') {
                  if (!valid()) {
                      isValid = false;
                  }
              }

              $('.co-author-wrapper:visible').each(function() {
                  var wrapper = $(this);
                  var nameInput = wrapper.find('.coauth-name');
                  var id = parseInt(nameInput.attr('data-id'));
                  var emailInput = wrapper.find('.coauth-email');
                  var errorMsg = wrapper.find('.error-msg');
                  
                  var nameVal = $.trim(nameInput.val());
                  var emailVal = $.trim(emailInput.val());

                  // Reset error state
                  errorMsg.hide();
                  emailInput.css('border', '');
                  emailInput.removeAttr('aria-invalid');
                  
                  // Warning Message container (create if not exists)
                  var warningMsg = wrapper.find('.warning-msg');
                  if (warningMsg.length === 0) {
                      warningMsg = $('<span class="help-block warning-msg" style="color: #8a6d3b; display: none;"><i class="fa fa-exclamation-triangle"></i> Warning: Email is missing. Proceeding with override.</span>');
                      emailInput.after(warningMsg);
                  }
                  warningMsg.hide();

                  if (nameVal !== '') {
                      if (emailVal === '') {
                          if ([1, 2, 3, 4, 5].indexOf(id) !== -1) {
                              // Allowed missing email
                              warningMsg.show();
                              // Do NOT set isValid = false
                          } else {
                              isValid = false;
                              errorMsg.html('<i class="fa fa-exclamation-circle"></i> Email is required when name is provided.');
                              errorMsg.show();
                              emailInput.css('border', '1px solid #a94442');
                              emailInput.attr('aria-invalid', 'true');
                          }
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
