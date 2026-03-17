<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
require_once 'session_setup.php';
include 'dbconnection.php';

// Check session
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

// Helper function to log email status (and create table if missing)
function logEmailStatus($con, $userId, $email, $subject, $status, $error = null) {
    if (!$con) return;
    
    $email = mysqli_real_escape_string($con, $email);
    $errorStr = $error ? "'" . mysqli_real_escape_string($con, $error) . "'" : "NULL";
    
    $query = "INSERT INTO email_logs (user_id, recipient_email, subject, status, error_message) VALUES ('$userId', '$email', '$subject', '$status', $errorStr)";
    
    try {
        if (!mysqli_query($con, $query)) {
            throw new Exception(mysqli_error($con), mysqli_errno($con));
        }
    } catch (Exception $e) {
        if ($e->getCode() == 1146 || mysqli_errno($con) == 1146) { // Table doesn't exist
            $createSql = "CREATE TABLE IF NOT EXISTS email_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                recipient_email VARCHAR(191) NOT NULL,
                subject VARCHAR(255),
                status VARCHAR(50),
                error_message TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            if (mysqli_query($con, $createSql)) {
                mysqli_query($con, $query); // Retry
            }
        }
    }
}

// Helper function for HTML Email
function getHtmlEmail($user, $role = 'main') {
    // Determine Name based on Role
    $fullName = $user['fname'] . ' ' . (isset($user['lname']) ? $user['lname'] : '');
    if ($role !== 'main' && strpos($role, 'co') === 0) {
        $idx = substr($role, 2); // e.g. 1 from co1
        if (isset($user['coauth'.$idx.'name']) && !empty($user['coauth'.$idx.'name'])) {
            $fullName = $user['coauth'.$idx.'name'];
        }
    }

    // Certificate Link Generation
    $secret_salt = 'ICPM2026_Secure_Salt';
    $hash = isset($user['id']) ? md5($user['id'] . $secret_salt) : '';
    // Use poster26 specific path
    $certLink = isset($user['id']) ? 'https://reg-sys.com/icpm2026/poster26/download-certificate.php?id=' . $user['id'] . '&hash=' . $hash : '';
    if ($role !== 'main') {
        $certLink .= '&role=' . $role;
    }

    // Unique ID for footer to prevent collapsing
    $uniqueId = uniqid();

    // Standardized Footer Content (Bilingual)
    $footerNote = '
    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; font-size: 13px; color: #555; background-color: #fcfcfc; padding: 15px; border-radius: 4px;">
        <p style="margin: 0 0 10px 0;"><strong>Certificate Support / دعم الشهادات:</strong></p>
        <p style="margin: 0 0 10px 0;">If you experience any display issues with the certificate or require a name correction, please contact us via WhatsApp at <a href="https://wa.me/971529936233" style="color: #d4af37; text-decoration: none; font-weight: bold;">00971529936233</a> or email <a href="mailto:support@reg-sys.com" style="color: #d4af37; text-decoration: none; font-weight: bold;">support@reg-sys.com</a>. Kindly allow 48-72 hours for us to process your request and make the necessary corrections.</p>
        <p style="margin: 0; direction: rtl; text-align: right; font-family: Tahoma, Arial, sans-serif;">إذا واجهت أي مشاكل في عرض الشهادة أو كنت بحاجة إلى تصحيح الاسم، يرجى التواصل معنا عبر واتساب على الرقم <a href="https://wa.me/971529936233" style="color: #d4af37; text-decoration: none; font-weight: bold;">00971529936233</a> أو عبر البريد الإلكتروني <a href="mailto:support@reg-sys.com" style="color: #d4af37; text-decoration: none; font-weight: bold;">support@reg-sys.com</a>. يرجى منحنا 48-72 ساعة لمعالجة طلبك وإجراء التصحيحات اللازمة.</p>
    </div>';

    // Images are now embedded via CID
    return '<!DOCTYPE html>
    <html>
    <head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 0; }
        .container { width: 100%; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #ddd; }
        .header { background: #f8f8f8; padding: 20px; text-align: center; border-bottom: 3px solid #d4af37; }
        .content { padding: 30px 20px; }
        .footer { font-size: 12px; color: #777; padding: 20px; background: #f4f4f4; text-align: center; border-top: 1px solid #ddd; }
        .text-logo { color: #2c3e50; text-align: center; font-family: Arial, sans-serif; font-size: 22px; font-weight: bold; margin: 20px 0; }
        .app-link { text-decoration: none; display: inline-block; }
    </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <img src="https://regsys.cloud/icpm2026/images/icpm-logo.png" alt="ICPM Logo" style="max-width: 200px; height: auto; display: block; margin: 0 auto 20px;">
                <h2 class="text-logo">International Conference of Pharmacy and Medicine (ICPM)</h2>
            </div>
            <div class="content">
                <h2 style="color: #2c3e50; text-align: center; font-family: Arial, sans-serif; font-size: 22px; font-weight: bold; margin: 20px 0;">Thank you for participating at ICPM 14 - 2026</h2>
                <p>Dear <strong>' . htmlspecialchars($fullName) . '</strong>,</p>
                <p>We sincerely appreciate your participation in the 14th International Conference of pharmacy and medcine (ICPM).</p>
                <p>We are pleased to provide you with your Certificate of Attendance.</p>' .
                (!empty($certLink) ? '<p>You can download your certificate using the following link:<br><a href="' . htmlspecialchars($certLink) . '">' . htmlspecialchars($certLink) . '</a></p>' : '') . '
                <p>We hope you found the sessions insightful and valuable.</p>
                <h2 style="color: #2c3e50; text-align: center; font-family: Arial, sans-serif; font-size: 22px; font-weight: bold; margin: 20px 0;">To activate your certificate please download ICPM Mobile app and login</h2>
                
                <h3 style="color: #2c3e50; font-family: Arial, sans-serif; font-weight: bold;">Download From the App Store</h3>
                <p>For apple IOS (Iphone and IPad) <a href="https://apps.apple.com/ae/app/icpm/id6757741792">https://apps.apple.com/ae/app/icpm/id6757741792</a></p>
                <p style="text-align: center;">
                    <a href="https://apps.apple.com/ae/app/icpm/id6757741792" class="app-link">
                        <img src="https://regsys.cloud/icpm2026/images/appstore.jpg" alt="Download on the App Store" style="width: 150px; height: auto;">
                    </a>
                </p>

                <h3 style="color: #2c3e50; font-family: Arial, sans-serif; font-weight: bold;">Download For Android</h3>
                <p>For All android : <a href="https://regsys.cloud/download.html">https://regsys.cloud/download.html</a></p>
                
                <p style="text-align: center;">
                    <a href="https://regsys.cloud/download.html" class="app-link">
                        <img src="https://regsys.cloud/icpm2026/images/googleplaycomingsoon.png" alt="Get it on Google Play (Coming Soon)" style="width: 150px; height: auto;">
                    </a>
                </p>
                
                <p>accept any security messages appear</p>
                <br>
                <h2 class="text-logo">*NB: Looking to see you at ICPM 15 - 2027 <br> Date: 27,28 March 2027 <br> Venue: Dubai - UAE ( V Hotel Dubai )</h2>
                <br>
                <p>Best Regards,</p>
                <p><strong>ICPM Organizing Committee</strong></p>
                
                ' . $footerNote . '
            </div>
            <div class="footer">
                <p>&copy; 2026 International Conference of Pharmacy and Medicine . All rights reserved.</p>
                <p>This is an automated message. Please do not reply directly to this email.</p>
                <p><a href="https://icpm.ae" style="color: #d4af37; text-decoration: none;">www.icpm.ae</a></p>
                <!-- Unique: ' . $uniqueId . ' -->
            </div>
        </div>
    </body>
    </html>';
}

// Helper function for Plain Text Email
function getPlainTextEmail($user, $role = 'main') {
    $name = $user['fname'] . ' ' . (isset($user['lname']) ? $user['lname'] : '');
    if ($role !== 'main' && strpos($role, 'co') === 0) {
        $idx = substr($role, 2);
        if (isset($user['coauth'.$idx.'name']) && !empty($user['coauth'.$idx.'name'])) {
            $name = $user['coauth'.$idx.'name'];
        }
    }

    $secret_salt = 'ICPM2026_Secure_Salt';
    $hash = isset($user['id']) ? md5($user['id'] . $secret_salt) : '';
    // Use poster26 specific path
    $certLink = isset($user['id']) ? 'https://reg-sys.com/icpm2026/poster26/download-certificate.php?id=' . $user['id'] . '&hash=' . $hash : '';
    if ($role !== 'main') {
        $certLink .= '&role=' . $role;
    }
    
    return "International Conference of Pharmacy and Medicine (ICPM)

Thank you for participating at ICPM 14 - 2026

Dear $name,

We sincerely appreciate your participation in the 14th International Conference of pharmacy and medcine (ICPM).
We are pleased to provide you with your Certificate of Attendance.
You can download your certificate using the following link:
$certLink
We hope you found the sessions insightful and valuable.

To activate your certificate please download ICPM Mobile app and login

Download From the App Store:
https://apps.apple.com/ae/app/icpm/id6757741792

Download For Android:
https://regsys.cloud/download.html

*NB: Looking to see you at ICPM 15 - 2027
Date: 27,28 March 2027
Venue: Dubai - UAE ( V Hotel Dubai )

Best Regards,
ICPM Organizing Committee

--------------------------------------------------
If you experience any display issues with the certificate or require a name correction, please contact us via WhatsApp at 00971529936233 or email support@reg-sys.com. Kindly allow 48-72 hours for us to process your request and make the necessary corrections.

إذا واجهت أي مشاكل في عرض الشهادة أو كنت بحاجة إلى تصحيح الاسم، يرجى التواصل معنا عبر واتساب على الرقم 00971529936233 أو عبر البريد الإلكتروني support@reg-sys.com. يرجى منحنا 48-72 ساعة لمعالجة طلبك وإجراء التصحيحات اللازمة.
--------------------------------------------------

(c) 2026 International Conference of Pharmacy and Medicine. All rights reserved.
www.icpm.ae
";
}

// Helper function to generate MIME message (Separated for testing)
function generateMimeMessage($user, $attachmentPath, $attachmentName, $extraAttachments = [], $role = 'main') {
    $fromAddr = getenv('SMTP_FROM') ?: 'ICPM@reg-sys.com';
    $fromName = getenv('SMTP_FROM_NAME') ?: 'ICPM 2026';
    
    // Boundaries
    $uniq = md5(uniqid(time(), true));
    $boundaryMixed = "ICPM_Mixed_" . $uniq;
    $boundaryAlt = "ICPM_Alt_" . $uniq;
    $eol = "\r\n";
    
    // Headers
    $headers = "From: $fromName <$fromAddr>" . $eol;
    $headers .= "MIME-Version: 1.0" . $eol;
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundaryMixed\"" . $eol;
    
    // 1. Start Mixed
    $message = "--" . $boundaryMixed . $eol;
    
    // 2. Start Alternative (Plain + HTML)
    $message .= "Content-Type: multipart/alternative; boundary=\"$boundaryAlt\"" . $eol . $eol;
    
    // 2a. Plain Text
    $message .= "--" . $boundaryAlt . $eol;
    $message .= "Content-Type: text/plain; charset=\"UTF-8\"" . $eol;
    $message .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
    $message .= getPlainTextEmail($user, $role) . $eol . $eol;
    
    // 2b. HTML Content
    $message .= "--" . $boundaryAlt . $eol;
    $message .= "Content-Type: text/html; charset=\"UTF-8\"" . $eol;
    $message .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
    $message .= getHtmlEmail($user, $role) . $eol . $eol;
    
    // End Alternative
    $message .= "--" . $boundaryAlt . "--" . $eol;
    
    // 3. Attachments (PDFs)
    $filesToAttach = [];
    if (!empty($attachmentPath) && file_exists($attachmentPath)) {
        $filesToAttach[] = ['path' => $attachmentPath, 'name' => $attachmentName];
    }
    if (!empty($extraAttachments)) {
        foreach ($extraAttachments as $f) {
            if (is_array($f) && isset($f['path']) && file_exists($f['path'])) {
                $filesToAttach[] = $f;
            } elseif (is_string($f) && file_exists($f)) {
                $filesToAttach[] = ['path' => $f, 'name' => basename($f)];
            }
        }
    }

    foreach ($filesToAttach as $file) {
        $path = $file['path'];
        $name = $file['name'];
        if (file_exists($path)) {
            $fileContent = file_get_contents($path);
            $encodedContent = chunk_split(base64_encode($fileContent));
            $mimeType = mime_content_type($path) ?: 'application/octet-stream';
            
            $message .= "--" . $boundaryMixed . $eol;
            $message .= "Content-Type: $mimeType; name=\"$name\"" . $eol;
            $message .= "Content-Transfer-Encoding: base64" . $eol;
            $message .= "Content-Disposition: attachment; filename=\"$name\"" . $eol . $eol;
            $message .= $encodedContent . $eol;
        }
    }
    
    // End Mixed
    $message .= "--" . $boundaryMixed . "--" . $eol;
    
    return ['headers' => $headers, 'body' => $message];
}

// Helper function for Fallback Email (mail()) with Inline Images
function sendFallbackMail($user, $attachmentPath, $attachmentName, $con = null, $extraAttachments = [], $role = 'main') {
    $to = $user['email'];
    $subject = 'Your ICPM 2026 Certificate';
    
    // Generate the MIME content
    $mime = generateMimeMessage($user, $attachmentPath, $attachmentName, $extraAttachments, $role);
    
    // Send - Suppress warnings to avoid JSON corruption
    $sent = @mail($to, $subject, $mime['body'], $mime['headers']);
    
    if ($sent) {
        if ($con && isset($user['id'])) {
             logEmailStatus($con, $user['id'], $to, 'Certificate Fallback', 'success');
        }
        return ['status' => 'success', 'message' => 'Email sent via mail()'];
    } else {
        $error = error_get_last()['message'] ?? 'Unknown error';
        if ($con && isset($user['id'])) {
             logEmailStatus($con, $user['id'], $to, 'Certificate Fallback', 'failure', $error);
        }
        return ['status' => 'error', 'message' => 'Failed to send via mail(): ' . $error];
    }
}

if ($action == 'save_template') {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $data = mysqli_real_escape_string($con, $_POST['data']);
    
    if (empty($name) || empty($data)) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Name and data required']);
        exit;
    }
    
    // Ensure table exists
    $tableCheck = mysqli_query($con, "SHOW TABLES LIKE 'certificate_templates'");
    if (mysqli_num_rows($tableCheck) == 0) {
        $createSql = "CREATE TABLE certificate_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(191) NOT NULL UNIQUE,
            data LONGTEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        if (!mysqli_query($con, $createSql)) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Table creation failed: ' . mysqli_error($con)]);
            exit;
        }
    }
    
    // Check if exists to update or insert
    $checkQuery = mysqli_query($con, "SELECT id FROM certificate_templates WHERE name='$name'");
    if (mysqli_num_rows($checkQuery) > 0) {
        // Update
        $query = mysqli_query($con, "UPDATE certificate_templates SET data='$data' WHERE name='$name'");
        $msg = 'Template updated successfully';
    } else {
        // Insert
        $query = mysqli_query($con, "INSERT INTO certificate_templates (name, data) VALUES ('$name', '$data')");
        $msg = 'Template saved successfully';
    }
    
    if ($query) {
        ob_clean();
        echo json_encode(['status' => 'success', 'message' => $msg]);
    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }
    
} elseif ($action == 'delete_template') {
    $id = intval($_POST['id']);
    $query = mysqli_query($con, "DELETE FROM certificate_templates WHERE id='$id'");
    
    if ($query) {
        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'Template deleted successfully']);
    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }

} elseif ($action == 'get_templates') {
    $query = mysqli_query($con, "SELECT id, name, created_at FROM certificate_templates ORDER BY created_at DESC");
    $templates = [];
    if ($query) {
        while ($row = mysqli_fetch_assoc($query)) {
            $templates[] = $row;
        }
    }
    ob_clean();
    echo json_encode(['status' => 'success', 'data' => $templates]);

} elseif ($action == 'load_template_by_name') {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $query = mysqli_query($con, "SELECT * FROM certificate_templates WHERE name='$name' LIMIT 1");
    $template = mysqli_fetch_assoc($query);
    
    if ($template) {
        ob_clean();
        echo json_encode(['status' => 'success', 'data' => $template]);
    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Template not found']);
    }

} elseif ($action == 'get_default_template') {
    // Priority: 'Other_Certificates (2026-02-11 05:29:56)' -> 'Final-CME' -> 'Final' -> 'Default' -> First available
    $targetTemplate = 'Other_Certificates (2026-02-11 05:29:56)';
    $names = ["'$targetTemplate'", "'Final-CME'", "'Final'", "'Default'"];
    $namesStr = implode(',', $names);
    
    $query = mysqli_query($con, "SELECT * FROM certificate_templates WHERE name IN ($namesStr) ORDER BY FIELD(name, $namesStr) LIMIT 1");
    $template = mysqli_fetch_assoc($query);
    
    if (!$template) {
        // Fallback to any template
        $query = mysqli_query($con, "SELECT * FROM certificate_templates ORDER BY id ASC LIMIT 1");
        $template = mysqli_fetch_assoc($query);
    }
    
    if ($template) {
        // Apply on-the-fly fixes for specific broken templates
        if ($template['name'] === $targetTemplate) {
            $data = json_decode($template['data'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $modified = false;
                foreach ($data as &$element) {
                    // Fix Name Variable
                    // Look for the specific name mentioned by user or general patterns if lost
                    if (isset($element['content']) && (stripos($element['content'], 'Majd Masadeh') !== false)) {
                        $element['dataVariable'] = 'name';
                        $element['dataTemplate'] = '{name}';
                        // We keep content as is for now, client-side will replace it if dataVariable is present
                        $modified = true;
                    }
                    
                    // Fix QR Code Variable
                    // Look for image elements that might be the QR code
                    // Usually QR codes are images. If we find an image without a variable, assume it's the QR if there's only one or it's small
                    if (strpos($element['content'], '<img') !== false) {
                        // Check if it's likely a QR code (e.g. square-ish or just the only image)
                        // Or just force it if it's the only image
                        if (!isset($element['dataVariable']) || $element['dataVariable'] == '') {
                            $element['dataVariable'] = 'qr_code';
                            $modified = true;
                        }
                    }
                }
                if ($modified) {
                    $template['data'] = json_encode($data);
                }
            }
        }

        // HOTFIX: Generic Category Fix
        $data = json_decode($template['data'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            $modified = false;
            foreach ($data as &$element) {
                $content = strip_tags($element['content'] ?? '');
                $isCategoryVar = (isset($element['dataVariable']) && $element['dataVariable'] === 'category');
                $isSpeakerText = (stripos($content, 'Speaker') !== false);
                
                if ($isCategoryVar || $isSpeakerText) {
                    if (stripos($content, 'In Gratitude') === false) {
                        $element['content'] = 'In gratitude for your outstanding contribution as a participant in the {competition_category}.';
                        $element['dataVariable'] = 'competition_category';
                        $element['dataTemplate'] = 'In gratitude for your outstanding contribution as a participant in the {competition_category}.';
                        $modified = true;
                    } elseif (stripos($content, 'In Gratitude') !== false && stripos($content, '{competition_category}') === false) {
                        $element['content'] = 'In gratitude for your outstanding contribution as a participant in the {competition_category}.';
                        $element['dataVariable'] = 'competition_category';
                        $element['dataTemplate'] = 'In gratitude for your outstanding contribution as a participant in the {competition_category}.';
                        $modified = true;
                    }
                }
            }
            if ($modified) {
                $template['data'] = json_encode($data);
            }
        }

        ob_clean();
        echo json_encode(['status' => 'success', 'data' => $template]);
    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'No templates found']);
    }

} elseif ($action == 'load_template') {
    $id = intval($_POST['id']);
    $query = mysqli_query($con, "SELECT * FROM certificate_templates WHERE id='$id'");
    $template = mysqli_fetch_assoc($query);
    if ($template) {
        // HOTFIX: Apply same repairs as get_default_template if it's the target template
        if ($template['name'] === 'Other_Certificates (2026-02-11 05:29:56)') {
            $data = json_decode($template['data'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $modified = false;
                foreach ($data as &$element) {
                    // Fix Name
                    if (isset($element['content']) && (stripos($element['content'], 'Majd Masadeh') !== false)) {
                        $element['dataVariable'] = 'name';
                        $element['dataTemplate'] = '{name}';
                        $modified = true;
                    }
                    // Fix QR
                    if (strpos($element['content'], '<img') !== false) {
                        if (!isset($element['dataVariable']) || $element['dataVariable'] == '') {
                            $element['dataVariable'] = 'qr_code';
                            $modified = true;
                        }
                    }
                }
                if ($modified) {
                    $template['data'] = json_encode($data);
                }
            }
        }
        // HOTFIX: Replace hardcoded "Speaker" with dynamic category variable
        $data = json_decode($template['data'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            $modified = false;
            foreach ($data as &$element) {
                // HOTFIX: Ensure "Speaker" or existing "{category}" variable gets the static prefix
                $content = strip_tags($element['content'] ?? '');
                $isCategoryVar = (isset($element['dataVariable']) && $element['dataVariable'] === 'category');
                $isSpeakerText = (stripos($content, 'Speaker') !== false);
                
                if ($isCategoryVar || $isSpeakerText) {
                    // Prevent double prefixing if already applied
                    if (stripos($content, 'In Gratitude') === false) {
                        $element['content'] = 'In gratitude for your outstanding contribution as a participant in the {competition_category}.';
                        $element['dataVariable'] = 'competition_category';
                        $element['dataTemplate'] = 'In gratitude for your outstanding contribution as a participant in the {competition_category}.';
                        $modified = true;
                    } elseif (stripos($content, 'In Gratitude') !== false && stripos($content, '{competition_category}') === false) {
                        // Update old phrasing or force dynamic template
                        $element['content'] = 'In gratitude for your outstanding contribution as a participant in the {competition_category}.';
                        $element['dataVariable'] = 'competition_category';
                        $element['dataTemplate'] = 'In gratitude for your outstanding contribution as a participant in the {competition_category}.';
                        $modified = true;
                    }
                }
            }
            if ($modified) {
                $template['data'] = json_encode($data);
            }
        }
        ob_clean();
        echo json_encode(['status' => 'success', 'data' => $template]);
    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Template not found']);
    }

} elseif ($action == 'update_certificate_status') {
    $uid = isset($_POST['user_id']) ? intval($_POST['user_id']) : intval($_POST['uid']);
    $status = intval($_POST['status']);
    
    try {
        $stmt = mysqli_prepare($con, "UPDATE users SET certificate_sent=? WHERE id=?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ii', $status, $uid);
            if (mysqli_stmt_execute($stmt)) {
                ob_clean();
                echo json_encode(['status' => 'success', 'message' => 'Status updated']);
            } else {
                throw new Exception(mysqli_stmt_error($stmt));
            }
        } else {
            throw new Exception(mysqli_error($con));
        }
    } catch (Exception $e) {
        // Check for missing column error (1054)
        if (strpos($e->getMessage(), "Unknown column 'certificate_sent'") !== false || $e->getCode() == 1054) {
             // Self-healing: Add column
             mysqli_query($con, "ALTER TABLE users ADD COLUMN certificate_sent INT DEFAULT 0");
             // Retry
             $stmt = mysqli_prepare($con, "UPDATE users SET certificate_sent=? WHERE id=?");
             if ($stmt) {
                 mysqli_stmt_bind_param($stmt, 'ii', $status, $uid);
                 if (mysqli_stmt_execute($stmt)) {
                     ob_clean();
                     echo json_encode(['status' => 'success', 'message' => 'Status updated (Schema fixed)']);
                     exit;
                 }
             }
        }
        
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
    }
    exit;

} elseif ($action == 'send_certificate') {
    $uid = intval($_POST['uid']);
    $role = isset($_POST['role']) ? trim($_POST['role']) : 'main';
    $pdfData = $_POST['pdf_data'];
    
    if ($uid == 0 || empty($pdfData)) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
        exit;
    }
    
    // Get User Email
    // Select specific columns to avoid large blobs
    $columns = "id, fname, lname, email, contactno, 
                coauth1name, coauth1email, coauth1nationality,
                coauth2name, coauth2email, coauth2nationality,
                coauth3name, coauth3email, coauth3nationality,
                coauth4name, coauth4email, coauth4nationality,
                coauth5name, coauth5email, coauth5nationality,
                supervisor_name, supervisor_email, supervisor_contact, supervisor_nationality,
                profession, organization, postertitle, category, posting_date, source_system,
                abstract_filename, companyref, paypalref, userip, password";
    $userQuery = mysqli_query($con, "SELECT $columns FROM users WHERE id='$uid'");
    $user = mysqli_fetch_assoc($userQuery);
    
    if (!$user) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    // Role-based Email Selection
    require_once __DIR__ . '/email_helper.php';

    // Override Email Logic
    $overrideEmail = isset($_POST['override_email']) ? trim($_POST['override_email']) : '';
    $useOverride = false;
    if (!empty($overrideEmail) && filter_var($overrideEmail, FILTER_VALIDATE_EMAIL)) {
        $useOverride = true;
    } elseif (!empty($overrideEmail)) {
         ob_clean();
         echo json_encode(['status' => 'error', 'message' => 'Invalid override email address']);
         exit;
    }

    $emailResult = get_recipient_email($user, $role);

    if ($useOverride) {
        $user['email'] = $overrideEmail;
    } else {
        if ($emailResult['error']) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => $emailResult['error']]);
            exit;
        }
        $user['email'] = $emailResult['email'];
    }
    
    // Save PDF temporarily
    $pdfContent = base64_decode($pdfData);
    $fileName = 'Certificate_' . $uid . '.pdf';
    $tempPath = sys_get_temp_dir() . '/' . $fileName;
    file_put_contents($tempPath, $pdfContent);
    
    // Send Email using Fallback (Standardized)
    // PHPMailer code removed to enforce fallback
    $result = sendFallbackMail($user, $tempPath, $fileName, $con, [], $role);
    
    if ($result['status'] == 'success') {
        // Transactional Update
        mysqli_begin_transaction($con);
        try {
            $updateQuery = "UPDATE users SET certificate_sent=1 WHERE id='$uid'";
            if (!mysqli_query($con, $updateQuery)) {
                throw new Exception(mysqli_error($con), mysqli_errno($con));
            }
            mysqli_commit($con);
            ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Email sent (fallback)']);
        } catch (Exception $e) {
            mysqli_rollback($con);
            
            // Self-healing: Check if error is due to missing column (1054)
            if ($e->getCode() == 1054 || mysqli_errno($con) == 1054) {
                $alterQuery = "ALTER TABLE users ADD COLUMN certificate_sent TINYINT(1) DEFAULT 0";
                if (mysqli_query($con, $alterQuery)) {
                    // Retry update
                    mysqli_query($con, "UPDATE users SET certificate_sent=1 WHERE id='$uid'");
                    ob_clean();
                    echo json_encode(['status' => 'success', 'message' => 'Email sent (fallback) & Schema updated']);
                } else {
                     ob_clean();
                     echo json_encode(['status' => 'error', 'message' => 'Schema update failed: ' . mysqli_error($con)]);
                }
            } else {
                ob_clean();
                echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
            }
        }
    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Email failed: ' . $result['message']]);
    }
    
    // Cleanup
    if (file_exists($tempPath)) {
        unlink($tempPath);
    }

} elseif ($action == 'get_user_data') {
    $uid = intval($_POST['uid']);
    $query = mysqli_query($con, "SELECT id, fname, lname, email, category, organization, profession FROM users WHERE id='$uid'");
    $user = mysqli_fetch_assoc($query);
    if ($user) {
        // Verification Link (needed for QR)
        $secret_salt = 'ICPM2026_Secure_Salt';
        $hash = md5($user['id'] . $secret_salt);
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $user['verificationLink'] = "https://regsys.cloud/icpm2026/poster26/verify.php?id=" . $user['id'] . "&hash=" . $hash;
        
        // Determine Competition Category Text
        // Default to 'Poster Competition' unless 'Scientific' is detected
        $compCategory = 'Poster Competition'; 
        if (!empty($user['category']) && stripos($user['category'], 'Scientific') !== false) {
            $compCategory = 'Scientific Competition';
        }
        $user['competition_category'] = $compCategory;

        ob_clean();
        echo json_encode(['status' => 'success', 'data' => $user]);
    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
    }

} elseif ($action == 'verify_admin_password') {
    $adminPassword = isset($_POST['admin_password']) ? $_POST['admin_password'] : '';
    if ($adminPassword === '') {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Admin password required']);
        exit;
    }
    $adminId = intval($_SESSION['id']);
    $hash = md5($adminPassword);
    $adminStmt = mysqli_prepare($con, "SELECT id FROM admin WHERE id=? AND password=? AND (access_scope='poster' OR access_scope='both')");
    if ($adminStmt) {
        mysqli_stmt_bind_param($adminStmt, 'is', $adminId, $hash);
        mysqli_stmt_execute($adminStmt);
        $adminRes = mysqli_stmt_get_result($adminStmt);
        $adminRow = mysqli_fetch_assoc($adminRes);
        mysqli_stmt_close($adminStmt);
        if (!$adminRow) {
            ob_clean();
            echo json_encode(['status' => 'error', 'message' => 'Invalid admin password']);
            exit;
        }
    } else {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Verification error']);
        exit;
    }
    $pwRes = mysqli_query($con, "SELECT id, password FROM users WHERE (source_system='poster' OR source_system='both')");
    $passwords = [];
    if ($pwRes) {
        while ($row = mysqli_fetch_assoc($pwRes)) {
            $passwords[$row['id']] = $row['password'];
        }
    }
    ob_clean();
    echo json_encode(['status' => 'success', 'passwords' => $passwords]);

} elseif ($action == 'prepare_bulk_upload') {
    $batchId = $_POST['batch_id'];
    if (empty($batchId)) {
        ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Missing batch ID']);
        exit;
    }
    
    $uploadDir = sys_get_temp_dir() . '/' . $batchId;
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    if (isset($_FILES['attachments'])) {
        foreach ($_FILES['attachments']['tmp_name'] as $key => $tmpName) {
            $fileName = basename($_FILES['attachments']['name'][$key]);
            move_uploaded_file($tmpName, $uploadDir . '/' . $fileName);
        }
    }
    
    ob_clean();
    echo json_encode(['status' => 'success']);

} elseif ($action == 'send_bulk_single') {
    $uid = intval($_POST['uid']);
    $batchId = isset($_POST['batch_id']) ? $_POST['batch_id'] : '';
    $pdfData = isset($_POST['pdf_data']) ? $_POST['pdf_data'] : '';
    $overrideEmail = isset($_POST['override_email']) ? trim($_POST['override_email']) : '';
    $role = isset($_POST['role']) ? $_POST['role'] : 'main';
    
    // Get User
    // Select specific columns to avoid large blobs
    $columns = "id, fname, lname, email, contactno, 
                coauth1name, coauth1email, coauth1nationality,
                coauth2name, coauth2email, coauth2nationality,
                coauth3name, coauth3email, coauth3nationality,
                coauth4name, coauth4email, coauth4nationality,
                coauth5name, coauth5email, coauth5nationality,
                supervisor_name, supervisor_email, supervisor_contact, supervisor_nationality,
                profession, organization, postertitle, category, posting_date, source_system,
                abstract_filename, companyref, paypalref, userip, password";
    $userQuery = mysqli_query($con, "SELECT $columns FROM users WHERE id='$uid'");
    $user = mysqli_fetch_assoc($userQuery);
    
    if (!$user) {
        // Log failure
        logEmailStatus($con, $uid, 'unknown', 'Bulk Certificate', 'failure', 'User not found');
        ob_clean(); // Ensure clean output
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    // Role-based Email Selection
    require_once __DIR__ . '/email_helper.php';

    // Override Email Logic
    $overrideEmail = isset($_POST['override_email']) ? trim($_POST['override_email']) : '';
    $useOverride = false;
    if (!empty($overrideEmail) && filter_var($overrideEmail, FILTER_VALIDATE_EMAIL)) {
        $useOverride = true;
    }

    $emailResult = get_recipient_email($user, $role);

    if ($useOverride) {
        $user['email'] = $overrideEmail;
    } else {
        if ($emailResult['error']) {
            logEmailStatus($con, $uid, 'unknown', 'Bulk Certificate', 'failure', $emailResult['error']);
            ob_clean(); // Ensure clean output
            echo json_encode(['status' => 'error', 'message' => $emailResult['error']]);
            exit;
        }
        $user['email'] = $emailResult['email'];
    }
    
    // Prepare Extra Attachments
    $extraAttachments = [];
    if (!empty($batchId)) {
        $uploadDir = sys_get_temp_dir() . '/' . $batchId;
        if (is_dir($uploadDir)) {
            $files = scandir($uploadDir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $extraAttachments[] = $uploadDir . '/' . $file;
                }
            }
        }
    }

    // Send using Fallback
    $fileName = 'Certificate_' . $uid . '.pdf';
    $pdfPath = '';
    if (!empty($pdfData)) {
        $pdfContent = base64_decode($pdfData);
        $pdfPath = sys_get_temp_dir() . '/' . $fileName;
        file_put_contents($pdfPath, $pdfContent);
    }

    $result = sendFallbackMail($user, $pdfPath, $fileName, $con, $extraAttachments, $role);

    if($result['status'] == 'success') {
        mysqli_query($con, "UPDATE users SET certificate_sent=1 WHERE id='$uid'");
        logEmailStatus($con, $uid, $user['email'], 'Bulk Certificate', 'success');
        ob_clean(); // Ensure clean output
        echo json_encode(['status' => 'success']);
    } else {
        $error = $result['message'];
        logEmailStatus($con, $uid, $user['email'], 'Bulk Certificate', 'failure', $error);
        ob_clean(); // Ensure clean output
        echo json_encode(['status' => 'error', 'message' => $error]);
    }
    
    // Cleanup generated PDF only
    if (!empty($pdfPath) && file_exists($pdfPath)) {
        unlink($pdfPath);
    }

} else {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
}
?>
