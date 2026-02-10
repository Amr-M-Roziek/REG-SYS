<?php
session_start();
include'dbconnection.php';
mysqli_set_charset($con, 'utf8mb4');
//Checking session is valid or not
if (!isset($_SESSION['id']) || strlen($_SESSION['id'])==0) {
  header('location:logout.php');
  } else{

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
  $coauth1email=isset($_POST['coauth1email']) ? trim($_POST['coauth1email']) : '';
  $coauth2email=isset($_POST['coauth2email']) ? trim($_POST['coauth2email']) : '';
  $coauth3email=isset($_POST['coauth3email']) ? trim($_POST['coauth3email']) : '';
  $coauth4email=isset($_POST['coauth4email']) ? trim($_POST['coauth4email']) : '';
  $coauth5email=isset($_POST['coauth5email']) ? trim($_POST['coauth5email']) : '';
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
  foreach ([
    ['label' => 'Main author', 'value' => $email],
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
  if (count($errors) > 0) {
    $_SESSION['msg'] = implode('. ', $errors);
  } else {
    $stmt=mysqli_prepare($con,"update users set id=?, fname=?, lname=?, nationality=?, profession=?, category=?, email=?, organization=?, password=?, contactno=?, companyref=?, paypalref=?, coauth1name=?, coauth1nationality=?, coauth2name=?, coauth2nationality=?, coauth3name=?, coauth3nationality=?, coauth4name=?, coauth4nationality=?, coauth5name=?, coauth5nationality=?, coauth1email=?, coauth2email=?, coauth3email=?, coauth4email=?, coauth5email=? where id=?");
    mysqli_stmt_bind_param($stmt,'issssssssssssssssssssssssssi',$id,$fname,$lname,$nationality,$profession,$category,$email,$organization,$enc,$contact,$companyref,$paypalref,$coauth1name,$coauth1nationality,$coauth2name,$coauth2nationality,$coauth3name,$coauth3nationality,$coauth4name,$coauth4nationality,$coauth5name,$coauth5nationality,$coauth1email,$coauth2email,$coauth3email,$coauth4email,$coauth5email,$uid);
    mysqli_stmt_execute($stmt);
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
                  <h5 class="centered"><?php echo $_SESSION['login'];?></h5>

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
                           <form class="form-horizontal style-form" name="form1" method="post" action="" onSubmit="return valid();">
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
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Last Name</label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="lname" value="<?php echo isset($row['lname']) ? htmlspecialchars($row['lname']) : '';?>" >
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
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Co-Author 1 Name </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="coauth1name" value="<?php echo htmlspecialchars(isset($row['coauth1name']) ? $row['coauth1name'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Co-Author 1 Nationality </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="coauth1nationality" value="<?php echo htmlspecialchars(isset($row['coauth1nationality']) ? $row['coauth1nationality'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Co-Author 1 Email </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="coauth1email" value="<?php echo htmlspecialchars($row['coauth1email']);?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Co-Author 2 Name </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="coauth2name" value="<?php echo htmlspecialchars(isset($row['coauth2name']) ? $row['coauth2name'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Co-Author 2 Nationality </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="coauth2nationality" value="<?php echo htmlspecialchars(isset($row['coauth2nationality']) ? $row['coauth2nationality'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Co-Author 2 Email </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="coauth2email" value="<?php echo htmlspecialchars($row['coauth2email']);?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Co-Author 3 Name </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="coauth3name" value="<?php echo htmlspecialchars(isset($row['coauth3name']) ? $row['coauth3name'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Co-Author 3 Nationality </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="coauth3nationality" value="<?php echo htmlspecialchars(isset($row['coauth3nationality']) ? $row['coauth3nationality'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Co-Author 3 Email </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="coauth3email" value="<?php echo htmlspecialchars($row['coauth3email']);?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Co-Author 4 Name </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="coauth4name" value="<?php echo htmlspecialchars(isset($row['coauth4name']) ? $row['coauth4name'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Co-Author 4 Nationality </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="coauth4nationality" value="<?php echo htmlspecialchars(isset($row['coauth4nationality']) ? $row['coauth4nationality'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Co-Author 4 Email </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="coauth4email" value="<?php echo htmlspecialchars($row['coauth4email']);?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Co-Author 5 Name </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="coauth5name" value="<?php echo htmlspecialchars(isset($row['coauth5name']) ? $row['coauth5name'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Co-Author 5 Nationality </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="coauth5nationality" value="<?php echo htmlspecialchars(isset($row['coauth5nationality']) ? $row['coauth5nationality'] : '');?>">
                              </div>
                          </div>
                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Co-Author 5 Email </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="coauth5email" value="<?php echo htmlspecialchars($row['coauth5email']);?>">
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Profession. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="profession" value="<?php echo htmlspecialchars($row['profession']);?>" >
                              </div>
                          </div>

                          <div class="form-group">
                              <label class="col-sm-2 col-sm-2 control-label" style="padding-left:40px;">Category. </label>
                              <div class="col-sm-10">
                                  <input type="text" class="form-control" name="category" value="<?php echo htmlspecialchars($row['category']);?>" >
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
    <script src="assets/js/jquery.nicescroll.js" type="text/javascript"></script>
    <script src="assets/js/common-scripts.js"></script>
  <script>
      $(function(){
          $('select.styled').customSelect();
      });

  </script>

  </body>
</html>
<?php } ?>
