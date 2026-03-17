<?php
session_start();
require_once('dbconnection.php');

if (!function_exists('icpm_get_client_ip')) {
    function icpm_get_client_ip() {
        $xff = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? trim($_SERVER['HTTP_X_FORWARDED_FOR']) : '';
        if ($xff !== '') {
            $parts = array_map('trim', explode(',', $xff));
            if (count($parts) > 0 && $parts[0] !== '') {
                return $parts[0];
            }
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
}

if (!function_exists('icpm_audit_log')) {
    function icpm_audit_log($event, $data = array()) {
        $payload = array(
            'event' => (string)$event,
            'ts' => gmdate('c'),
            'ip' => icpm_get_client_ip(),
            'ua' => isset($_SERVER['HTTP_USER_AGENT']) ? (string)$_SERVER['HTTP_USER_AGENT'] : '',
            'path' => isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '',
            'data' => is_array($data) ? $data : array('value' => $data),
        );
        error_log('ICPM_AUDIT ' . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'audit') {
    $event = isset($_POST['event']) ? (string)$_POST['event'] : 'unknown';
    $dataRaw = isset($_POST['data']) ? (string)$_POST['data'] : '';
    $data = array();
    if ($dataRaw !== '') {
        $decoded = json_decode($dataRaw, true);
        if (is_array($decoded)) {
            $data = $decoded;
        } else {
            $data = array('raw' => $dataRaw);
        }
    }
    icpm_audit_log($event, $data);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('ok' => true));
    exit();
}

// Session Check
$uid = 0;
$accessMode = 'unknown';

// 1. Priority: Check for valid hash access (public link)
if (isset($_GET['id']) && isset($_GET['hash'])) {
    $id = intval($_GET['id']);
    $hash = $_GET['hash'];
    $secret_salt = 'ICPM2026_Secure_Salt'; // Must match admin generation
    
    if ($hash === md5($id . $secret_salt)) {
        $uid = $id;
        $accessMode = 'public_hash';
    }
}

// 2. Fallback: Check for active session if no valid hash provided
if ($uid == 0 && isset($_SESSION['id'])) {
    $uid = $_SESSION['id'];
    $accessMode = 'session';
}

if ($uid == 0) {
    // If no session and no hash, redirect to login or show error
    header("Location: index.php"); 
    exit();
}

// Fetch user from poster26 DB
$query = mysqli_query($con, "SELECT * FROM users WHERE id='$uid'");
if (!$query || mysqli_num_rows($query) == 0) {
    die("User data not found.");
}
$user = mysqli_fetch_assoc($query);

// Role Handling (Main vs Co-authors)
$role = isset($_GET['role']) ? trim($_GET['role']) : 'main';
$fullName = '';

if ($role === 'main') {
    $fullName = $user['fname'];
    if (!empty($user['lname'])) {
        $fullName .= ' ' . $user['lname'];
    }
} elseif ($role === 'co1') {
    $fullName = isset($user['coauth1name']) ? $user['coauth1name'] : '';
} elseif ($role === 'co2') {
    $fullName = isset($user['coauth2name']) ? $user['coauth2name'] : '';
} elseif ($role === 'co3') {
    $fullName = isset($user['coauth3name']) ? $user['coauth3name'] : '';
} elseif ($role === 'co4') {
    $fullName = isset($user['coauth4name']) ? $user['coauth4name'] : '';
} elseif ($role === 'co5') {
    $fullName = isset($user['coauth5name']) ? $user['coauth5name'] : '';
}

if (empty($fullName)) {
    $fullName = "Participant";
}

icpm_audit_log('certificate_view', array('uid' => (int)$uid, 'access' => $accessMode, 'role' => $role));

$refNo = $user['id'];
if ($role !== 'main') {
    $refNo .= '-' . $role;
}
$category = isset($user['category']) ? $user['category'] : 'Poster Presenter';

// Dynamic Certificate Content based on Category
// Defaulting to generic participation text
$certTitle = "Certificate of Participation";
$orgText = "A Conference Organized by ICPM";
$awardedToText = "This Certificate is awarded to"; 
$contributionText = "For successful participation and attendance at \"ICPM 2026\""; 
$confTitle = "the 14th International Conference of Pharmacy and Medicine (ICPM)"; 
$entitledText = "Entitled";
$topicText = "Building a Culture of Innovation and Technology in Healthcare";
$dateText = "Held on 20th – 22nd January 2026";
$venueText = "Venue: Sharjah Research Technology and Innovation Park UAE<br>This Program has been awarded with total of 21 CPD Credits";

$compCategory = (!empty($category) && stripos($category, 'Scientific') !== false) ? "Scientific Competition" : "Poster Competition";
$accreditationText = "ICPM 2026 " . $compCategory;

$isParticipant = (stripos($category, 'Participant') !== false);

// Logic for custom text if needed (e.g. for Poster presenters)
if (!$isParticipant && !empty($category)) {
    $awardedToText = "This Certificate is awarded to";
    $contributionText = "In gratitude for your outstanding contribution as a participant in the " . $compCategory . ".";

    $confTitle = "At the 14th International Conference of Pharmacy and Medicine";
    $venueText = "Venue at Sharjah SRTIP United Arab of Emirates";
} else {
    $awardedToText = "This Certificate has been awarded to";
    $venueText = "Venue: Sharjah Research Technology and Innovation Park UAE<br>This Program has been awarded with total of 21 CPD Credits";
}

// Verification Link
$secret_salt = 'ICPM2026_Secure_Salt';
$hash = md5($user['id'] . $secret_salt);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$verificationLink = $protocol . $_SERVER['HTTP_HOST'] . "/icpm2026/poster26/verify.php?id=" . $user['id'] . "&hash=" . $hash;
if ($role !== 'main') {
    $verificationLink .= "&role=" . $role;
}

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

        #device-gate {
            display: none;
            max-width: 1123px;
            margin: 0 auto 15px;
            padding: 14px 16px;
            border-radius: 6px;
            border: 1px solid #d1ecf1;
            background: #d1ecf1;
            color: #0c5460;
            text-align: left;
        }

        #device-gate.gate-warn {
            border-color: #ffeeba;
            background: #fff3cd;
            color: #856404;
        }

        #device-gate.gate-block {
            border-color: #f5c6cb;
            background: #f8d7da;
            color: #721c24;
        }

        #device-gate-title {
            font-weight: bold;
            margin-bottom: 6px;
        }

        #device-gate-actions {
            margin-top: 10px;
        }

        #device-gate-actions a,
        #device-gate-actions button {
            display: inline-block;
            margin-right: 10px;
            margin-top: 6px;
            padding: 8px 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        #device-gate-actions a {
            background: #0c5460;
            color: #ffffff;
        }

        #device-gate-actions a.secondary,
        #device-gate-actions button.secondary {
            background: #6c757d;
            color: #ffffff;
        }

        #device-gate-actions button {
            background: #0c5460;
            color: #ffffff;
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

        /* PARTICIPANT TEMPLATE STYLES (Used for Poster as well now) */
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

        @media max-width {
             /* Scale down for mobile view if needed, but keeping it fixed size for now */
        }
    </style>
</head>
<body>

    <div id="device-gate" role="status" aria-live="polite">
        <div id="device-gate-title"></div>
        <div id="device-gate-message"></div>
        <div id="device-gate-actions"></div>
    </div>

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
                <img src="../admin/assets/img/icpm-certified-badge.png" class="cert-logo" alt="Certified Badge">
            </div>
            
            <div id="logo-right" class="cert-element">
                 <img src="../admin/assets/img/icpm-certified-badge.png" class="cert-logo" alt="Certified Badge">
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
                « <?php echo htmlspecialchars($fullName); ?> »
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

            <!-- Signatures -->


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
        const certificateRole = "<?php echo htmlspecialchars($role, ENT_QUOTES); ?>";
        const auditEndpoint = window.location.href.split('#')[0];
        const ICPM_APP_STORE_URL = "https://apps.apple.com/ae/app/icpm/id6757741792";
        const ICPM_ANDROID_URL = "https://regsys.cloud/download.html";
        const ICPM_DEEP_LINK = "icpm://open?target=certificate&id=" + encodeURIComponent(String(userId)) + "&hash=" + encodeURIComponent(String(publicToken)) + "&role=" + encodeURIComponent(String(certificateRole));
        
        new QRCode(qrContainer, {
            text: verificationLink,
            width: 80,
            height: 80,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        function isMobileDevice() {
            const ua = navigator.userAgent || '';
            const uaDataMobile = navigator.userAgentData && typeof navigator.userAgentData.mobile === 'boolean' ? navigator.userAgentData.mobile : null;
            if (uaDataMobile !== null) return uaDataMobile;
            const isIPhone = /iPhone/i.test(ua);
            const isIPad = /iPad/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
            const isIPod = /iPod/i.test(ua);
            const isAndroid = /Android/i.test(ua);
            return isIPhone || isIPad || isIPod || isAndroid;
        }

        function isIOS() {
            const ua = navigator.userAgent || '';
            return /iPhone|iPad|iPod/i.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        }

        function isAndroid() {
            const ua = navigator.userAgent || '';
            return /Android/i.test(ua);
        }

        function postAudit(eventName, data) {
            try {
                const payload = new FormData();
                payload.append('action', 'audit');
                payload.append('event', String(eventName));
                payload.append('data', JSON.stringify(Object.assign({
                    uid: userId,
                    role: certificateRole
                }, data || {})));
                if (navigator.sendBeacon) {
                    navigator.sendBeacon(auditEndpoint, payload);
                    return;
                }
                fetch(auditEndpoint, { method: 'POST', body: payload, credentials: 'same-origin' }).catch(() => {});
            } catch (e) {}
        }

        function setGateUI(mode, title, message, actions) {
            const gate = document.getElementById('device-gate');
            const t = document.getElementById('device-gate-title');
            const m = document.getElementById('device-gate-message');
            const a = document.getElementById('device-gate-actions');

            gate.classList.remove('gate-warn', 'gate-block');
            if (mode) gate.classList.add(mode);

            t.textContent = title || '';
            m.textContent = message || '';
            a.innerHTML = '';

            (actions || []).forEach(action => {
                if (action.type === 'link') {
                    const link = document.createElement('a');
                    link.href = action.href;
                    link.target = action.target || '_blank';
                    link.rel = 'noopener';
                    link.textContent = action.label;
                    if (action.className) link.className = action.className;
                    a.appendChild(link);
                } else if (action.type === 'button') {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.textContent = action.label;
                    if (action.className) btn.className = action.className;
                    btn.addEventListener('click', action.onClick);
                    a.appendChild(btn);
                }
            });

            gate.style.display = 'block';
        }

        function attemptOpenICPMApp() {
            return new Promise(resolve => {
                let resolved = false;
                let becameHidden = false;

                const cleanup = () => {
                    document.removeEventListener('visibilitychange', onVisibilityChange, true);
                    window.removeEventListener('pagehide', onPageHide, true);
                    window.removeEventListener('blur', onBlur, true);
                };

                const finish = (result) => {
                    if (resolved) return;
                    resolved = true;
                    cleanup();
                    resolve(result);
                };

                const onVisibilityChange = () => {
                    if (document.hidden) {
                        becameHidden = true;
                        finish(true);
                    }
                };
                const onPageHide = () => {
                    becameHidden = true;
                    finish(true);
                };
                const onBlur = () => {
                    becameHidden = true;
                    finish(true);
                };

                document.addEventListener('visibilitychange', onVisibilityChange, true);
                window.addEventListener('pagehide', onPageHide, true);
                window.addEventListener('blur', onBlur, true);

                const timeoutMs = 1600;
                const timeout = setTimeout(() => {
                    clearTimeout(timeout);
                    finish(becameHidden);
                }, timeoutMs);

                try {
                    if (isIOS()) {
                        const iframe = document.createElement('iframe');
                        iframe.style.display = 'none';
                        iframe.src = ICPM_DEEP_LINK;
                        document.body.appendChild(iframe);
                        setTimeout(() => {
                            try { document.body.removeChild(iframe); } catch (e) {}
                        }, 1000);
                        window.location.href = ICPM_DEEP_LINK;
                    } else {
                        window.location.href = ICPM_DEEP_LINK;
                    }
                } catch (e) {
                    finish(false);
                }
            });
        }

        const gateState = {
            appVerified: false,
            isMobile: isMobileDevice()
        };

        function applyDesktopRestriction(reason) {
            setGateUI(
                'gate-block',
                'Certificate download is restricted to mobile devices',
                'For security reasons, certificate management is only available on the ICPM mobile app. Please open this link on your phone to view and download your certificate.',
                [
                    { type: 'link', href: ICPM_APP_STORE_URL, label: 'Install ICPM (iOS)' },
                    { type: 'link', href: ICPM_ANDROID_URL, label: 'Install ICPM (Android)' }
                ]
            );
            document.querySelectorAll('.btn-download').forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.6';
                btn.style.cursor = 'not-allowed';
            });
            postAudit('blocked_desktop', { reason: reason || 'desktop' });
        }

        function showInstallPrompt() {
            setGateUI(
                'gate-warn',
                'ICPM app required',
                'To download your certificate, please install the ICPM app and log in. After installation, return to this page and try again.',
                [
                    { type: 'link', href: ICPM_APP_STORE_URL, label: 'Install ICPM (iOS)' },
                    { type: 'link', href: ICPM_ANDROID_URL, label: 'Install ICPM (Android)' },
                    { type: 'button', label: 'Open ICPM App', className: 'secondary', onClick: () => { attemptOpenICPMApp().then(() => {}); } }
                ]
            );
        }

        function ensureMobileAndAppVerified(actionName) {
            if (!gateState.isMobile) {
                applyDesktopRestriction(actionName || 'download_attempt');
                return Promise.resolve(false);
            }
            if (gateState.appVerified) return Promise.resolve(true);
            return attemptOpenICPMApp().then(opened => {
                if (opened) {
                    gateState.appVerified = true;
                    postAudit('app_verified', { action: actionName || 'unknown' });
                    return true;
                }
                postAudit('blocked_app_not_installed', { action: actionName || 'unknown', platform: isIOS() ? 'ios' : (isAndroid() ? 'android' : 'mobile') });
                showInstallPrompt();
                return false;
            });
        }

        if (!gateState.isMobile) {
            applyDesktopRestriction('page_view');
        }

        window.downloadPDF = function() {
            ensureMobileAndAppVerified('download_pdf').then(allowed => {
                if (!allowed) return;
                postAudit('download_pdf_allowed', {});
                doDownloadPDF();
            });
        };

        function doDownloadPDF() {
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
        }

        window.emailPDF = function() {
            ensureMobileAndAppVerified('email_pdf').then(allowed => {
                if (!allowed) return;
                postAudit('email_pdf_allowed', {});
                doEmailPDF();
            });
        };

        function doEmailPDF() {
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
                formData.append('role', '<?php echo $role; ?>');
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
        }
    </script>
</body>
</html>
