<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'session_setup.php';
include 'dbconnection.php';
require_once 'permission_helper.php';
require_once __DIR__ . '/../../logging_helper.php';

mysqli_set_charset($con, 'utf8mb4');

// Auth Check
if (empty($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

// Permission Check (Edit Users allows Adding Users)
if (!has_permission($con, 'edit_users')) {
    die("You do not have permission to add users.");
}

if (isset($_POST['Submit'])) {
    $fname = $_POST['fname'];
    $postertitle = isset($_POST['postertitle']) ? $_POST['postertitle'] : '';
    $nationality = isset($_POST['nationality']) ? $_POST['nationality'] : '';
    $coauth1name = isset($_POST['coauth1name']) ? $_POST['coauth1name'] : '';
    $coauth2name = isset($_POST['coauth2name']) ? $_POST['coauth2name'] : '';
    $coauth3name = isset($_POST['coauth3name']) ? $_POST['coauth3name'] : '';
    $coauth4name = isset($_POST['coauth4name']) ? $_POST['coauth4name'] : '';
    $coauth5name = isset($_POST['coauth5name']) ? $_POST['coauth5name'] : '';
    $coauth1nationality = isset($_POST['coauth1nationality']) ? $_POST['coauth1nationality'] : '';
    $coauth2nationality = isset($_POST['coauth2nationality']) ? $_POST['coauth2nationality'] : '';
    $coauth3nationality = isset($_POST['coauth3nationality']) ? $_POST['coauth3nationality'] : '';
    $coauth4nationality = isset($_POST['coauth4nationality']) ? $_POST['coauth4nationality'] : '';
    $coauth5nationality = isset($_POST['coauth5nationality']) ? $_POST['coauth5nationality'] : '';
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $supervisor_choice = isset($_POST['supervisor_choice']) ? $_POST['supervisor_choice'] : 'no';
    $supervisor_name = isset($_POST['supervisor_name']) ? $_POST['supervisor_name'] : '';
    $supervisor_nationality = isset($_POST['supervisor_nationality']) ? $_POST['supervisor_nationality'] : '';
    $supervisor_contact = isset($_POST['supervisor_contact']) ? $_POST['supervisor_contact'] : '';
    $supervisor_email = isset($_POST['supervisor_email']) ? trim($_POST['supervisor_email']) : '';
    $coauth1email = isset($_POST['coauth1email']) ? trim($_POST['coauth1email']) : '';
    $coauth2email = isset($_POST['coauth2email']) ? trim($_POST['coauth2email']) : '';
    $coauth3email = isset($_POST['coauth3email']) ? trim($_POST['coauth3email']) : '';
    $coauth4email = isset($_POST['coauth4email']) ? trim($_POST['coauth4email']) : '';
    $coauth5email = isset($_POST['coauth5email']) ? trim($_POST['coauth5email']) : '';
    $profession = $_POST['profession'];
    $categoryInput = isset($_POST['category']) ? $_POST['category'] : '';
    $category = is_array($categoryInput) ? implode(', ', $categoryInput) : $categoryInput;
    $organization = $_POST['organization'];
    $companyref = $_POST['companyref'];
    $paypalref = $_POST['paypalref'];
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    // Check if email already exists
    $check_query = mysqli_query($con, "SELECT id FROM users WHERE email='$email'");
    if (mysqli_num_rows($check_query) > 0) {
        $_SESSION['msg'] = "Email already exists. Please use a different email.";
    } else {
        $enc = password_hash($password, PASSWORD_DEFAULT);
        $errors = [];
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
        }

        if (count($errors) > 0) {
            $_SESSION['msg'] = implode('. ', $errors);
        } else {
            $sql = "INSERT INTO users (fname, postertitle, nationality, profession, category, email, organization, password, contactno, companyref, paypalref, coauth1name, coauth1nationality, coauth2name, coauth2nationality, coauth3name, coauth3nationality, coauth4name, coauth4nationality, coauth5name, coauth5nationality, coauth1email, coauth2email, coauth3email, coauth4email, coauth5email, supervisor_choice, supervisor_name, supervisor_nationality, supervisor_contact, supervisor_email, posting_date, source_system) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'poster')";
            
            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, 'sssssssssssssssssssssssssssssss', $fname, $postertitle, $nationality, $profession, $category, $email, $organization, $enc, $contact, $companyref, $paypalref, $coauth1name, $coauth1nationality, $coauth2name, $coauth2nationality, $coauth3name, $coauth3nationality, $coauth4name, $coauth4nationality, $coauth5name, $coauth5nationality, $coauth1email, $coauth2email, $coauth3email, $coauth4email, $coauth5email, $supervisor_choice, $supervisor_name, $supervisor_nationality, $supervisor_contact, $supervisor_email);
            
            if (mysqli_stmt_execute($stmt)) {
                $new_user_id = mysqli_insert_id($con);
                
                // Abstract Upload
                if (isset($_FILES['abstract_file']) && $_FILES['abstract_file']['error'] == UPLOAD_ERR_OK) {
                    $fileTmpPath = $_FILES['abstract_file']['tmp_name'];
                    $fileName = $_FILES['abstract_file']['name'];
                    $fileType = $_FILES['abstract_file']['type'];
                    $newFileContent = file_get_contents($fileTmpPath);
                    
                    $null = NULL;
                    $stmt_abs = mysqli_prepare($con, "UPDATE users SET abstract_filename=?, abstract_mime=?, abstract_blob=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt_abs, 'ssbi', $fileName, $fileType, $null, $new_user_id);
                    mysqli_stmt_send_long_data($stmt_abs, 2, $newFileContent);
                    mysqli_stmt_execute($stmt_abs);
                    mysqli_stmt_close($stmt_abs);
                }

                $_SESSION['msg'] = "User Added successfully";
                echo "<script>alert('User added successfully'); window.location='manage-users.php';</script>";
            } else {
                $_SESSION['msg'] = "Error adding user: " . mysqli_error($con);
            }
        }
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
    <title>Admin | Add User</title>
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
      
      <section id="main-content">
          <section class="wrapper">
            <h3><i class="fa fa-angle-right"></i> Add New User</h3>

        <div class="row">
                  <div class="col-md-12">
                      <div class="content-panel">
                      <p align="center" style="color:#F00;"><?php if(isset($_SESSION['msg']) && $_SESSION['msg']!==''){ echo htmlspecialchars($_SESSION['msg']); $_SESSION['msg']=""; } ?></p>
                           <form class="form-horizontal style-form" id="addUserForm" name="form1" method="post" action="" enctype="multipart/form-data">
                           
                           <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">First Name </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="fname" required>
                              </div>
                          </div>

                              <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Poster Title</label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="postertitle">
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Nationality </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="nationality">
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Email </label>
                              <div class="col-sm-10">
                                  <input type="email" class="form-control" name="email" required>
                              </div>
                          </div>
                          
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Has Supervisor?</label>
                              <div class="col-sm-10">
                                  <select class="form-control" name="supervisor_choice">
                                      <option value="no">No</option>
                                      <option value="yes">Yes</option>
                                  </select>
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Supervisor Name</label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="supervisor_name">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Supervisor Nationality</label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="supervisor_nationality">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Supervisor Contact</label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="supervisor_contact">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Supervisor Email</label>
                              <div class="col-sm-10">
                                  <input type="email" class="form-control" name="supervisor_email">
                              </div>
                          </div>

                          <div id="co-authors-section">
                              <h4 style="margin-left: 40px; margin-bottom: 20px; border-bottom: 1px solid #eff2f7; padding-bottom: 10px;">Co-Authors Management</h4>
                              
                              <?php for($i=1; $i<=5; $i++): ?>
                              <div class="co-author-wrapper" id="coauth-wrapper-<?php echo $i; ?>" style="<?php echo ($i > 1) ? 'display:none;' : ''; ?>">
                                  <div class="form-group">
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
                                          <input type="text" class="form-control coauth-name" data-id="<?php echo $i; ?>" name="coauth<?php echo $i; ?>name">
                                      </div>
                                  </div>
                                  <div class="form-group">
                                      <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Nationality</label>
                                      <div class="col-sm-10">
                                          <input type="text" class="form-control coauth-nat" data-id="<?php echo $i; ?>" name="coauth<?php echo $i; ?>nationality">
                                      </div>
                                  </div>
                                  <div class="form-group">
                                      <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Email</label>
                                      <div class="col-sm-10">
                                          <input type="email" class="form-control coauth-email" data-id="<?php echo $i; ?>" name="coauth<?php echo $i; ?>email">
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
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Profession </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="profession">
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Category</label>
                              <div class="col-sm-10">
                                  <select class="form-control" name="category[]" multiple size="8">
                                      <option value="Speaker">Speaker</option>
                                      <option value="Organizer">Organizer</option>
                                      <option value="Attendee">Attendee</option>
                                      <option value="Committee Member">Committee Member</option>
                                      <option value="Sponsor">Sponsor</option>
                                      <option value="Exhibitor">Exhibitor</option>
                                      <option value="Volunteer">Volunteer</option>
                                      <option value="Visitor">Visitor</option>
                                  </select>
                                  <p class="help-block">Hold Ctrl to select multiple</p>
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Organization </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="organization">
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Contact no. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="contact">
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Company Ref. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="companyref">
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">PayPal Ref. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="paypalref">
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Abstract Upload</label>
                              <div class="col-sm-10">
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
                                  <input type="text" class="form-control" name="password" required>
                              </div>
                          </div>

                          <div style="margin-left:100px;">
                          <input type="submit" name="Submit" value="Add User" class="btn btn-theme"></div>
                          </form>
                      </div>
                  </div>
              </div>
    </section>
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
          
          // Visitor validation
          var categorySelect = document.querySelector('select[name="category[]"]');
          if (categorySelect) {
                categorySelect.addEventListener('change', function() {
                    var selectedOptions = Array.from(this.selectedOptions).map(option => option.value);
                    var isVisitor = selectedOptions.includes('Visitor');
                    if (isVisitor && selectedOptions.length > 1) {
                        alert('Note: "Visitor" category cannot be combined with other roles. Please select either "Visitor" or your specific role(s).');
                        for (var i = 0; i < this.options.length; i++) {
                            if (this.options[i].value === 'Visitor') {
                                this.options[i].selected = false;
                            }
                        }
                    }
                });
            }

          // Client-side Validation on Submit
          $('#addUserForm').submit(function(e) {
              var isValid = true;
              
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
                  
                  // Warning Message container
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