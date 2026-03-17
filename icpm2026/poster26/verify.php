<?php
include 'dbconnection.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$hash = isset($_GET['hash']) ? $_GET['hash'] : '';
$role = isset($_GET['role']) ? trim($_GET['role']) : 'main';
$secret_salt = 'ICPM2026_Secure_Salt';

$verification_status = false;
$user_data = null;
$fullName = '';
$refNo = '';

if ($id > 0 && $hash === md5($id . $secret_salt)) {
    // Check if users table exists and get user
    $query = mysqli_query($con, "SELECT * FROM users WHERE id='$id'");
    if ($query && $row = mysqli_fetch_array($query)) {
        $verification_status = true;
        $user_data = $row;
        
        // Determine Name based on Role
        if ($role === 'main') {
            $fullName = $row['fname'];
            if (!empty($row['lname'])) {
                $fullName .= ' ' . $row['lname'];
            }
        } elseif ($role === 'co1') {
            $fullName = isset($row['coauth1name']) ? $row['coauth1name'] : '';
        } elseif ($role === 'co2') {
            $fullName = isset($row['coauth2name']) ? $row['coauth2name'] : '';
        } elseif ($role === 'co3') {
            $fullName = isset($row['coauth3name']) ? $row['coauth3name'] : '';
        } elseif ($role === 'co4') {
            $fullName = isset($row['coauth4name']) ? $row['coauth4name'] : '';
        } elseif ($role === 'co5') {
            $fullName = isset($row['coauth5name']) ? $row['coauth5name'] : '';
        }
        
        if (empty($fullName)) {
            $fullName = "Participant";
        }
        
        // Determine Ref No based on Role
        $refNo = $row['id'];
        if ($role !== 'main') {
            $refNo .= '-' . $role;
        }
        
        $category = isset($row['category']) ? trim($row['category']) : '';
        $eventName = "ICPM 2026 - Poster Competition";
        if (!empty($category) && stripos($category, 'Scientific') !== false) {
            $eventName = "ICPM 2026 - Scientific Competition";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification - ICPM 2026 (Poster)</title>
    <!-- Assuming Bootstrap is available in parent directories or CDN -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>
        body { background: #f0f2f5; padding-top: 50px; font-family: 'Open Sans', sans-serif; }
        .verification-card {
            background: white;
            max-width: 600px;
            margin: 0 auto;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        .status-icon { font-size: 60px; margin-bottom: 20px; }
        .valid { color: #27ae60; }
        .invalid { color: #c0392b; }
        .detail-row {
            margin: 15px 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }
        .detail-label { font-weight: bold; color: #555; }
        .detail-value { color: #333; }
        .btn-home { background: #003366; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="verification-card">
            <?php if ($verification_status && $user_data): ?>
                <div class="status-icon valid">&#10004;</div>
                <h2 class="valid">Certificate Verified</h2>
                <p>The certificate with ID <strong><?php echo htmlspecialchars($refNo); ?></strong> is valid and authentic.</p>
                <hr>
                <div class="detail-row">
                    <span class="detail-label">Recipient Name</span>
                    <span class="detail-value"><?php echo htmlspecialchars($fullName); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Reference ID</span>
                    <span class="detail-value"><?php echo htmlspecialchars($refNo); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Issuer</span>
                    <span class="detail-value">ICPM</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Event</span>
                    <span class="detail-value"><?php echo htmlspecialchars($eventName); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Issue Date</span>
                    <span class="detail-value">January 2026</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status</span>
                    <span class="detail-value" style="color: #27ae60; font-weight: bold;">Active</span>
                </div>
            <?php else: ?>
                <div class="status-icon invalid">&#10008;</div>
                <h2 class="invalid">Verification Failed</h2>
                <p>The certificate details could not be verified. The link may be invalid or the certificate does not exist.</p>
            <?php endif; ?>
            
            <div style="margin-top: 30px;">
                <a href="https://icpm.ae" class="btn btn-home">Go to Home</a>
            </div>
        </div>
    </div>
</body>
</html>
