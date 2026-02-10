<?php
session_start();
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');
error_reporting(E_ALL);
include("dbconnection.php");
mysqli_set_charset($con, 'utf8mb4');
if(isset($_POST['login']))
{
  $adminusername=$_POST['username'];
  $pass=md5($_POST['password']);
$stmt=mysqli_prepare($con,"SELECT * FROM admin WHERE username=? and password=?");
mysqli_stmt_bind_param($stmt,'ss',$adminusername,$pass);
mysqli_stmt_execute($stmt);
$ret=mysqli_stmt_get_result($stmt);
$num=mysqli_fetch_array($ret);
if($num>0)
{
$sid=session_id();
session_regenerate_id(true);
$extra="manage-users.php";
$_SESSION['login']=$_POST['username'];
$_SESSION['id']=$num['id'];
ob_end_clean();
header("Refresh: 3; url=".$extra);
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
.primary{background:#2563eb;color:#fff}
.secondary{background:#e5e7eb;color:#111}
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
var url="'.htmlspecialchars($extra,ENT_QUOTES,'UTF-8').'";
var c=3,t=null;
function tick(){c--;document.getElementById("count").textContent=c;if(c<=0){cleanup();location.href=url}}
function cleanup(){if(t){clearInterval(t)}var o=document.querySelector(".overlay");if(o){o.remove()}}
document.getElementById("cancel").addEventListener("click",function(){cleanup()});
document.getElementById("now").addEventListener("click",function(){cleanup();location.href=url});
document.getElementById("count").textContent=c;t=setInterval(tick,1000);setTimeout(function(){cleanup();location.href=url},3000);
</script></body></html>';
exit();
}
else
{
$_SESSION['action1']="*Invalid username or password";
$extra="index.php";
ob_end_clean();
header("Refresh: 3; url=".$extra);
echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Redirecting</title><style>
html,body{height:100%}body{margin:0}
.overlay{position:fixed;inset:0;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;z-index:9999}
.panel{background:#fff;color:#222;border-radius:12px;box-shadow:0 10px 30px rgba(0,0,0,.3);width:90%;max-width:420px;padding:24px;text-align:center}
.spinner{width:48px;height:48px;border-radius:50%;border:4px solid #e5e7eb;border-top-color:#ef4444;margin:0 auto 16px;animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.msg{font-size:18px;margin:8px 0}
.count{font-size:14px;color:#555}
.actions{display:flex;gap:12px;justify-content:center;margin-top:16px;flex-wrap:wrap}
.btn{padding:10px 16px;border:none;border-radius:8px;cursor:pointer}
.primary{background:#ef4444;color:#fff}
.secondary{background:#e5e7eb;color:#111}
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
var url="'.htmlspecialchars($extra,ENT_QUOTES,'UTF-8').'";
var c=3,t=null;
function tick(){c--;document.getElementById("count").textContent=c;if(c<=0){cleanup();location.href=url}}
function cleanup(){if(t){clearInterval(t)}var o=document.querySelector(".overlay");if(o){o.remove()}}
document.getElementById("cancel").addEventListener("click",function(){cleanup()});
document.getElementById("now").addEventListener("click",function(){cleanup();location.href=url});
document.getElementById("count").textContent=c;t=setInterval(tick,1000);setTimeout(function(){cleanup();location.href=url},3000);
</script></body></html>';
exit();
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

    <title>Admin | Login</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
  </head>

  <body>
	  <div id="login-page">
	  	<div class="container">
      
	  	
		      <form class="form-login" action="" method="post">
		        <h2 class="form-login-heading">sign in now</h2>
                  <p style="color:#F00; padding-top:20px;" align="center">
                    <?php echo $_SESSION['action1'];?><?php echo $_SESSION['action1']="";?></p>
		        <div class="login-wrap">
		            <input type="text" name="username" class="form-control" placeholder="User ID" autofocus>
		            <br>
		            <input type="password" name="password" class="form-control" placeholder="Password"><br >
		            <input  name="login" class="btn btn-theme btn-block" type="submit">
		         
		        </div>
		      </form>	  	
	  	
	  	</div>
	  </div>
    <script src="assets/js/jquery.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="assets/js/jquery.backstretch.min.js"></script>
    <script>
        $.backstretch("assets/img/login-bg.jpg", {speed: 500});
    </script>


  </body>
</html>
