<?php
// test_session_security.php
// Run this in browser to verify logic (requires DB connection)

session_start();
require_once('dbconnection.php');
require_once('session_manager.php');

// Mock User ID
$test_user_id = 99999;
echo "<h2>Session Security Test</h2>";

// 1. Test Initialization
initialize_session_table($con);
echo "Table initialization: Done<br>";

// 2. Test Login
$_SESSION['id'] = $test_user_id;
login_user_session($con, $test_user_id);
$sess_id_1 = session_id();
echo "Login 1 (Session ID: $sess_id_1): Success<br>";

// Verify DB
$res = mysqli_query($con, "SELECT session_id FROM user_sessions WHERE user_id = $test_user_id");
$row = mysqli_fetch_assoc($res);
if ($row && $row['session_id'] === $sess_id_1) {
    echo "DB Verification: Passed<br>";
} else {
    echo "DB Verification: FAILED<br>";
}

// 3. Test Validity
$status = check_session_validity($con);
echo "Session Validity Check: " . ($status === 'valid' ? 'Passed' : 'FAILED (' . $status . ')') . "<br>";

// 4. Test 5-Minute Rule (Should FAIL logout)
$_SESSION['login_time'] = time(); // Just logged in
$logout_check = can_logout();
if ($logout_check !== true) {
    echo "5-Minute Rule Enforcement: Passed (Blocked logout, remaining: $logout_check seconds)<br>";
} else {
    echo "5-Minute Rule Enforcement: FAILED (Allowed logout too early)<br>";
}

// 5. Test 5-Minute Rule (Should ALLOW logout)
$_SESSION['login_time'] = time() - 305; // 5 mins ago
$logout_check = can_logout();
if ($logout_check === true) {
    echo "5-Minute Rule Expiration: Passed (Allowed logout)<br>";
} else {
    echo "5-Minute Rule Expiration: FAILED (Blocked logout)<br>";
}

// 6. Test Duplicate Login Prevention
// Simulate another login (which would happen in another browser, but we simulate by updating DB)
$fake_new_session_id = "new_session_" . time();
mysqli_query($con, "UPDATE user_sessions SET session_id = '$fake_new_session_id' WHERE user_id = $test_user_id");
echo "Simulated Duplicate Login (DB updated to: $fake_new_session_id)<br>";

$status = check_session_validity($con);
if ($status === 'duplicate') {
    echo "Duplicate Detection: Passed (Detected mismatch)<br>";
} else {
    echo "Duplicate Detection: FAILED (Status: $status)<br>";
}

// Cleanup
mysqli_query($con, "DELETE FROM user_sessions WHERE user_id = $test_user_id");
mysqli_query($con, "DELETE FROM user_audit_logs WHERE user_id = $test_user_id");
echo "Cleanup Done.<br>";
?>