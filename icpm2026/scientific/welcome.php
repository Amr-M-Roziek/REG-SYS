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
                    <img src="https://reg-sys.com/icpm2026/images/icpm-logo.png" alt="ICPM 2026 Conference Logo" style="height: 40px; width: auto;">
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


            <?php if (isset($_SESSION['role']) && $_SESSION['role']==='coauthor') { ?>
            <h1> <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo $_SESSION['id'].'-'.($_SESSION['coauthor_suffix'] ?? '');?>" title="Your reference Number is <?php echo $_SESSION['id'].'-'.($_SESSION['coauthor_suffix'] ?? '');?>" /></h1>
            <?php } else { ?>
            <h1> <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo $_SESSION['id'];?>" title="Your reference Number is <?php echo $_SESSION['id'];?>" /></h1>
            <?php } ?>
            <h3>Reg No: <?php echo $_SESSION['id'];?></h3>
            <br>
                <h2 style="text-transform:uppercase"><?php echo $_SESSION['scategory'];?></h2>
            <h3>Project Title: <?php echo htmlspecialchars($_SESSION['postertitle'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
            <div>
            <p>Welcome To ICPM 2026</p>
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
                  echo '<h3>Team Members QR</h3>';
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
