<?php
require_once 'session_setup.php';
$_SESSION['login']=="";

session_unset();
$_SESSION['action1']="You have logged out successfully..!";
header("Location: index.php");
exit();
?>
