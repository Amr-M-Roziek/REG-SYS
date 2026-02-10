<?php
ini_set('session.save_path', sys_get_temp_dir());
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');
session_start();
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
$isAjax = ($_SERVER['REQUEST_METHOD'] === 'POST') && isset($_POST['ajax']);
if ($isAjax) {
  header('Content-Type: application/json; charset=utf-8');
  $tok = isset($_POST['logout_token']) ? $_POST['logout_token'] : '';
  $ok = isset($_SESSION['logout_token']) && hash_equals($_SESSION['logout_token'], $tok) && isset($_SESSION['id']) && intval($_SESSION['id']) > 0;
  if ($ok) {
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 3600, $params['path'] ?: '/', $params['domain'] ?: '', $params['secure'], $params['httponly']);
    }
    session_unset();
    session_destroy();
    echo json_encode(array('ok'=>true));
    exit;
  } else {
    echo json_encode(array('ok'=>false,'error'=>'INVALID'));
    exit;
  }
} else {
  $_SESSION['login']="";
  session_unset();
  $_SESSION['action1']="You have logged out successfully..!";
  ?>
  <script language="javascript">document.location="index.php";</script>
  <?php
}
