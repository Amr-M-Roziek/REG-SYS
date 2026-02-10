<?php
session_start();
require_once('dbconnection.php');
require_once('session_manager.php');

// 1. Session Duration Enforcement (5 Minutes)
$logout_check = can_logout();
if ($logout_check !== true) {
    $remaining_seconds = $logout_check;
    $minutes = floor($remaining_seconds / 60);
    $seconds = $remaining_seconds % 60;
    $time_str = ($minutes > 0 ? "$minutes minutes " : "") . "$seconds seconds";
    
    echo "<script>
        alert('Security Requirement: Active session must be maintained for at least 5 minutes. Please wait $time_str before signing out.'); 
        window.history.back();
    </script>";
    exit();
}

// 2. Automatic Session Termination (Cleanup)
if (isset($_SESSION['id'])) {
    clear_user_session($con, $_SESSION['id']);
}

$_SESSION['login']=="";

session_unset();
$_SESSION['action1']="You have logged out successfully..!";
?>
<script language="javascript">
document.location="index.php";
</script>
