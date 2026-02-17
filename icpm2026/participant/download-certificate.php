<?php
session_start();
require_once('dbconnection.php');
require_once('../session_manager.php');

// Session Check
$uid = 0;

// 1. Priority: Check for valid hash access (public link)
if (isset($_GET['id']) && isset($_GET['hash'])) {
    $id = intval($_GET['id']);
    $hash = $_GET['hash'];
    $secret_salt = 'ICPM2026_Secure_Salt'; // Must match admin generation
    
    if ($hash === md5($id . $secret_salt)) {
        $uid = $id;
    }
}

// 2. Fallback: Check for active session if no valid hash provided
if ($uid == 0) {
    $session_status = check_session_validity($con);
    if ($session_status === 'valid') {
        $uid = $_SESSION['id'];
    }
}

if ($uid == 0) {
    header("Location: login.php");
    exit();
}

// Helper function to safely fetch user (Robust DB Connection)
if (!function_exists('fetchUser')) {
    function fetchUser($con, $dbName, $userId) {
        $result = null;
        try {
            if (@mysqli_select_db($con, $dbName)) {
                $q = mysqli_query($con, "SELECT * FROM users WHERE id='$userId'");
                if ($q && mysqli_num_rows($q) > 0) {
                    $result = mysqli_fetch_assoc($q);
                }
            }
            
            if (
                !$result &&
                ($dbName === 'regsys_participant' || $dbName === 'regsys_reg' || $dbName === 'regsys_poster26' || $dbName === 'regsys_poster')
            ) {
                if ($dbName === 'regsys_participant') {
                    $targetUser = 'regsys_part';
                } elseif ($dbName === 'regsys_reg') {
                    $targetUser = 'regsys_reg';
                } else {
                    $targetUser = 'regsys_poster';
                }
                $targetPass = 'regsys@2025';
                
                $envHost = getenv('DB_HOST');
                $host = $envHost ? $envHost : 'localhost';

                $whitelist = array('127.0.0.1','::1','localhost');
                if (in_array($_SERVER['SERVER_NAME'] ?? 'localhost', $whitelist)) {
                    $targetUser = 'root';
                    $targetPass = '';
                    $host = '127.0.0.1';
                }
                
                $con2 = false;
                try {
                    $con2 = @mysqli_connect($host, $targetUser, $targetPass, $dbName);
                } catch (Exception $e) {}

                if (!$con2) {
                    try { $con2 = @mysqli_connect('127.0.0.1', 'root', '', $dbName); } catch (Exception $e) {}
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
        } catch (Exception $e) {}
        return $result;
    }
}

// 1. Check current DB (participant)
$user_query = mysqli_query($con, "SELECT * FROM users WHERE id='$uid'");
$user = mysqli_fetch_array($user_query);

// 2. Check other DBs if not found
if (!$user) {
    $user = fetchUser($con, 'regsys_reg', $uid);
}
if (!$user) {
    $user = fetchUser($con, 'regsys_participant', $uid);
}
if (!$user) {
    $user = fetchUser($con, 'regsys_poster26', $uid);
}
if (!$user) {
    $user = fetchUser($con, 'regsys_poster', $uid);
}

if (!$user) {
    echo "User data not found.";
    exit();
}

// Prepare dynamic data
$fullName = $user['fname'] . ' ' . $user['lname'];
$refNo = $user['id'];
$category = isset($user['category']) ? $user['category'] : '';

// Dynamic Certificate Content based on Category
$certTitle = "Certificate of Participation";
$orgText = "A Conference Organized by ICPM";
$awardedToText = "This Certificate is awarded to"; // Default
$contributionText = "For successful participation and attendance at \"ICPM 2026\""; // Default
$confTitle = "the 14th International Conference of Pharmacy and Medicine (ICPM)"; // Default
$entitledText = "Entitled";
$topicText = "Building a Culture of Innovation and Technology in Healthcare";
$dateText = "Held on 20th – 22nd January 2026";
$venueText = "Venue: Sharjah Research Technology and Innovation Park UAE<br>This Program has been awarded with total of 21 CPD Credits";
$accreditationText = "Accreditation Code EHS/CPD/26/068";

$isParticipant = (stripos($category, 'Participant') !== false);

if (!$isParticipant && !empty($category)) {
    // Custom text for Non-Participants (Speakers, Exhibitors, etc.)
    $awardedToText = "This Certificate is awarded to";
    $contributionText = "In Gratitude for the outstanding Contribution as " . htmlspecialchars($category);
    $confTitle = "At the 14th International Conference of Pharmacy and Medicine";
    $venueText = "Venue at Sharjah SRTIP United Arab of Emirates";
} else {
    // Participant specific text adjustments
    $awardedToText = "This Certificate has been awarded to";
    $venueText = "Venue: Sharjah Research Technology and Innovation Park UAE<br>This Program has been awarded with total of 11 CPD Credits";
}

// Verification Logic
$secret_salt = 'ICPM2026_Secure_Salt';
$hash = md5($user['id'] . $secret_salt);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$verificationLink = $protocol . $_SERVER['HTTP_HOST'] . "/icpm2026/verify.php?id=" . $user['id'] . "&hash=" . $hash;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Certificate - <?php echo htmlspecialchars($fullName); ?></title>
    
    <link href="admin/assets/css/bootstrap.css" rel="stylesheet">
    <link href="admin/assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    
    <!-- PDF Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- QR Code Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <style>
        body {
            font-family: 'Times New Roman', serif;
        }
        body {
            background: #f0f2f5;
            font-family: 'Open Sans', sans-serif;
            margin: 0;
            padding: 20px;
            text-align: center;
        }
        
        @media print {
            .toolbar { display: none !important; }
            body { background: white; margin: 0; padding: 0; }
            #certificate-container { box-shadow: none; margin: 0; }
        }

        .toolbar {
            margin-bottom: 20px;
        }
        
        .btn-download {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-download:hover {
            background: #2980b9;
            color: white;
        }

        .btn-back {
            background: #7f8c8d;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        
        .btn-back:hover {
            background: #95a5a6;
            color: white;
        }

        /* Certificate Container */
        #certificate-container {
            display: inline-block;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            background: white;
            position: relative;
        }

        /* A4 Landscape Dimensions: 297mm x 210mm */
        /* Screen conversion approx: 1123px x 794px at 96 DPI */
        #certificate-preview {
            width: 1123px;
            height: 794px;
            background: white;
            position: relative;
            border: 1px solid #ddd;
            overflow: hidden;
            margin: 0 auto;
            text-align: left; /* Reset text align for absolute positioning */
        }

        /* Certificate Styling */
        .cert-border {
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            bottom: 20px;
            border: 5px solid #b22222;
            pointer-events: none;
            z-index: 0;
        }
        
        .cert-inner-border {
            position: absolute;
            top: 40px;
            left: 40px;
            right: 40px;
            bottom: 40px;
            border: 2px solid #cccccc;
            pointer-events: none;
            z-index: 0;
        }
        
        .cert-element {
            position: absolute;
            text-align: center;
            min-width: 50px;
            padding: 5px;
            box-sizing: border-box;
        }
        
        .cert-logo {
            max-width: 150px;
            height: auto;
        }
        
        #logo-center .cert-logo {
            max-width: 270px;
        }

        <?php if ($isParticipant): ?>
        #logo-left { top: 40px; left: 40px; }
        #logo-center { top: 55px; left: 50%; transform: translateX(-50%); width: 3000px; }
        #logo-right { top: 40px; right: 40px; }

        #logo-left .cert-logo,
        #logo-right .cert-logo {
            max-width: 180px;
        }
        
        #org-text {
            top: 195px;
            left: 0;
            width: 100%;
            font-size: 16px;
            color: #555;
        }

        #title-header {
            top: 240px;
            left: 50%;
            transform: translateX(-50%);
            width: 520px;
            font-size: 22px;
            color: #555;
            font-weight: 400;
            background: #f5f5f5;
            border: 1px solid #dddddd;
            border-radius: 4px;
            padding: 8px 0;
        }

        #awarded-to {
            top: 290px;
            left: 0;
            width: 100%;
            font-size: 18px;
            color: #333;
        }

        #recipient-name {
            top: 330px;
            left: 0;
            width: 100%;
            font-size: 40px;
            font-weight: bold;
            font-family: 'Times New Roman', serif;
            color: #000;
        }

        #participation-text {
            top: 390px;
            left: 0;
            width: 100%;
            font-size: 16px;
            color: #333;
        }

        #conference-title {
            top: 425px;
            left: 0;
            width: 100%;
            font-size: 22px;
            font-weight: bold;
            color: #003366;
        }

        #date-text {
            top: 470px;
            left: 0;
            width: 100%;
            font-size: 16px;
            color: #333;
        }

        #venue-text {
            top: 505px;
            left: 0;
            width: 100%;
            font-size: 14px;
            color: #333;
            line-height: 1.5;
        }
        
        #accreditation-text {
            top: 545px;
            left: 0;
            width: 100%;
            font-size: 18px;
            font-weight: bold;
            color: #ff4500;
        }

        #sig-left { bottom: 60px; left: 120px; text-align: center; }
        #sig-center { display: none; }
        #icpm-stamp-right { bottom: 60px; left: 50%; transform: translateX(-50%); z-index: 1; opacity: 0.95; }
        #sig-right-img { bottom: 90px; right: 120px; text-align: center; z-index: 2; }
        #sig-right-text { bottom: 55px; right: 120px; text-align: center; z-index: 1; }

        #qr-code-container {
            bottom: 195px;
            left: 80px;
            width: 100px;
            height: 100px;
            z-index: 10;
        }

        #verification-text {
            bottom: 295px;
            left: 50px;
            width: 160px;
            font-size: 11px;
            font-weight: bold;
            color: #000;
        }

        #ref-no {
            bottom: 180px;
            left: 55px;
            width: 140px;
            font-size: 11px;
            font-weight: bold;
            text-align: center;
        }

        #entitled-text, #topic-text, #footer-logo-left, #sig-right-group { display: none; }

        <?php else: ?>
        /* --- EXHIBITOR/SPEAKER/OTHER TEMPLATE STYLES --- */
        #logo-left { top: 30px; left: 30px; width: 165px; }
        #logo-right { top: 30px; right: 30px; width: 165px; }
        
        #title-header {
            top: 50px;
            width: 100%;
            font-size: 36px;
            color: #000;
            font-weight: 400;
            text-align: center;
        }

        #org-text {
            top: 90px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            color: #000;
        }

        #awarded-to {
            top: 140px;
            width: 100%;
            font-size: 20px;
            font-weight: bold;
            color: #000;
        }

        #recipient-name {
            top: 180px;
            width: 100%;
            font-size: 48px;
            font-weight: bold;
            font-family: 'Times New Roman', serif;
            color: #e91e63; /* Pink/Red */
        }

        #participation-text {
            top: 260px;
            width: 100%;
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }

        #conference-title {
            top: 300px;
            width: 100%;
            font-size: 22px;
            font-weight: bold;
            color: #000;
        }

        #logo-center { 
            top: 350px; 
            left: 50%; 
            transform: translateX(-50%); 
            width: 120px; 
        }

        #entitled-text {
            top: 440px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            color: #000;
        }

        #topic-text {
            top: 470px;
            width: 100%;
            font-size: 20px;
            font-weight: bold;
            color: #00bcd4; /* Cyan/Blue */
        }

        #date-text {
            top: 530px;
            width: 100%;
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }

        #venue-text {
            top: 570px;
            width: 100%;
            font-size: 16px;
            font-weight: bold;
            color: #000;
        }
        
        #accreditation-text {
            display: none;
            top: 620px;
            width: 100%;
            font-size: 14px;
            color: #777;
        }

        /* Footer Elements */
        #footer-logo-left {
            bottom: 40px;
            left: 50px;
            width: 200px;
        }

        #sig-right-group {
            bottom: 40px;
            right: 50px;
            text-align: center;
        }
        
        #sig-right-img { position: relative; width: 150px; display: block; margin: 0 auto; }
        #sig-right-text { position: relative; margin-top: 5px; font-weight: bold; }
        #icpm-stamp-right { position: absolute; bottom: 20px; right: 0; width: 140px; opacity: 0.9; z-index: 1; }

        #qr-code-container {
            bottom: 50px;
            left: 300px;
            width: 80px;
            height: 80px;
            z-index: 10;
        }

        #ref-no {
            bottom: 15px;
            right: 20px;
            width: auto;
            font-size: 11px;
            color: #777;
            text-align: right;
        }

        #verification-text {
            bottom: 40px;
            left: 0;
            width: 100%;
            font-size: 11px;
            color: #555;
        }

        /* Hide elements not in this template */
        #sig-left, #sig-center { display: none; }
        <?php endif; ?>

        @media max-width {
             /* Scale down for mobile view if needed, but keeping it fixed size for now */
        }
    </style>
</head>
<body>

    <div class="toolbar">
        <a href="welcome.php" class="btn-back"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
        <button onclick="downloadPDF()" class="btn-download"><i class="fa fa-download"></i> Download PDF</button>
        <button onclick="emailPDF()" class="btn-download" style="background:#27ae60;"><i class="fa fa-envelope"></i> Email PDF</button>
    </div>

    <div id="certificate-container">
        <div id="certificate-preview">
            <div class="cert-border"></div>
            <div class="cert-inner-border"></div>

            <!-- Logos -->
            <div id="logo-left" class="cert-element">
                <?php if ($isParticipant): ?>
                <img src="../admin/assets/img/rakmhsu-logo.png" class="cert-logo" alt="RAKMHSU Logo">
                <?php else: ?>
                <img src="admin/assets/img/icpm-stamp.png" class="cert-logo" alt="Stamp 1">
                <?php endif; ?>
            </div>
            
            <div id="logo-right" class="cert-element">
                 <?php if ($isParticipant): ?>
                 <img src="../admin/assets/img/icpm-certified-badge.png" class="cert-logo" alt="Certified Badge">
                 <?php else: ?>
                 <img src="../admin/assets/img/icpm-stamp.png" class="cert-logo" alt="Stamp 2">
                 <?php endif; ?>
            </div>

            <!-- Text Content -->
            <div id="title-header" class="cert-element">
                <?php echo $certTitle; ?>
            </div>

            <div id="org-text" class="cert-element">
                <?php echo $orgText; ?>
            </div>

            <div id="awarded-to" class="cert-element">
                <?php echo $awardedToText; ?>
            </div>

            <div id="recipient-name" class="cert-element">
                <?php if ($isParticipant): ?>
                « <?php echo htmlspecialchars($fullName); ?> »
                <?php else: ?>
                <?php echo htmlspecialchars($fullName); ?>
                <?php endif; ?>
            </div>

            <div id="participation-text" class="cert-element">
                <?php echo $contributionText; ?>
            </div>

            <div id="conference-title" class="cert-element">
                <?php echo $confTitle; ?>
            </div>
            
            <div id="logo-center" class="cert-element">
                <img src="../images/icpm-logo.png" class="cert-logo" style="width: 100%;" alt="ICPM Logo">
            </div>

            <div id="entitled-text" class="cert-element">
                <?php echo $entitledText; ?>
            </div>
            
            <div id="topic-text" class="cert-element">
                <?php echo $topicText; ?>
            </div>

            <div id="date-text" class="cert-element">
                <?php echo $dateText; ?>
            </div>

            <div id="venue-text" class="cert-element">
                <?php echo $venueText; ?>
            </div>
            
            <div id="accreditation-text" class="cert-element">
                 <?php echo $accreditationText; ?>
            </div>

            <!-- Signatures (Participant) -->
            <div id="sig-left" class="cert-element">
                <img src="../admin/assets/img/sig-prof-omer.png" alt="Prof. Omer Signature" style="height: 60px; display: block; margin: 0 auto 5px;">
                <strong>Prof. Omer Eladil Abdalla Hamid</strong><br>
                RAKMHSU
            </div>

            <div id="sig-center" class="cert-element">
                 <img src="../images/icpm-logo.png" style="width: 80px; opacity: 0.8;" alt="Stamp">
            </div>

            <!-- Footer (Non-Participant) -->
            <div id="footer-logo-left" class="cert-element">
                 <img src="../images/icpm-logo.png" style="width: 100%;" alt="ICPM Logo Footer">
            </div>

            <div id="sig-right-group" class="cert-element">
                <div id="icpm-stamp-right">
                    <img src="../admin/assets/img/icpm-stamp-blue.png" style="width: 100%; height: auto;" alt="Stamp">
                </div>
                <div id="sig-right-img">
                    <img src="../admin/assets/img/dr-muneer-signature.png" alt="Signature" style="width: 100%; height: auto;">
                </div>
                <div id="sig-right-text">
                    Dr. Muneer Rayan<br>
                    <span style="font-size: 0.8em; font-weight: normal;">Al Madinah Al Dwalia Exhibitions and<br>Conference Management</span><br>
                    ICPM
                </div>
            </div>

            <?php if ($isParticipant): ?>
                 <div id="icpm-stamp-right" class="cert-element">
                     <img src="../admin/assets/img/icpm-oval-stamp.png" alt="ICPM Stamp" style="width: 220px; height: auto;">
                 </div>

                 <div id="sig-right-img" class="cert-element">
                     <img src="../admin/assets/img/sig-dr-muneer.png" alt="Signature" style="width: 150px; height: auto;">
                 </div>

                 <div id="sig-right-text" class="cert-element">
                     <div style="font-family: 'Times New Roman', serif; font-size: 12pt; color: #000;">
                         <strong>Dr. Muneer Rayan</strong><br>
                         ICPM
                     </div>
                 </div>
            <?php endif; ?>

            <div id="qr-code-container" class="cert-element"></div>

            <div id="verification-text" class="cert-element">
                CERTIFICATE VERIFICATION ONLINE
            </div>

            <div id="ref-no" class="cert-element">
                Ref No. <?php echo htmlspecialchars($refNo); ?>
            </div>
        </div>
    </div>

    <script>
        const qrContainer = document.getElementById('qr-code-container');
        const verificationLink = "<?php echo $verificationLink; ?>";
        const userId = <?php echo (int)$refNo; ?>;
        const publicToken = "<?php echo isset($_GET['hash']) ? $_GET['hash'] : $hash; ?>";
        
        new QRCode(qrContainer, {
            text: verificationLink,
            width: 80,
            height: 80,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        window.downloadPDF = function() {
            const btn = document.querySelector('.btn-download');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Generating...';
            btn.disabled = true;

            const element = document.getElementById('certificate-preview');
            
            html2canvas(element, {
                scale: 2, // Higher quality
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/jpeg', 0.9);
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('l', 'mm', 'a4'); // Landscape, mm, A4
                
                // A4 Landscape: 297mm x 210mm
                pdf.addImage(imgData, 'JPEG', 0, 0, 297, 210);
                pdf.save('Certificate_<?php echo $refNo; ?>.pdf');
                
                btn.innerHTML = originalText;
                btn.disabled = false;
            }).catch(err => {
                console.error(err);
                alert('Error generating PDF. Please try again.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        };

        window.emailPDF = function() {
            const btns = document.querySelectorAll('.btn-download');
            const emailBtn = Array.from(btns).find(b => b.innerText.includes('Email'));
            const originalText = emailBtn ? emailBtn.innerHTML : '';
            if (emailBtn) {
                emailBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';
                emailBtn.disabled = true;
            }
            const element = document.getElementById('certificate-preview');
            html2canvas(element, {
                scale: 2,
                useCORS: true,
                logging: false,
                backgroundColor: '#ffffff'
            }).then(canvas => {
                const imgData = canvas.toDataURL('image/jpeg', 0.9);
                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('l', 'mm', 'a4');
                pdf.addImage(imgData, 'JPEG', 0, 0, 297, 210);
                const pdfDataUri = pdf.output('datauristring');
                const pdfBase64 = pdfDataUri.split(',')[1];
                const formData = new FormData();
                formData.append('action', 'send_certificate');
                formData.append('uid', userId);
                formData.append('pdf_data', pdfBase64);
                formData.append('token', publicToken);
                return fetch('admin/ajax_handler.php', {
                    method: 'POST',
                    body: formData
                });
            }).then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    alert('Email sent successfully.');
                } else {
                    alert('Error: ' + result.message);
                }
            }).catch(err => {
                alert('Error sending email.');
            }).finally(() => {
                if (emailBtn) {
                    emailBtn.innerHTML = originalText;
                    emailBtn.disabled = false;
                }
            });
        };
    </script>
</body>
</html>
