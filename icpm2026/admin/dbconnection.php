<?php
$whitelist = array(
    '127.0.0.1',
    '::1',
    'localhost'
);

if(in_array($_SERVER['SERVER_NAME'] ?? 'localhost', $whitelist)){
    define('DB_SERVER','127.0.0.1');
    define('DB_USER','root');
    define('DB_PASS' ,'');
    define('DB_NAME', 'regsys_reg');
} else {
    define('DB_SERVER','localhost');
    define('DB_USER','regsys_reg');
    define('DB_PASS' ,'regsys@2025');
    define('DB_NAME', 'regsys_reg');
}

$con = null;
try {
    $con = @mysqli_connect(DB_SERVER, DB_USER, DB_PASS, DB_NAME);
} catch (Exception $e) {
    $con = false;
}

// Check connection and apply fallback without terminating
if (!$con) {
    try {
        $con = @mysqli_connect('localhost','regsys_part','regsys@2025','regsys_participant');
        if ($con) {
            mysqli_set_charset($con, 'utf8mb4');
        }
    } catch (Exception $e) {
        // Both connections failed, do nothing (will be handled by calling script)
    }
} else {
    mysqli_set_charset($con, 'utf8mb4');
}

?>
