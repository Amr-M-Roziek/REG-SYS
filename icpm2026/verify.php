<?php
// Prevent 500 errors by handling exceptions
error_reporting(0); // Hide errors from output, but we handle logic carefully
// error_reporting(E_ALL); ini_set('display_errors', 1); // Uncomment for debugging

require_once('dbconnection.php');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$hash = isset($_GET['hash']) ? $_GET['hash'] : '';
$secret_salt = 'ICPM2026_Secure_Salt';

$isValid = false;
$user = null;

// Helper function to safely fetch user
if (!function_exists('fetchUser')) {
    function fetchUser($con, $dbName, $userId) {
        $result = null;
        try {
            // 1. Try switching database on existing connection
            // We use @ to suppress errors if the user doesn't have permission to switch DB
            if (@mysqli_select_db($con, $dbName)) {
                $q = mysqli_query($con, "SELECT * FROM users WHERE id='$userId'");
                if ($q && mysqli_num_rows($q) > 0) {
                    $result = mysqli_fetch_assoc($q);
                }
            }
            
            // 2. Fallback: If switch failed or user not found, and we are targeting known separate DBs, try explicit connection
            // This handles cases where database users are segregated (e.g. cPanel/Shared Hosting)
            if (!$result && ($dbName === 'regsys_participant' || $dbName === 'regsys_reg')) {
                 $targetUser = '';
                 $targetPass = '';
                 // Check for Docker/Env DB Host
                 $envHost = getenv('DB_HOST');
                 $host = $envHost ? $envHost : 'localhost';

                 // Determine credentials based on DB name
                 if ($dbName === 'regsys_participant') {
                     $targetUser = 'regsys_part';
                     $targetPass = 'regsys@2025';
                 } elseif ($dbName === 'regsys_reg') {
                     $targetUser = 'regsys_reg';
                     $targetPass = 'regsys@2025';
                 }

                 // Handle local dev environment (override credentials)
                 $whitelist = array('127.0.0.1','::1','localhost');
                 if (in_array($_SERVER['SERVER_NAME'] ?? 'localhost', $whitelist)) {
                     $targetUser = 'root';
                     $targetPass = '';
                     $host = '127.0.0.1';
                 }
                 
                 // Attempt new connection
                 try {
                     $con2 = @mysqli_connect($host, $targetUser, $targetPass, $dbName);
                 } catch (Exception $e) {
                     $con2 = false;
                 }

                 // Fallback for local XAMPP with custom domain (e.g. reg-sys.com mapped to 127.0.0.1)
                 // If production credentials failed, try root/empty
                 if (!$con2) {
                     try {
                        $con2 = @mysqli_connect('127.0.0.1', 'root', '', $dbName);
                     } catch (Exception $e) {
                        $con2 = false;
                     }
                 }

                 if ($con2) {
                     mysqli_set_charset($con2, 'utf8mb4');
                     $q = mysqli_query($con2, "SELECT * FROM users WHERE id='$userId'");
                     if ($q && mysqli_num_rows($q) > 0) {
                         $result = mysqli_fetch_assoc($q);
                     }
                     mysqli_close($con2);
                 }
            }

        } catch (Exception $e) {
            // Ignore db access errors
        }
        return $result;
    }
}

if ($id > 0 && !empty($hash)) {
    // Verify Hash Integrity
    if ($hash === md5($id . $secret_salt)) {
        
        // 1. Check regsys_participant
        $user = fetchUser($con, 'regsys_participant', $id);

        // 2. Check regsys_reg (Admin DB)
        if (!$user) {
            $user = fetchUser($con, 'regsys_reg', $id);
        }

        // 3. Check icpmvibe_icpm2026 (Legacy)
        if (!$user) {
            $user = fetchUser($con, 'icpmvibe_icpm2026', $id);
        }

        if ($user) {
            $isValid = true;
        }
    }
}

// Safe data extraction
$fullName = "Unknown";
$refNo = $id;
$category = "Unknown";

if ($user) {
    $fname = isset($user['fname']) ? $user['fname'] : '';
    $lname = isset($user['lname']) ? $user['lname'] : '';
    $fullName = trim($fname . ' ' . $lname);
    
    $category = isset($user['category']) ? $user['category'] : 'Participant'; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification - ICPM 2026</title>
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container { margin-top: 50px; max-width: 600px; }
        .card { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; }
        .icon-box { font-size: 60px; margin-bottom: 20px; }
        .valid { color: #28a745; }
        .invalid { color: #dc3545; }
        h1 { font-size: 24px; margin-bottom: 10px; }
        p { color: #6c757d; }
        .details { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; text-align: left; }
        .detail-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .label { font-weight: 600; color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <?php if ($isValid): ?>
                <div class="icon-box valid"><i class="glyphicon glyphicon-ok-circle"></i></div>
                <h1 class="valid">Certificate Verified</h1>
                <p>This certificate is valid and was issued by ICPM 2026.</p>
                
                <div class="details">
                    <div class="detail-row">
                        <span class="label">Name:</span>
                        <span><?php echo htmlspecialchars($fullName); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Reference No:</span>
                        <span><?php echo htmlspecialchars($refNo); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Category:</span>
                        <span><?php echo htmlspecialchars($category); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Status:</span>
                        <span class="badge badge-success" style="background-color: #28a745;">Verified</span>
                    </div>
                </div>

            <?php else: ?>
                <div class="icon-box invalid"><i class="glyphicon glyphicon-remove-circle"></i></div>
                <h1 class="invalid">Invalid Certificate</h1>
                <p>The certificate you are trying to verify could not be found or the link is invalid.</p>
            <?php endif; ?>
            
            <div style="margin-top: 30px;">
                <a href="https://reg-sys.com/icpm2026/" class="btn btn-primary">Go to Home</a>
            </div>
        </div>
    </div>
</body>
</html>
