<?php
ini_set('session.save_path', sys_get_temp_dir());
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
error_reporting(E_ALL);
session_start();
require_once('countries_helper.php');
require_once('phone_codes_helper.php');
ob_start();
require_once( 'dbconnection.php' );
error_reporting(E_ALL);

// Initialize supervisor variables
$supervisor_choice = isset($_POST['supervisor_choice']) ? $_POST['supervisor_choice'] : 'no';
$supervisor_name = isset($_POST['supervisor_name']) ? $_POST['supervisor_name'] : '';
$supervisor_nationality = isset($_POST['supervisor_nationality']) ? $_POST['supervisor_nationality'] : '';
$supervisor_contact = isset($_POST['supervisor_contact']) ? $_POST['supervisor_contact'] : '';
$supervisor_email = isset($_POST['supervisor_email']) ? $_POST['supervisor_email'] : '';
  
if (isset($_SESSION['id']) && intval($_SESSION['id']) > 0) {
  $host = $_SERVER['HTTP_HOST'];
  $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $url = "$scheme://$host$uri/welcome.php";
  ob_end_clean();
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
  exit;
}
if (isset($_POST['redirect_event']) && $_POST['redirect_event'] === 'start') {
  $em = isset($_POST['email']) ? $_POST['email'] : '';
  $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
  $line = date('c') . " | REDIRECT_START | $em | $uid |  | start\n";
  @file_put_contents(__DIR__ . '/submissions.log', $line, FILE_APPEND);
  header('Content-Type: application/json');
  echo json_encode(array('ok'=>true));
  exit;
}



function send_mail_with_attachment($to, $subject, $bodyText, $filePath, $fileName, $from, $replyTo=null) {
    $boundary = md5(uniqid(time(), true));
    $headers = "From: $from\r\n";
    if ($replyTo) $headers .= "Reply-To: $replyTo\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
    $body = "--$boundary\r\n";
    $body .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $bodyText . "\r\n";
    if ($filePath && is_file($filePath)) {
        $data = chunk_split(base64_encode(file_get_contents($filePath)));
        $mime = 'application/octet-stream';
        if (function_exists('finfo_open')) {
            $fi = @finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) { $mime = @finfo_file($fi, $filePath); @finfo_close($fi); }
        }
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: $mime; name=\"$fileName\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n";
        $body .= $data . "\r\n";
    }
    $body .= "--$boundary--";
    return mail($to, $subject, $body, $headers);
}
function send_mail_with_attachment_ex($to, $subject, $bodyText, $filePath, $fileName, $from, $replyTo=null) {
    $transport = 'mail';
    $error = '';
    $ok = false;
    $ts = date('c');
    $linePrefix = $ts . " | EMAIL_ATTEMPT | $to | " . ($fileName ?: '') . " | ";
    @file_put_contents(__DIR__ . '/submissions.log', $linePrefix . "transport=$transport; subject=" . $subject . "\n", FILE_APPEND);
    $host = getenv('SMTP_HOST');
    $user = getenv('SMTP_USER');
    $pass = getenv('SMTP_PASS');
    $port = getenv('SMTP_PORT');
    $secure = getenv('SMTP_SECURE') ?: 'tls';
    $fromAddr = getenv('SMTP_FROM');
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
        $mail->IsHTML(false);
        $mail->CharSet = 'UTF-8';
        $mail->Username = $user;
        $mail->Password = $pass;
        $mail->SetFrom($fromAddr ?: 'ICPM@reg-sys.com', $fromName);
        $mail->Subject = $subject;
        $mail->Body = $bodyText;
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
        $ok = send_mail_with_attachment($to, $subject, $bodyText, $filePath, $fileName, $from, $replyTo);
        if (!$ok) { $error = 'mail() returned false'; }
    }
    $status = $ok ? 'EMAIL_SENT' : 'EMAIL_FAIL';
    $msg = $ok ? "transport=$transport" : ("transport=$transport; error=" . $error);
    @file_put_contents(__DIR__ . '/submissions.log', $ts . " | $status | $to | " . ($fileName ?: '') . " | " . $msg . "\n", FILE_APPEND);
    return array($ok, $transport, $error);
}
function log_submission($email, $status, $message, $filename, $refId) {
    $line = date('c') . " | $status | $email | $refId | $filename | $message\n";
    @file_put_contents(__DIR__ . '/submissions.log', $line, FILE_APPEND);
}



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

if ( isset( $_POST[ 'signup' ] ) )
{
  $fname = $_POST[ 'fname' ];
  $nationality = $_POST[ 'nationality' ];
  $coauth1name = $_POST[ 'coauth1name' ];
  $coauth1nationality = $_POST[ 'coauth1nationality' ];
  $coauth1email = isset($_POST['coauth1email']) ? $_POST['coauth1email'] : '';
  $coauth1mobile = isset($_POST['coauth1mobile']) ? $_POST['coauth1mobile'] : '';
  $coauth2name = $_POST[ 'coauth2name' ];
  $coauth2nationality = $_POST[ 'coauth2nationality' ];
  $coauth2email = isset($_POST['coauth2email']) ? $_POST['coauth2email'] : '';
  $coauth2mobile = isset($_POST['coauth2mobile']) ? $_POST['coauth2mobile'] : '';
  $coauth3name = $_POST[ 'coauth3name' ];
  $coauth3nationality = $_POST[ 'coauth3nationality' ];
  $coauth3email = isset($_POST['coauth3email']) ? $_POST['coauth3email'] : '';
  $coauth3mobile = isset($_POST['coauth3mobile']) ? $_POST['coauth3mobile'] : '';
  $coauth4name = $_POST[ 'coauth4name' ];
  $coauth4nationality = $_POST[ 'coauth4nationality' ];
  $coauth4email = isset($_POST['coauth4email']) ? $_POST['coauth4email'] : '';
  $coauth4mobile = isset($_POST['coauth4mobile']) ? $_POST['coauth4mobile'] : '';
  $supervisor_choice = isset($_POST['supervisor_choice']) ? $_POST['supervisor_choice'] : 'no';
  $supervisor_name = isset($_POST['supervisor_name']) ? $_POST['supervisor_name'] : '';
  $supervisor_nationality = isset($_POST['supervisor_nationality']) ? $_POST['supervisor_nationality'] : '';
  $supervisor_contact = isset($_POST['supervisor_contact']) ? $_POST['supervisor_contact'] : '';
  $supervisor_email = isset($_POST['supervisor_email']) ? $_POST['supervisor_email'] : '';
  $email = $_POST[ 'email' ];
  $profession = $_POST[ 'profession' ];
  $organization = $_POST[ 'organization' ];
  $category = $_POST[ 'category' ];
  $postertitle = isset($_POST['postertitle']) ? $_POST['postertitle'] : '';
  $password = $_POST[ 'password' ];
  $contact = isset($_POST['contact']) ? $_POST['contact'] : (isset($_POST['contactno']) ? $_POST['contactno'] : '');
  $userip = $_POST[ 'userip' ];
  $enc_password = password_hash($password, PASSWORD_DEFAULT);

  $coauthors_count = isset($_POST['coauthors_count']) ? intval($_POST['coauthors_count']) : 0;
  if ($coauthors_count > 4) {
    echo "<script>alert('Maximum team size exceeded. You can add up to 4 co-authors only.');</script>";
    exit();
  }
  // Ensure schema email columns exist
  $needCols = array('coauth1email','coauth2email','coauth3email','coauth4email');
  foreach ($needCols as $col) {
    $check = mysqli_query($con, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='$col'");
    if ($check && mysqli_num_rows($check) == 0) {
      @mysqli_query($con, "ALTER TABLE users ADD COLUMN `$col` VARCHAR(255) NULL");
    }
  }
  // Ensure schema mobile columns exist
  $needMobCols = array('coauth1mobile','coauth2mobile','coauth3mobile','coauth4mobile');
  foreach ($needMobCols as $col) {
    $check = mysqli_query($con, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='$col'");
    if ($check && mysqli_num_rows($check) == 0) {
      @mysqli_query($con, "ALTER TABLE users ADD COLUMN `$col` VARCHAR(15) NULL");
    }
  }
  $ptCheck = mysqli_query($con, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='postertitle'");
  if ($ptCheck && mysqli_num_rows($ptCheck) == 0) {
    @mysqli_query($con, "ALTER TABLE users ADD COLUMN `postertitle` VARCHAR(255) NULL");
  }

  // Ensure supervisor columns exist
  $supCheck = mysqli_query($con, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='supervisor_choice'");
  if ($supCheck && mysqli_num_rows($supCheck) == 0) {
      @mysqli_query($con, "ALTER TABLE users ADD COLUMN `supervisor_choice` VARCHAR(3) NULL DEFAULT 'no'");
      @mysqli_query($con, "ALTER TABLE users ADD COLUMN `supervisor_name` VARCHAR(255) NULL");
      @mysqli_query($con, "ALTER TABLE users ADD COLUMN `supervisor_nationality` VARCHAR(255) NULL");
      @mysqli_query($con, "ALTER TABLE users ADD COLUMN `supervisor_contact` VARCHAR(20) NULL");
      @mysqli_query($con, "ALTER TABLE users ADD COLUMN `supervisor_email` VARCHAR(255) NULL");
  }
  function valid_email($e){ return filter_var($e, FILTER_VALIDATE_EMAIL) !== false; }
  $emails = array($coauth1email,$coauth2email,$coauth3email,$coauth4email);
  for ($i=1; $i<=$coauthors_count; $i++) {
    $e = $emails[$i-1];
    if (!$e || !valid_email($e)) { echo "<script>alert('Invalid co-author email for CO-$i');</script>"; exit(); }
    if ($e === $email) { echo "<script>alert('Co-author email must differ from main author email');</script>"; exit(); }
  }

  $stmt = mysqli_prepare( $con, "select id from users where email=?" );
  mysqli_stmt_bind_param($stmt,'s',$email);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  $row = mysqli_num_rows( $res );
  if ( $row > 0 )
  {
    echo "<script>alert('Email id already exist with another account. Please try with other email id');</script>";
  } else {


    
    $hasCoEmails = true;
    foreach (array('coauth1email','coauth2email','coauth3email','coauth4email') as $c) {
      $q = mysqli_query($con, "SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='$c'");
      if (!$q || mysqli_num_rows($q) == 0) { $hasCoEmails = false; break; }
    }
    if ($hasCoEmails) {
      $stmt = mysqli_prepare( $con, "insert into users(fname,nationality,coauth1name,coauth1nationality,coauth1email,coauth1mobile,coauth2name,coauth2nationality,coauth2email,coauth2mobile,coauth3name,coauth3nationality,coauth3email,coauth3mobile,coauth4name,coauth4nationality,coauth4email,coauth4mobile,email,profession,organization,postertitle,category,password,contactno,userip,supervisor_choice,supervisor_name,supervisor_nationality,supervisor_contact,supervisor_email) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)" );
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, str_repeat('s', 31),
          $fname,$nationality,$coauth1name,$coauth1nationality,$coauth1email,$coauth1mobile,
          $coauth2name,$coauth2nationality,$coauth2email,$coauth2mobile,
          $coauth3name,$coauth3nationality,$coauth3email,$coauth3mobile,
          $coauth4name,$coauth4nationality,$coauth4email,$coauth4mobile,
          $email,$profession,$organization,$postertitle,$category,$enc_password,$contact,$userip,
          $supervisor_choice,$supervisor_name,$supervisor_nationality,$supervisor_contact,$supervisor_email
        );
      }
    } else {
      $stmt = mysqli_prepare( $con, "insert into users(fname,nationality,coauth1name,coauth1nationality,coauth2name,coauth2nationality,coauth3name,coauth3nationality,coauth4name,coauth4nationality,email,profession,organization,postertitle,category,password,contactno,userip,supervisor_choice,supervisor_name,supervisor_nationality,supervisor_contact,supervisor_email) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)" );
      if ($stmt) {
        mysqli_stmt_bind_param($stmt, str_repeat('s', 23),
          $fname,$nationality,$coauth1name,$coauth1nationality,
          $coauth2name,$coauth2nationality,
          $coauth3name,$coauth3nationality,
          $coauth4name,$coauth4nationality,
          $email,$profession,$organization,$postertitle,$category,$enc_password,$contact,$userip,
          $supervisor_choice,$supervisor_name,$supervisor_nationality,$supervisor_contact,$supervisor_email
        );
      }
    }
    $msg = $stmt ? mysqli_stmt_execute($stmt) : false;
    if (!$stmt) {
      $fallback = mysqli_prepare($con, "insert into users(fname,nationality,coauth1name,coauth1nationality,coauth1email,coauth2name,coauth2nationality,coauth2email,coauth3name,coauth3nationality,coauth3email,coauth4name,coauth4nationality,coauth4email,email,profession,organization,category,password,contactno,userip,supervisor_choice,supervisor_name,supervisor_nationality,supervisor_contact,supervisor_email) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
      if ($fallback) {
        mysqli_stmt_bind_param($fallback, str_repeat('s', 26),
          $fname,$nationality,$coauth1name,$coauth1nationality,$coauth1email,
          $coauth2name,$coauth2nationality,$coauth2email,
          $coauth3name,$coauth3nationality,$coauth3email,
          $coauth4name,$coauth4nationality,$coauth4email,
          $email,$profession,$organization,$category,$enc_password,$contact,$userip,
          $supervisor_choice,$supervisor_name,$supervisor_nationality,$supervisor_contact,$supervisor_email
        );
        $msg = mysqli_stmt_execute($fallback);
        if ($msg) {
          $upd = mysqli_prepare($con, "UPDATE users SET postertitle=? WHERE email=?");
          if ($upd) {
            mysqli_stmt_bind_param($upd, "ss", $postertitle, $email);
            @mysqli_stmt_execute($upd);
          }
        }
      }
    }
    if (!$stmt) { log_submission($email, 'DB_PREPARE_FAIL', 'Prepare failed: '.mysqli_error($con), '', 0); }
    elseif (!$msg) { log_submission($email, 'DB_EXECUTE_FAIL', 'Execute failed: '.mysqli_error($con), '', 0); }


    if ( $msg )
    {
      // if ( isset( $_POST[ 'signup' ] ) )
      // {
      //   $password = $_POST[ 'password' ];
      //   $dec_password = $password;
      //   $useremail = $_POST[ 'email' ];
        $stmt = mysqli_prepare( $con, "SELECT * FROM users WHERE email=?" );
        $num = false;
        if ($stmt) {
          mysqli_stmt_bind_param($stmt,'s',$email);
          mysqli_stmt_execute($stmt);
          $ret = mysqli_stmt_get_result($stmt);
          $num = $ret ? mysqli_fetch_array($ret) : false;
        }
        if (!$num) {
          $esc = mysqli_real_escape_string($con, $email);
          $qq = mysqli_query($con, "SELECT * FROM users WHERE email='$esc' LIMIT 1");
          $num = $qq ? mysqli_fetch_array($qq) : false;
        }

        if ( $num )
        {
          $refId = $num[ 'id' ];
          $committee = "ICPM@reg-sys.com";
          $cmsub = "Registration: Ref #$refId - $fname";
          $cmbody = "A new registration has been received.\nReference: $refId\nAuthor Email: $email\n";
        $abres = send_mail_with_attachment_ex($committee, $cmsub, $cmbody, null, '', "ICPM <ICPM@reg-sys.com>", $email);
        $abok = $abres[0];
        $usersub = "Registration Confirmation - Ref #$refId";
        $userbody = "Thank you for your registration.\nReference: $refId\n";
        $ures = send_mail_with_attachment_ex($email, $usersub, $userbody, null, '', "ICPM <ICPM@reg-sys.com>");
        $uok = $ures[0];
        $footerImg = "https://reg-sys.com/icpm2026/images/icpm-logo.png";
        $headers = "From: ICPM@reg-sys.com\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=utf-8\r\n";
        $coList = array(
          array($coauth1name, $coauth1email),
          array($coauth2name, $coauth2email),
          array($coauth3name, $coauth3email),
          array($coauth4name, $coauth4email)
        );
        $sentCoauthors = array();
        for ($i=0; $i<count($coList); $i++) {
          $cn = isset($coList[$i][0]) ? trim($coList[$i][0]) : '';
          $ce = isset($coList[$i][1]) ? trim($coList[$i][1]) : '';
          if ($ce !== '' && filter_var($ce, FILTER_VALIDATE_EMAIL)) {
            $suffix = 'CO'.($i+1);
            $qrData = $refId.'-'.$suffix;
            $subject = "ICPM Scientific Team Member Access - ".$suffix." - ".htmlspecialchars($fname, ENT_QUOTES, 'UTF-8');
            $message = '<div style="font-family:Arial,Helvetica,sans-serif;color:#111;line-height:1.6">
<p>Hello ' . htmlspecialchars($cn !== '' ? $cn : 'Team Member', ENT_QUOTES, 'UTF-8') . ',</p>
<p>You have been added as a team member for the ICPM Scientific Competition.</p>
<p><strong>Team Leader:</strong> ' . htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') . '</p>
<p><strong>Competition Category:</strong> ' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '</p>
<p><strong>Organization:</strong> ' . htmlspecialchars($organization, ENT_QUOTES, 'UTF-8') . '</p>
<p><strong>Login to your account:</strong><br>
<a href="https://reg-sys.com/icpm2026/regnew" target="_blank" rel="noopener">https://reg-sys.com/icpm2026/regnew</a></p>
<p><strong>Credentials</strong><br>
Email: ' . htmlspecialchars($ce, ENT_QUOTES, 'UTF-8') . '<br>
Password: 1234<br>
Registration Number: ' . htmlspecialchars($refId, ENT_QUOTES, 'UTF-8') . '</p>
<p><img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . htmlspecialchars($qrData, ENT_QUOTES, 'UTF-8') . '" alt="QR ' . htmlspecialchars($qrData, ENT_QUOTES, 'UTF-8') . '"></p>
<hr style="border:none;border-top:1px solid #eee;margin:16px 0">
<div style="text-align:center">
<img src="' . $footerImg . '" alt="ICPM" width="200" height="78" style="display:inline-block">
</div>
</div>';
            @mail($ce, $subject, $message, $headers);
            $sentCoauthors[] = array('name'=>$cn !== '' ? $cn : 'Team Member', 'email'=>$ce, 'suffix'=>$suffix);
          }
        }
        $mainSubject = "ICPM Scientific Registration Confirmation - Ref #".$refId;
        $mainMessage = '<div style="font-family:Arial,Helvetica,sans-serif;color:#111;line-height:1.6">
<p>Hello ' . htmlspecialchars($fname, ENT_QUOTES, 'UTF-8') . ',</p>
<p>Your registration has been completed.</p>
<p><strong>Login:</strong> <a href="https://reg-sys.com/icpm2026/regnew" target="_blank" rel="noopener">https://reg-sys.com/icpm2026/regnew/</a></p>
<p><strong>Credentials</strong><br>
Email: ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '<br>
Password: ' . htmlspecialchars($password, ENT_QUOTES, 'UTF-8') . '<br>
Registration Number: ' . htmlspecialchars($refId, ENT_QUOTES, 'UTF-8') . '</p>
<p><img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . htmlspecialchars($refId, ENT_QUOTES, 'UTF-8') . '" alt="QR ' . htmlspecialchars($refId, ENT_QUOTES, 'UTF-8') . '"></p>
';
        if (!empty($sentCoauthors)) {
          $mainMessage .= '<p>Team Member emails sent:</p><ul>';
          foreach ($sentCoauthors as $sc) {
            $mainMessage .= '<li>' . htmlspecialchars($sc['suffix'], ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars($sc['name'], ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($sc['email'], ENT_QUOTES, 'UTF-8') . ')</li>';
          }
          $mainMessage .= '</ul>';
        }
        $mainMessage .= '
<hr style="border:none;border-top:1px solid #eee;margin:16px 0">
<div style="text-align:center">
<img src="' . $footerImg . '" alt="ICPM" width="200" height="78" style="display:inline-block">
</div>
</div>';
        @mail($email, $mainSubject, $mainMessage, $headers);
        $_SESSION['abstract_path'] = '';
        $_SESSION['abstract_name'] = '';
        $_SESSION['email_committee_sent'] = $abok ? true : false;
        $_SESSION['email_user_sent'] = $uok ? true : false;
        log_submission($email, ($abok && $uok)?'SUCCESS':'PARTIAL', ($abok&&$uok)?'Emails sent':'Email error', '', $refId);
        $extra = "welcome.php";
        session_regenerate_id(true);
        $_SESSION[ 'signup' ] = $_POST[ 'email' ];
        $_SESSION[ 'id' ] = $num[ 'id' ];
          $_SESSION[ 'name' ] = $num[ 'fname' ];
          $_SESSION[ 'fullname' ] = $_SESSION['name'];
          $_SESSION[ 'slname' ] = '';
  		      $_SESSION[ 'snationality' ] = $num[ 'nationality' ];
          $_SESSION[ 'coauth1name' ] = $num[ 'coauth1name' ];
  		      $_SESSION[ 'scoauth1nationality' ] = $num[ 'coauth1nationality' ];
          $_SESSION[ 'coauth2name' ] = $num[ 'coauth2name' ];
    		  $_SESSION[ 'scoauth2nationality' ] = $num[ 'coauth2nationality' ];
    		  $_SESSION[ 'coauth3name' ] = $num[ 'coauth3name' ];
    		  $_SESSION[ 'scoauth3nationality' ] = $num[ 'coauth3nationality' ];
          $_SESSION[ 'coauth4name' ] = $num[ 'coauth4name' ];
    		  $_SESSION[ 'scoauth4nationality' ] = $num[ 'coauth4nationality' ];
          $_SESSION[ 'email' ] = $num[ 'email' ];
          $_SESSION[ 'profession' ] = $num[ 'profession' ];
          $_SESSION[ 'scategory' ] = $num[ 'category' ];
          $_SESSION[ 'postertitle' ] = isset($num['postertitle']) ? $num['postertitle'] : '';
          $_SESSION[ 'contact' ] = $num[ 'contactno' ];
  		      $_SESSION[ 'userip' ] = $num[ 'userip' ];
          $_SESSION[ 'companyref' ] = $num[ 'companyref' ];
          $_SESSION[ 'paypalref' ] = $num[ 'paypalref' ];
          $host = $_SERVER[ 'HTTP_HOST' ];
          $uri = rtrim( dirname( $_SERVER[ 'PHP_SELF' ] ), '/\\' );
          $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
          $target = "$scheme://$host$uri/$extra";
          $okrow = mysqli_query($con, "SELECT id FROM users WHERE id=".$_SESSION['id']);
          if (!$okrow || mysqli_num_rows($okrow) === 0) { echo "<script>alert('Registration not completed. Please try again');</script>"; exit(); }
          $delay = 3000;
          $redirLog = date('c') . " | REDIRECT_SCHEDULED | ".$_SESSION['email']." | ".$_SESSION['id']." |  | delay=".$delay."ms\n";
          @file_put_contents(__DIR__ . '/submissions.log', $redirLog, FILE_APPEND);
          session_write_close();
          ob_end_clean();
          if (!headers_sent()) {
            @file_put_contents(__DIR__ . '/submissions.log', date('c') . " | REDIRECT_302_SENT | ".$_SESSION['email']." | ".$_SESSION['id']." | \n", FILE_APPEND);
            header("Location: ".$target, true, 302);
            exit();
          } else {
            @file_put_contents(__DIR__ . '/submissions.log', date('c') . " | REDIRECT_FALLBACK | ".$_SESSION['email']." | ".$_SESSION['id']." | \n", FILE_APPEND);
            echo '<!DOCTYPE html><html><head><meta charset="utf-8"><meta http-equiv="Cache-Control" content="no-store"><meta name="viewport" content="width=device-width, initial-scale=1"><meta http-equiv="refresh" content="'.($delay/1000).';url='.$target.'"><title>Completing Registration</title><style>.overlay{position:fixed;left:0;top:0;width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:#fff}.box{text-align:center;font-family:system-ui,Segoe UI,Arial;color:#111}.spinner{width:64px;height:64px;border:6px solid #ddd;border-top-color:#111;border-radius:50%;animation:spin 1s linear infinite;margin:18px auto}@keyframes spin{to{transform:rotate(360deg)}}.count{margin-top:8px;color:#666}</style></head><body><div class="overlay"><div class="box"><div class="spinner"></div><div>Redirecting to Welcome...</div><div class="count" id="count"></div></div></div><script>(function(){var d='.$delay.';var s=document.getElementById("count");var t=Math.floor(d/1000);function u(){s.textContent="Redirect in "+t+"s";}u();var x=setInterval(function(){t--;u();if(t<=0){clearInterval(x);}},1000);try{fetch("",{method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded"},body:"redirect_event=start&email='.urlencode($_SESSION['email']).'&uid='.$_SESSION['id'].'"});}catch(e){}setTimeout(function(){location.href="'.$target.'";},d);}());</script></body></html>';
            exit();
          }
        } else {
          log_submission($email, 'POST_INSERT_VERIFY_FAIL', 'Row not found after insert', '', 0);
          echo "<script>alert('Registration failed to save. Please try again');</script>";
          exit();
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
    $_SESSION[ 'slname' ] = '';
	  $_SESSION[ 'snationality' ] = $num[ 'nationality' ];
    $_SESSION[ 'coauth1name' ] = $num[ 'coauth1name' ];
	  $_SESSION[ 'scoauth1nationality' ] = $num[ 'coauth1nationality' ];
    $_SESSION[ 'coauth2name' ] = $num[ 'coauth2name' ];
	  $_SESSION[ 'scoauth2nationality' ] = $num[ 'coauth2nationality' ];
	      $_SESSION[ 'coauth3name' ] = $num[ 'coauth3name' ];
	  $_SESSION[ 'scoauth3nationality' ] = $num[ 'coauth3nationality' ];
          $_SESSION[ 'coauth4name' ] = $num[ 'coauth4name' ];
	  $_SESSION[ 'scoauth4nationality' ] = $num[ 'coauth4nationality' ];
          $_SESSION[ 'email' ] = $num[ 'email' ];
          $_SESSION[ 'profession' ] = $num[ 'profession' ];
          $_SESSION[ 'scategory' ] = $num[ 'category' ];
          $_SESSION[ 'contact' ] = $num[ 'contactno' ];
	  $_SESSION[ 'userip' ] = $num[ 'userip' ];
          $_SESSION[ 'companyref' ] = $num[ 'companyref' ];
          $_SESSION[ 'paypalref' ] = $num[ 'paypalref' ];
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
    $co = mysqli_prepare($con, "SELECT id,fname,category,postertitle,coauth1name,coauth1email,coauth2name,coauth2email,coauth3name,coauth3email,coauth4name,coauth4email FROM users WHERE coauth1email=? OR coauth2email=? OR coauth3email=? OR coauth4email=? LIMIT 1");
    mysqli_stmt_bind_param($co,'ssss',$useremail,$useremail,$useremail,$useremail);
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
      $extra = "index.php";
      $host = $_SERVER[ 'HTTP_HOST' ];
      $uri = rtrim( dirname( $_SERVER[ 'PHP_SELF' ] ), '/\\' );
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

	   $to = "$email";
    $subject = "ICPM Password Request";
    $footerImg = "https://reg-sys.com/icpm2026/images/icpm-logo.png";
    $message = '<div style="font-family:Arial,Helvetica,sans-serif;color:#111;line-height:1.6">
<p>Please go to the login page to access your account.</p>
<p><strong>Login:</strong> <a href="https://reg-sys.com/icpm2026/scientific/login.php" target="_blank" rel="noopener">https://reg-sys.com/icpm2026/scientific/login.php</a></p>
<p><strong>Credentials</strong><br>
Email: ' . htmlspecialchars($email) . '<br>
Password: ' . htmlspecialchars($password) . '<br>
Registration Number: ' . htmlspecialchars($id) . '</p>
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
<style>
.coauthor-group{display:none;margin-top:10px;border-top:1px solid #eee;padding-top:10px}
.supervisor-fields{margin-top:10px;border-top:1px solid #eee;padding-top:10px}
.coauthors-label{font-weight:700;font-size:20px;animation:coflash 1.2s ease-in-out infinite}
.register p{font-weight:600}
@keyframes coflash{0%{opacity:1}50%{opacity:.55}100%{opacity:1}}
.upload-status{margin-top:6px;font-size:13px;color:#c62828}
.badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 12px; font-weight: bold; margin-left: 8px; vertical-align: middle; }
.badge-leader { background-color: #ffd700; color: #000; border: 1px solid #e6c200; }
.badge-member { background-color: #69e1ffff; color: #333; border: 1px solid #ccc; }
.badge-Supervisor { background-color: #00ff62ff; color: #333; border: 1px solid #ccc; }
.toast-notice{position:fixed;right:12px;top:12px;background:#222;color:#fff;padding:10px 14px;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,0.25);z-index:9999}
.modal-notice{position:fixed;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center;z-index:9998}
.modal-notice .content{background:#fff;color:#111;padding:18px 22px;border-radius:8px;max-width:90%;box-shadow:0 2px 10px rgba(0,0,0,0.3)}
</style>
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
<div class="main">
  <h1>ICPM 2026 Registration and Login System<br>
    Scientific Competition Only</h1>
  <div class="sap_tabs">
    <div id="horizontalTab" style="display: block; width: 100%; margin: 0px;">
      <ul class="resp-tabs-list">
        <li class="resp-tab-item" aria-controls="tab_item-0" role="tab">
          <div class="top-img"><img src="images/top-note.png" alt=""/></div>
          <span>Register</span> </li>
        <li class="resp-tab-item" aria-controls="tab_item-1" role="tab">
          <div class="top-img"><img src="images/top-lock.png" alt=""/></div>
          <span>Login</span></li>
        <li class="resp-tab-item lost" aria-controls="tab_item-2" role="tab">
          <div class="top-img"><img src="images/top-key.png" alt=""/></div>
          <span>Forgot Password</span></li>
        <div class="clear"></div>
      </ul>
      <div class="resp-tabs-container">
        <div class="tab-1 resp-tab-content" aria-labelledby="tab_item-0">
          <div class="facts">
            <div class="register">
              <form name="registration" method="post" action="">
                <p>Please Make sure you Enter correct Full Names , Email , Category and Mobile Number <br></p>
                  <p> <span style="color: black">NB.: Only 5 students required from each university<br>
NB.: Preferred final year Students</span></p>
				  <hr>
                <p>Team Leader Full Name <span class="badge badge-leader">Team Leader</span></p>
                <input type="text" class="text" value=""  name="fname" required >
				        <p>Team Leader Nationality <span class="badge badge-leader">Team Leader</span></p>
                <select name="nationality" class="text" name="nationality" required>
                <?php echo getCountryOptions(); ?>
                </select>
                        <p>Team Leader Email Address (Can't register twice with same email) <span class="badge badge-leader">Team Leader</span><br>
                  <span style="color: black">* Will be used as user name to login</span></p>
                <input type="text" class="text" value="" name="email"  >
                <p>Team Leader Mobile/Phone No. <span class="badge badge-leader">Team Leader</span></p>
                <input type="tel" class="text" value="" name="contact" required pattern="[0-9]{10,15}" inputmode="numeric" title="Please enter a valid phone number (10-15 digits)">
                <small class="form-text text-muted">Please enter your phone number (digits only, 10-15 characters)</small>
				  	  <hr>
                <p>Add supervisor<span class="badge badge-Supervisor">Supervisor</span></p>
                <select class="text" name="supervisor_choice" aria-label="Add supervisor">
                  <option value="no" <?php if($supervisor_choice!='yes') echo 'selected'; ?>>No supervisor</option>
                  <option value="yes" <?php if($supervisor_choice=='yes') echo 'selected'; ?>>Add supervisor</option>
                </select>

                <div class="supervisor-group" id="supervisor-group" style="display:none;">
                  <div class="supervisor-fields">
                    <p>Supervisor Full Name <span class="badge badge-Supervisor">Supervisor</span></p>
                    <input type="text" class="text" name="supervisor_name" value="<?php echo htmlspecialchars($supervisor_name); ?>">
                    
                    <p>Supervisor Nationality <span class="badge badge-Supervisor">Supervisor</span></p>
                    <select name="supervisor_nationality" class="text">
                      <?php echo getCountryOptions($supervisor_nationality); ?>
                    </select>

                    <p>Supervisor Contact Number <span class="badge badge-Supervisor">Supervisor</span></p>
                    <input type="tel" class="text" name="supervisor_contact" pattern="[0-9]{10,15}" title="Please enter a valid phone number (10-15 digits)" value="<?php echo htmlspecialchars($supervisor_contact); ?>">
                    
                    <p>Supervisor Email <span class="badge badge-Supervisor">Supervisor</span></p>
                    <input type="email" class="text" name="supervisor_email" value="<?php echo htmlspecialchars($supervisor_email); ?>">
                  </div>
                </div>
                <hr>
                <p class="coauthors-label">Number of Team Members (excluding Leader) - required</p>
                <select class="text" name="coauthors_count" required aria-label="Number of Team Members">
                  <option value="" disabled selected>Select number of Team Members</option>
                  <option value="1">1 Team Member (Total Team Size: 2)</option>
                  <option value="2">2 Team Members (Total Team Size: 3)</option>
                  <option value="3">3 Team Members (Total Team Size: 4)</option>
                  <option value="4">4 Team Members (Total Team Size: 5)</option>
                </select>
				                  <div class="coauthor-group" id="coauthor-1">
                  <p><span class="badge badge-member">Team Member 1</span> Full Name </p>
                 <input type="text" class="text" value="" name="coauth1name" >
 					        <p>Team Member 1 Nationality </p>
                 <select name="coauth1nationality" class="text" name="coauth1nationality">
                 <?php echo getCountryOptions(); ?>
                </select>
                <p>Team Member 1 Email</p>
                <input type="email" class="text" value="" name="coauth1email" aria-label="Team Member 1 Email">
                <p>Team Member 1 Mobile Number</p>
                <div style="display:flex; gap:10px;">
                    <select class="text" style="width:140px;padding:10px;" onchange="var i=this.nextElementSibling; var v=this.value; if(v=='other'){i.value='';i.focus();}else{i.value=v;i.focus();}">
                        <?php echo getPhoneCodeOptions(); ?>
                    </select>
                    <input type="tel" class="text" value="" name="coauth1mobile" placeholder="+1234567890" pattern="\+?[0-9]{8,15}" title="International format e.g. +971501234567" style="flex:1">
                </div>
                </div>
                
				         <div class="coauthor-group" id="coauthor-2">
				         <p><span class="badge badge-member">Team Member 2</span> Full Name </p>
                 <input type="text" class="text" value="" name="coauth2name" >
  				  				   <p>Team Member 2 Nationality </p>
                 <select name="coauth2nationality" class="text" name="coauth2nationality">
                 <?php echo getCountryOptions(); ?>
                </select>
                <p>Team Member 2 Email</p>
                <input type="email" class="text" value="" name="coauth2email" aria-label="Team Member 2 Email">
                <p>Team Member 2 Mobile Number</p>
                <div style="display:flex; gap:10px;">
                    <select class="text" style="width:140px;padding:10px;" onchange="var i=this.nextElementSibling; var v=this.value; if(v=='other'){i.value='';i.focus();}else{i.value=v;i.focus();}">
                        <?php echo getPhoneCodeOptions(); ?>
                    </select>
                    <input type="tel" class="text" value="" name="coauth2mobile" placeholder="+1234567890" pattern="\+?[0-9]{8,15}" title="International format e.g. +971501234567" style="flex:1">
                </div>
                </div>
				                  <div class="coauthor-group" id="coauthor-3">
				                  <p><span class="badge badge-member">Team Member 3</span> Full Name </p>
                 <input type="text" class="text" value="" name="coauth3name" >
  				  				   <p>Team Member 3 Nationality </p>
                 <select name="coauth3nationality" class="text" name="coauth3nationality">
                 <?php echo getCountryOptions(); ?>
                </select>
                <p>Team Member 3 Email</p>
                <input type="email" class="text" value="" name="coauth3email" aria-label="Team Member 3 Email">
                <p>Team Member 3 Mobile Number</p>
                <div style="display:flex; gap:10px;">
                    <select class="text" style="width:140px;padding:10px;" onchange="var i=this.nextElementSibling; var v=this.value; if(v=='other'){i.value='';i.focus();}else{i.value=v;i.focus();}">
                        <?php echo getPhoneCodeOptions(); ?>
                    </select>
                    <input type="tel" class="text" value="" name="coauth3mobile" placeholder="+1234567890" pattern="\+?[0-9]{8,15}" title="International format e.g. +971501234567" style="flex:1">
                </div>
                </div>
				         <div class="coauthor-group" id="coauthor-4">
				         <p><span class="badge badge-member">Team Member 4</span> Full Name </p>
                 <input type="text" class="text" value="" name="coauth4name" >
  				  			<p>Team Member 4 Nationality </p>
                 <select name="coauth4nationality" class="text" name="coauth4nationality">
                 <?php echo getCountryOptions(); ?>
                </select>
                <p>Team Member 4 Email</p>
                <input type="email" class="text" value="" name="coauth4email" aria-label="Team Member 4 Email">
                <p>Team Member 4 Mobile Number</p>
                <div style="display:flex; gap:10px;">
                    <select class="text" style="width:140px;padding:10px;" onchange="var i=this.nextElementSibling; var v=this.value; if(v=='other'){i.value='';i.focus();}else{i.value=v;i.focus();}">
                        <?php echo getPhoneCodeOptions(); ?>
                    </select>
                    <input type="tel" class="text" value="" name="coauth4mobile" placeholder="+1234567890" pattern="\+?[0-9]{8,15}" title="International format e.g. +971501234567" style="flex:1">
                </div>
                </div>
				  	  <hr>
        
                <p>Profession </p>
                <select type="text" class="text" name="profession" value="" required>
                  <option></option>
                  <option value="Pharmacist">Pharmacist</option>
                  <option value="Physician">Physician</option>
                  <option value="Nurse">Nurse</option>
                  <option value="Other">Other</option>
                </select>
                <p>Category </p>
                <select type="text" class="text" name="category" value="Scientific Competition" required>
                  <option selected value="Scientific Competition">Scientific Competition</option>
                </select>
                <p>Project Title </p>
                <input type="text" class="text" value="" name="postertitle" required>
                <p>Organization - University </p>
                <input type="text" class="text" value="" name="organization" required>
                <p>Password - (Create your own password and keep it for future login) </p>
                <input type="password" value="" name="password" required>
                
                <hr />
               
	            <p>IP (Automatically Detected)</p>
				<input type="text" value="<?php echo getUserIP(); ?>" name="userip"  required readonly	 >


                <!--
								<p>

									<span>PLease Select Payment Type</span><br>

									<label for="chkYes">

									    <input type="radio" id="chkYes" name="chkPaymentType" onclick="ShowHideDiv()" required="" />

									    Paid by Organization Or Company

									</label><br>

									<label for="chkNo">

									    <input type="radio" id="chkNo" name="chkPaymentType" onclick="ShowHideDiv()" />

									    Self Payment ( Card or paypal )

									</label>

								</p>
-->

                <div id="dvPaymentType" style="display: block">
                  <p>University Name</p>
                  <input type="text" name="companyref" id="companyref" />
                </div>
                <div id="dvPaymentPaypal" style="display: none">
                  <p>* Plaese Make Payment<br>
                    * Copy Reference Number <br>
                    . Or <br>
                    * Fill with Registered Company Name into Requested Field</p>
                  <div id="smart-button-container">
                    <div style="text-align: center;">
                      <div id="paypal-button-container"></div>
                    </div>
                  </div>
                  <script src="https://www.paypal.com/sdk/js?client-id=AcFaEDQrIEflQi69flYQrw7QSirRAyXM8VeC0wBSO74PUEhzQCxFSWHFdKAZbLQZbze-17Li-r6yQyk2&currency=USD" data-sdk-integration-source="button-factory"></script>
                  <script>
										    function initPayPalButton() {
										      paypal.Buttons({
										        style: {
										          shape: 'rect',
										          color: 'black',
										          layout: 'vertical',
										          label: 'pay',

										        },

										        createOrder: function(data, actions) {
										          return actions.order.create({
										            purchase_units: [{"description":"Scientific Competition","amount":{"currency_code":"USD","value":45}}]
										          });
										        },

										        onApprove: function(data, actions) {
										          return actions.order.capture().then(function(details) {
										            alert('Transaction completed by ' + details.payer.name.given_name + '!');
										          });
										        },

										        onError: function(err) {
										          console.log(err);
										        }
										      }).render('#paypal-button-container');
										    }
										    initPayPalButton();
										  </script>
                  <p>PayPal / Payment Reference Number After Payment Success</p>
                  <input type="text" class="text" value="" name="paypalref"  >
                </div>
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
<script>
$(function(){

  var supSelect = $('select[name="supervisor_choice"]');
  var supGroup = $('#supervisor-group');
  function updateSupervisor() {
    var isSupervisor = supSelect.val() === 'yes';
    supGroup.toggle(isSupervisor);
    $('input[name="supervisor_name"]').prop('required', isSupervisor);
    $('select[name="supervisor_nationality"]').prop('required', isSupervisor);
    $('input[name="supervisor_contact"]').prop('required', isSupervisor);
    $('input[name="supervisor_email"]').prop('required', isSupervisor);
  }
  supSelect.on('change', updateSupervisor);
  updateSupervisor();

  var s = $('select[name="coauthors_count"]');
  function u(){
    var n = parseInt(s.val() || 0);
    for (var i = 1; i <= 4; i++) {
      var show = i <= n;
      $('#coauthor-' + i).toggle(show);
      $('input[name="coauth' + i + 'name"]').prop('required', show);
      $('select[name="coauth' + i + 'nationality"]').prop('required', show);
      $('input[name="coauth' + i + 'email"]').prop('required', show);
    }
  }
  s.on('change', u);
  u();
  $('form[name="registration"]').on('submit', function(e){
    if (supSelect.val() === 'yes') {
      var sName = $.trim($('input[name="supervisor_name"]').val());
      var sNat = $('select[name="supervisor_nationality"]').val();
      var sContact = $.trim($('input[name="supervisor_contact"]').val());
      var sEmail = $.trim($('input[name="supervisor_email"]').val());
      var emailRe = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
      var phoneRe = /^[0-9]{10,15}$/;

      if (!sName || !sNat || !sContact || !sEmail) {
        alert('Please fill all supervisor fields');
        e.preventDefault(); return false;
      }
      if (!emailRe.test(sEmail)) {
         alert('Please enter a valid email for Supervisor');
         e.preventDefault(); return false;
      }
      if (!phoneRe.test(sContact)) {
         alert('Please enter a valid contact number for Supervisor');
         e.preventDefault(); return false;
      }
    }
    var sv = s.val();
    if (!sv) { alert('Please select number of Team Members'); e.preventDefault(); return false; }
    var n = parseInt(sv || 0);
    for (var i = 1; i <= n; i++) {
      var name = $.trim($('input[name="coauth' + i + 'name"]').val());
      var nat = $('select[name="coauth' + i + 'nationality"]').val();
      var em = $.trim($('input[name="coauth' + i + 'email"]').val());
      var re = /^[^@\s]+@[^@\s]+\.[^@\s]+$/;
      if (!name || !nat) {
        alert('Please fill all visible Team Member fields');
        e.preventDefault();
        return false;
      }
      if (!re.test(em)) {
        alert('Please enter a valid email for Team Member ' + i);
        e.preventDefault();
        return false;
      }
    }

    return true;
  });
});
</script>
<script>
(function(){
  function findLabel(el){
    var p = el.previousElementSibling;
    while (p && !(p.tagName && (p.tagName.toLowerCase()==='p' || p.tagName.toLowerCase()==='label'))) { p = p.previousElementSibling; }
    return p;
  }
  function apply(el){
    if (!el.required) return;
    el.setAttribute('aria-required','true');
    var lab = findLabel(el);
    if (!lab) return;
    lab.classList.add('required-label');
    var star = lab.querySelector('.req-star');
    if (!star) {
      star = document.createElement('span');
      star.className = 'req-star';
      star.setAttribute('aria-hidden','true');
      star.textContent = '*';
      lab.appendChild(star);
    }
    function check(){
      var v = el.checkValidity();
      el.setAttribute('aria-invalid', v ? 'false' : 'true');
      if (v) { star.classList.add('hidden'); } else { star.classList.remove('hidden'); }
    }
    var t;
    function deb(){
      if (t) clearTimeout(t);
      t = setTimeout(check, 350);
    }
    el.addEventListener('input', deb);
    el.addEventListener('change', deb);
    el.addEventListener('blur', check);
    check();
  }
  function scan(form){
    var ctrls = form.querySelectorAll('input, select, textarea');
    ctrls.forEach(function(el){
      apply(el);
    });
    var mo = new MutationObserver(function(mut){
      mut.forEach(function(m){
        if (m.type==='attributes' && m.attributeName==='required') apply(m.target);
      });
    });
    ctrls.forEach(function(el){
      mo.observe(el, { attributes:true, attributeFilter:['required'] });
    });
  }
  document.querySelectorAll('form').forEach(scan);
})();
</script>
</html>
