<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php-error.log');

include "../admin/dbconnection.php";
require_once '../admin/includes/auth_helper.php';
if ($con) {
    mysqli_set_charset($con, 'utf8mb4');
}

$protocol = empty($_SERVER['HTTPS']) ? 'http' : 'https';
$domain = $_SERVER['HTTP_HOST'];
$path = dirname($_SERVER['PHP_SELF']);
$path = str_replace('\\', '/', $path);
$base_url = $protocol . "://" . $domain . rtrim($path, '/') . "/";

if (isset($_SESSION['id']) && $_SESSION['id'] > 0) {
    header("Location: " . $base_url . "main-dashboard.php");
    exit();
}

if (isset($_POST['login'])) {
    if (!$con) {
        $_SESSION['action1'] = "*Database connection failed. Please try again later.";
        $extra = $base_url . "index.php";
        header("Location: " . $extra);
        exit();
    }
    $adminusername = $_POST['username'];
    $pass = md5($_POST['password']);
    $stmt = mysqli_prepare($con, "SELECT * FROM admin WHERE username=? and password=?");
    mysqli_stmt_bind_param($stmt, 'ss', $adminusername, $pass);
    mysqli_stmt_execute($stmt);
    $ret = mysqli_stmt_get_result($stmt);
    $num = mysqli_fetch_array($ret);
    if ($num > 0) {
        $sid = session_id();
        session_regenerate_id(true);
        $extra = $base_url . "main-dashboard.php";
        $_SESSION['login'] = $_POST['username'];
        $_SESSION['id'] = $num['id'];
        ob_end_clean();
        header("Location: " . $extra);
        exit();
    } else {
        $_SESSION['action1'] = "*Invalid username or password";
        $extra = $base_url . "index.php";
        ob_end_clean();
        header("Location: " . $extra);
        exit();
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?php echo $base_url; ?>">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="Dashboard">
    <meta name="keyword" content="Dashboard, Bootstrap, Admin, Template, Theme, Responsive, Fluid, Retina">
    <title>Main Admin | Login</title>
    <link href="../admin/assets/css/bootstrap.css" rel="stylesheet">
    <link href="../admin/assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="../admin/assets/css/style.css" rel="stylesheet">
    <link href="../admin/assets/css/style-responsive.css" rel="stylesheet">
</head>
<body>
<div id="login-page">
    <div class="container">
        <form class="form-login" action="" method="post">
            <h2 class="form-login-heading">main admin sign in</h2>
            <p style="color:#F00; padding-top:20px;" align="center">
                <?php echo isset($_SESSION['action1']) ? $_SESSION['action1'] : ''; ?><?php $_SESSION['action1'] = ""; ?>
            </p>
            <div class="login-wrap">
                <input type="text" name="username" class="form-control" placeholder="User ID" autofocus>
                <br>
                <input type="password" name="password" class="form-control" placeholder="Password"><br>
                <input name="login" class="btn btn-theme btn-block" type="submit">
            </div>
        </form>
    </div>
</div>
<script src="../admin/assets/js/jquery.js"></script>
<script src="../admin/assets/js/bootstrap.min.js"></script>
<script type="text/javascript" src="../admin/assets/js/jquery.backstretch.min.js"></script>
<script>
    $.backstretch("../admin/assets/img/ny.jpg", {speed: 500});
</script>
</body>
</html>

