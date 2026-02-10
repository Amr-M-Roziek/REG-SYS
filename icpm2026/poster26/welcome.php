<?php
ini_set('session.save_path', sys_get_temp_dir());
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');
session_start();
if (isset($_POST['auto_logout_on_exit'])) {
  $_SESSION['auto_logout_on_exit'] = $_POST['auto_logout_on_exit'] === '1';
}
if (isset($_GET['auto_logout'])) {
  $_SESSION['auto_logout_on_exit'] = $_GET['auto_logout'] === '1';
}
if (!isset($_SESSION['logout_token'])) {
  $_SESSION['logout_token'] = bin2hex(random_bytes(16));
}
if (!isset($_SESSION['id']) || intval($_SESSION['id']) <= 0) {
  header('location:index.php');
} else{
include 'dbconnection.php';

$msg = "";
$msg_type = "";

// Handle Upload
if (isset($_POST['upload_abstract'])) {
    if (isset($_FILES['abstract_file']) && $_FILES['abstract_file']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['abstract_file']['tmp_name'];
        $fileName = $_FILES['abstract_file']['name'];
        $fileSize = $_FILES['abstract_file']['size'];
        $fileType = $_FILES['abstract_file']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = array('pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif');
        if (in_array($fileExtension, $allowedfileExtensions)) {
            if ($fileSize <= 5 * 1024 * 1024) {
                 $newFileContent = file_get_contents($fileTmpPath);
                 $null = NULL;
                 $stmt_abs = mysqli_prepare($con, "UPDATE users SET abstract_filename=?, abstract_mime=?, abstract_blob=? WHERE id=?");
                 mysqli_stmt_bind_param($stmt_abs, 'ssbi', $fileName, $fileType, $null, $_SESSION['id']);
                 mysqli_stmt_send_long_data($stmt_abs, 2, $newFileContent);
                 if(mysqli_stmt_execute($stmt_abs)) {
                     $msg = "Abstract uploaded successfully!";
                     $msg_type = "success";
                 } else {
                     $msg = "Database update failed: " . mysqli_error($con);
                     $msg_type = "danger";
                 }
                 mysqli_stmt_close($stmt_abs);
            } else {
                $msg = "File is too large. Max size is 5MB.";
                $msg_type = "danger";
            }
        } else {
            $msg = "Invalid file format. Only PDF, DOC, DOCX, JPG, PNG, and GIF are allowed.";
            $msg_type = "danger";
        }
    } else {
        $uploadErr = $_FILES['abstract_file']['error'];
        if ($uploadErr == UPLOAD_ERR_INI_SIZE || $uploadErr == UPLOAD_ERR_FORM_SIZE) {
            $msg = "File is too large. Server limit exceeded.";
        } else if ($uploadErr == UPLOAD_ERR_PARTIAL) {
            $msg = "File was only partially uploaded.";
        } else if ($uploadErr == UPLOAD_ERR_NO_FILE) {
            $msg = "No file was selected.";
        } else {
            $msg = "Upload failed with error code: " . $uploadErr;
        }
        $msg_type = "danger";
    }
}

// Handle Title Update
if (isset($_POST['save_title'])) {
    $p_title = isset($_POST['poster_title']) ? trim($_POST['poster_title']) : '';
    if (!empty($p_title)) {
        // Update database
        $stmt_pt = mysqli_prepare($con, "UPDATE users SET postertitle=? WHERE id=?");
        mysqli_stmt_bind_param($stmt_pt, "si", $p_title, $_SESSION['id']);
        if (mysqli_stmt_execute($stmt_pt)) {
             $msg = "Poster title saved successfully!";
             $msg_type = "success";
             // Update session to reflect change immediately
             $_SESSION['postertitle'] = $p_title;
        } else {
             $msg = "Error saving title: " . mysqli_error($con);
             $msg_type = "danger";
        }
        mysqli_stmt_close($stmt_pt);
    } else {
        $msg = "Title cannot be empty.";
        $msg_type = "danger";
    }
}

// Check if abstract and poster title exist
$abstract_exists = false;
$abstract_filename = "";
$db_poster_title = "";
$query_abs = "SELECT abstract_filename, postertitle FROM users WHERE id=?";
$stmt_check = mysqli_prepare($con, $query_abs);
mysqli_stmt_bind_param($stmt_check, "i", $_SESSION['id']);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_bind_result($stmt_check, $abstract_filename, $db_poster_title);
if (mysqli_stmt_fetch($stmt_check)) {
    if (!empty($abstract_filename)) {
        $abstract_exists = true;
    }
}
mysqli_stmt_close($stmt_check);
?><!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Welcome </title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/heroic-features.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="https://icpm.ae/" style="padding: 5px 15px;">
                    <img src="https://regsys.cloud/icpm2026/images/icpm-logo.png" alt="ICPM 2026 Conference Logo" style="height: 40px; width: auto;">
                </a>
                <a class="navbar-brand" href="#">Welcome !</a>
            </div>
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <li>
                        <a href="#"><?php echo $_SESSION['name'];?></a>
                    </li>
                    <li>
                        <a href="logout.php">Logout</a>
                    </li>

                </ul>
            </div>
        </div>
    </nav>
    <div class="container" style="text-align: center;">
        <header class="jumbotron hero-spacer">
            <h2>Welcome! <?php echo $_SESSION['fullname'] ?? $_SESSION['name'];?></h2>

            <?php if (!empty($msg)): ?>
            <div class="alert alert-<?php echo $msg_type; ?> alert-dismissible" role="alert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
              <?php echo $msg; ?>
            </div>
            <?php endif; ?>

            <?php if (!$abstract_exists): ?>
            <div class="alert alert-warning" role="alert" style="text-align: left; margin-bottom: 20px; border-left: 5px solid #8a6d3b;">
                <h4><i class="glyphicon glyphicon-warning-sign"></i> Abstract Missing</h4>
                <p>We noticed that your abstract is missing from our records. Please upload your abstract to complete your registration requirements.</p>
            </div>

            <div class="panel panel-default" style="max-width: 600px; margin: 0 auto 30px auto;">
                <div class="panel-heading">
                    <h3 class="panel-title">Upload Abstract</h3>
                </div>
                <div class="panel-body">
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                    <label for="abstract_file">Choose File (PDF, DOC, DOCX, JPG, PNG, GIF | Max 5MB)</label>
                    <input type="file" name="abstract_file" id="abstract_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif" required>
                </div>
                        <button type="submit" name="upload_abstract" class="btn btn-primary btn-block">Upload Abstract</button>
                    </form>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-success" role="alert" style="margin-top: 20px;">
                    <i class="glyphicon glyphicon-ok-sign"></i> Abstract Submitted: <strong><?php echo htmlspecialchars($abstract_filename); ?></strong>
                    <a href="download-abstract.php" target="_blank" class="btn btn-success btn-xs" style="margin-left: 10px;">Download/Preview</a>
                </div>
            <?php endif; ?>


            <?php if (isset($_SESSION['role']) && $_SESSION['role']==='coauthor') { ?>
            <h1> <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo $_SESSION['id'].'-'.($_SESSION['coauthor_suffix'] ?? '');?>" title="Your reference Number is <?php echo $_SESSION['id'].'-'.($_SESSION['coauthor_suffix'] ?? '');?>" /></h1>
            <?php } else { ?>
            <h1> <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo $_SESSION['id'];?>" title="Your reference Number is <?php echo $_SESSION['id'];?>" /></h1>
            <?php } ?>
            <h3>Reg No: <?php echo $_SESSION['id'];?></h3>
            <br>
                <h2 style="text-transform:uppercase"><?php echo $_SESSION['scategory'];?></h2>
            
            <?php if (empty($db_poster_title)): ?>
                <div class="panel panel-info" style="max-width: 600px; margin: 20px auto;">
                    <div class="panel-heading"><h3 class="panel-title">Poster Title Required</h3></div>
                    <div class="panel-body">
                        <p>Please enter the title of your poster to proceed.</p>
                        <form method="post">
                            <div class="form-group">
                                <textarea name="poster_title" class="form-control" rows="3" required placeholder="Enter your poster title here..."></textarea>
                            </div>
                            <button type="submit" name="save_title" class="btn btn-info btn-block">Save Title</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <h3>Poster Title: <?php echo htmlspecialchars($db_poster_title, ENT_QUOTES, 'UTF-8'); ?></h3>
            <?php endif; ?>

            <div>
            <p>Welcome To ICPM 2026</p>
            </div>
            <div style="margin-top:12px">
            <?php if (!isset($_SESSION['email_committee_sent']) || $_SESSION['email_committee_sent'] !== true) { ?>
              <button class="btn btn-warning" id="resendBtn">Resend Abstract to Committee</button>
              <script>
              document.getElementById('resendBtn')?.addEventListener('click', function(){
                fetch('index.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'resend_abstract=1' })
                .then(r=>r.json()).then(function(d){
                  if (d && d.ok) { alert('Abstract email resent successfully'); location.reload(); }
                  else { alert('Failed to resend: ' + (d && d.error ? d.error : 'Unknown error')); }
                }).catch(function(e){ alert('Network error'); });
              });
              </script>
            <?php } else { ?>
              <p style="color: #3c763d">Abstract email delivered to committee.</p>
            <?php } ?>
            </div>
            <div style="margin-top:12px">
              <form method="post" action="welcome.php" style="display:inline-block">
                <input type="hidden" name="auto_logout_on_exit" value="<?php echo isset($_SESSION['auto_logout_on_exit']) && $_SESSION['auto_logout_on_exit'] ? '0' : '1'; ?>">
                <button type="submit" class="btn btn-default">
                  <?php echo (isset($_SESSION['auto_logout_on_exit']) && $_SESSION['auto_logout_on_exit']) ? 'Disable Auto Logout on Exit' : 'Enable Auto Logout on Exit'; ?>
                </button>
              </form>
            </div>
            <?php
              $uid = intval($_SESSION['id']);
              $res = mysqli_query($con, "select coauth1name,coauth2name,coauth3name,coauth4name,coauth5name from users where id='$uid'");
              if ($res && mysqli_num_rows($res) > 0) {
                $r = mysqli_fetch_assoc($res);
                $cos = array(
                  array('n'=>$r['coauth1name'],'suf'=>'CO1'),
                  array('n'=>$r['coauth2name'],'suf'=>'CO2'),
                  array('n'=>$r['coauth3name'],'suf'=>'CO3'),
                  array('n'=>$r['coauth4name'],'suf'=>'CO4'),
                  array('n'=>$r['coauth5name'],'suf'=>'CO5'),
                );
                $has = false;
                foreach ($cos as $c) { if (trim($c['n']) !== '') { $has = true; break; } }
                if ($has && (!isset($_SESSION['role']) || $_SESSION['role']!=='coauthor')) {
                  echo '<h3>Co-Authors QR</h3>';
                  echo '<div class="row" style="margin-top:10px">';
                  foreach ($cos as $idx=>$c) {
                    if (trim($c['n']) === '') { continue; }
                    $label = htmlspecialchars($c['n'], ENT_QUOTES, 'UTF-8');
                    $data = $_SESSION['id'] . '-' . $c['suf'];
                    echo '<div class="col-sm-6 col-md-4" style="margin-bottom:15px">';
                    echo '<div>';
                    echo '<img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' . $data . '" alt="QR" />';
                    echo '<div><strong>' . $label . '</strong></div>';
                    echo '</div>';
                    echo '</div>';
                  }
                  echo '</div>';
                }
              }
            ?>
                <p><a  href="logout.php" class="btn btn-primary btn-large"> Logout </a>  <a  href="https://icpm.ae/" class="btn btn-primary btn-large"> Back Home </a>
            </p>
        </header>

        </div>

    </div>
    <script src="js/jquery.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script>
    (function(){
      var enabled = <?php echo (isset($_SESSION['auto_logout_on_exit']) && $_SESSION['auto_logout_on_exit']) ? 'true' : 'false'; ?>;
      if (!enabled) return;
      var token = "<?php echo htmlspecialchars($_SESSION['logout_token'], ENT_QUOTES, 'UTF-8'); ?>";
      function doLogout(){
        try {
          var fd = new FormData();
          fd.append('ajax','1');
          fd.append('logout_token', token);
          var ok = false;
          if (navigator.sendBeacon) {
            ok = navigator.sendBeacon('logout.php', fd);
          }
          if (!ok) {
            fetch('logout.php', { method: 'POST', body: fd, keepalive: true }).catch(function(){});
          }
        } catch(e) {}
      }
      window.addEventListener('beforeunload', function(){ doLogout(); });
      window.addEventListener('pagehide', function(){ doLogout(); });
    }());
    </script>
</body>

</html>
<?php } ?>
