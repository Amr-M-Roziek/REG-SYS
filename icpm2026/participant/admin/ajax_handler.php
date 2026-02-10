<?php
ob_start(); // Start output buffering to prevent unwanted output
session_start();
header('Content-Type: application/json');
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/error_log'); // Ensure errors are logged locally

include 'dbconnection.php';

// Clear any output from includes
ob_clean();

// Helper function to send JSON response cleanly
function json_response($data) {
    ob_clean(); // Discard any previous output (warnings, notices, whitespace)
    $json = json_encode($data);
    if ($json === false) {
        // Handle encoding errors (e.g. malformed UTF-8)
        $json = json_encode(['status' => 'error', 'message' => 'JSON Encode Error: ' . json_last_error_msg()]);
    }
    echo $json;
    exit;
}

// Check session
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    json_response(['status' => 'error', 'message' => 'Unauthorized']);
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

try {

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
function getHtmlEmail($user) {
    // Unique ID for footer to prevent collapsing
    $uniqueId = uniqid();

    // Standardized Footer Content (Bilingual)
    $footerNote = '
    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; font-size: 13px; color: #555; background-color: #fcfcfc; padding: 15px; border-radius: 4px;">
        <p style="margin: 0 0 10px 0;"><strong>Certificate Support / دعم الشهادات:</strong></p>
        <p style="margin: 0 0 10px 0;">If you experience any display issues with the certificate or require a name correction, please contact us via WhatsApp at <a href="https://wa.me/971529936233" style="color: #d4af37; text-decoration: none; font-weight: bold;">00971529936233</a> or email <a href="mailto:support@reg-sys.com" style="color: #d4af37; text-decoration: none; font-weight: bold;">support@reg-sys.com</a>. Kindly allow 48-72 hours for us to process your request and make the necessary corrections.</p>
        <p style="margin: 0; direction: rtl; text-align: right; font-family: Tahoma, Arial, sans-serif;">إذا واجهت أي مشاكل في عرض الشهادة أو كنت بحاجة إلى تصحيح الاسم، يرجى التواصل معنا عبر واتساب على الرقم <a href="https://wa.me/971529936233" style="color: #d4af37; text-decoration: none; font-weight: bold;">00971529936233</a> أو عبر البريد الإلكتروني <a href="mailto:support@reg-sys.com" style="color: #d4af37; text-decoration: none; font-weight: bold;">support@reg-sys.com</a>. يرجى منحنا 48-72 ساعة لمعالجة طلبك وإجراء التصحيحات اللازمة.</p>
    </div>';

    return '<!DOCTYPE html>
    <html>
    <head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 0; }
        .container { width: 100%; max-width: 600px; margin: 0 auto; background: #ffffff; border: 1px solid #ddd; }
        .header { background: #f8f8f8; padding: 20px; text-align: center; border-bottom: 3px solid #d4af37; }
        .content { padding: 30px 20px; }
        .footer { font-size: 12px; color: #333; padding: 20px; background: #ffffff; text-align: center; }
        .text-logo { color: #2c3e50; text-align: center; font-family: Arial, sans-serif; font-size: 22px; font-weight: bold; margin: 20px 0; }
        .app-link { text-decoration: none; display: inline-block; }
    </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <img src="https://reg-sys.com/icpm2026/images/icpm-logo.png" alt="ICPM Logo" style="max-width: 200px; height: auto; display: block; margin: 0 auto 20px;">
                <h2 class="text-logo">International Conference of Pharmacy and Medicine (ICPM)</h2>
            </div>
            <div class="content">
                <h2 style="color: #2c3e50; text-align: center; font-family: Arial, sans-serif; font-size: 22px; font-weight: bold; margin: 20px 0;">Thank you for participating at ICPM 14 - 2026</h2>
                <p>Dear <strong>' . htmlspecialchars($user['fname'] . ' ' . (isset($user['lname']) ? $user['lname'] : '')) . '</strong>,</p>
                <p>We sincerely appreciate your participation in the 14th International Conference of pharmacy and medcine (ICPM).</p>
                <p>We are pleased to provide you with your Certificate of Attendance, which is attached to this email.</p>
                <p>We hope you found the sessions insightful and valuable.</p>
                <h2 style="color: #2c3e50; text-align: center; font-family: Arial, sans-serif; font-size: 22px; font-weight: bold; margin: 20px 0;">To activate your certificate please download ICPM Mobile app and login</h2>
                
                <h3 style="color: #2c3e50; font-family: Arial, sans-serif; font-weight: bold;">Download From the App Store</h3>
                <p>For apple IOS (Iphone and IPad) <a href="https://apps.apple.com/ae/app/icpm/id6757741792">https://apps.apple.com/ae/app/icpm/id6757741792</a></p>
                <p style="text-align: center;">
                    <a href="https://apps.apple.com/ae/app/icpm/id6757741792" class="app-link">
                        <img src="https://reg-sys.com/icpm2026/images/appstore.jpg" alt="Download on the App Store" style="width: 150px; height: auto;">
                    </a>
                </p>

                <h3 style="color: #2c3e50; font-family: Arial, sans-serif; font-weight: bold;">Download For Android</h3>
                <p>For All android : <a href="https://regsys.cloud/download.html">https://regsys.cloud/download.html</a></p>
                
                <p style="text-align: center;">
                    <a href="https://regsys.cloud/download.html" class="app-link">
                        <img src="https://reg-sys.com/icpm2026/images/googleplaycomingsoon.png" alt="Get it on Google Play (Coming Soon)" style="width: 150px; height: auto;">
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
function getPlainTextEmail($user) {
    $name = $user['fname'] . ' ' . (isset($user['lname']) ? $user['lname'] : '');
    
    return "International Conference of Pharmacy and Medicine (ICPM)

Thank you for participating at ICPM 14 - 2026

Dear $name,

We sincerely appreciate your participation in the 14th International Conference of pharmacy and medcine (ICPM).
We are pleased to provide you with your Certificate of Attendance, which is attached to this email.
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
function generateMimeMessage($user, $attachmentPath, $attachmentName, $extraAttachments = []) {
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
    $message .= getPlainTextEmail($user) . $eol . $eol;
    
    // 2b. HTML Content
    $message .= "--" . $boundaryAlt . $eol;
    $message .= "Content-Type: text/html; charset=\"UTF-8\"" . $eol;
    $message .= "Content-Transfer-Encoding: 8bit" . $eol . $eol;
    $message .= getHtmlEmail($user) . $eol . $eol;
    
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
function sendFallbackMail($user, $attachmentPath, $attachmentName, $con = null, $extraAttachments = []) {
    $to = $user['email'];
    $subject = 'Your ICPM 2026 Certificate';
    
    // Generate the MIME content
    $mime = generateMimeMessage($user, $attachmentPath, $attachmentName, $extraAttachments);
    
    // Send
    $sent = mail($to, $subject, $mime['body'], $mime['headers']);
    
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
        json_response(['status' => 'error', 'message' => 'Name and data required']);
    }

    // Ensure table exists
    $tableCheck = mysqli_query($con, "SHOW TABLES LIKE 'certificate_templates'");
    if ($tableCheck && mysqli_num_rows($tableCheck) == 0) {
        $createSql = "CREATE TABLE certificate_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(191) NOT NULL UNIQUE,
            data LONGTEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        if (!mysqli_query($con, $createSql)) {
            json_response(['status' => 'error', 'message' => 'Table creation failed: ' . mysqli_error($con)]);
        }
    }
    
    // Check if exists to update or insert
    $checkQuery = mysqli_query($con, "SELECT id FROM certificate_templates WHERE name='$name'");
    
    if ($checkQuery && mysqli_num_rows($checkQuery) > 0) {
        // Update
        $query = mysqli_query($con, "UPDATE certificate_templates SET data='$data' WHERE name='$name'");
        $msg = 'Template updated successfully';
    } else {
        // Insert
        $query = mysqli_query($con, "INSERT INTO certificate_templates (name, data) VALUES ('$name', '$data')");
        $msg = 'Template saved successfully';
    }
    
    if ($query) {
        json_response(['status' => 'success', 'message' => $msg]);
    } else {
        json_response(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }
    
    
} elseif ($action == 'delete_template') {
    $id = intval($_POST['id']);
    $query = mysqli_query($con, "DELETE FROM certificate_templates WHERE id='$id'");
    
    if ($query) {
        json_response(['status' => 'success', 'message' => 'Template deleted successfully']);
    } else {
        json_response(['status' => 'error', 'message' => 'Database error: ' . mysqli_error($con)]);
    }

} elseif ($action == 'get_templates') {
    $query = mysqli_query($con, "SELECT id, name, created_at FROM certificate_templates ORDER BY created_at DESC");
    $templates = [];
    if ($query) {
        while ($row = mysqli_fetch_assoc($query)) {
            $templates[] = $row;
        }
    }
    json_response(['status' => 'success', 'data' => $templates]);

} elseif ($action == 'load_template') {
    $id = intval($_POST['id']);
    $query = mysqli_query($con, "SELECT * FROM certificate_templates WHERE id='$id'");
    $template = mysqli_fetch_assoc($query);
    
    if ($template) {
        // HOTFIX: Replace hardcoded "Speaker" with dynamic category variable
        $data = json_decode($template['data'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            $modified = false;
            foreach ($data as &$element) {
                // Check if content is "Speaker" (case-insensitive) or contains it
                if (isset($element['content']) && stripos(strip_tags($element['content']), 'Speaker') !== false) {
                    // Only change if it's not already a variable
                    if (empty($element['dataVariable'])) {
                        $element['content'] = 'In Gratitude for the outstanding Contribution as {category}';
                        $element['dataVariable'] = 'category';
                        $modified = true;
                    }
                }
            }
            if ($modified) {
                $template['data'] = json_encode($data);
            }
        }
        json_response(['status' => 'success', 'data' => $template]);
    } else {
        json_response(['status' => 'error', 'message' => 'Template not found']);
    }

} elseif ($action == 'get_default_template') {
    // Try to find 'Final-CME' first, then 'Final', then 'Default'
    $query = mysqli_query($con, "SELECT * FROM certificate_templates WHERE name='Final-CME' ORDER BY id DESC LIMIT 1");
    if (mysqli_num_rows($query) == 0) {
        $query = mysqli_query($con, "SELECT * FROM certificate_templates WHERE name='Final' ORDER BY id DESC LIMIT 1");
    }
    if (mysqli_num_rows($query) == 0) {
        $query = mysqli_query($con, "SELECT * FROM certificate_templates WHERE name='Default' ORDER BY id DESC LIMIT 1");
    }
    
    $template = mysqli_fetch_assoc($query);
    
    if ($template) {
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
                        $element['content'] = 'In Gratitude for the outstanding Contribution as {category}';
                        $element['dataVariable'] = 'category';
                        $modified = true;
                    }
                }
            }
            if ($modified) {
                $template['data'] = json_encode($data);
            }
        }
        
        echo json_encode(['status' => 'success', 'data' => $template]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Default template not found']);
    }

} elseif ($action == 'update_certificate_status') {
    $uid = isset($_POST['user_id']) ? intval($_POST['user_id']) : intval($_POST['uid']);
    $status = intval($_POST['status']);
    
    $stmt = mysqli_prepare($con, "UPDATE users SET certificate_sent=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ii', $status, $uid);
    
    if (mysqli_stmt_execute($stmt)) {
        json_response(['status' => 'success', 'message' => 'Status updated']);
    } else {
        json_response(['status' => 'error', 'message' => 'Database update failed']);
    }

} elseif ($action == 'send_certificate') {
    $uid = intval($_POST['uid']);
    $pdfData = $_POST['pdf_data'];
    $overrideEmail = isset($_POST['override_email']) ? trim($_POST['override_email']) : '';
    
    if ($uid == 0 || empty($pdfData)) {
        json_response(['status' => 'error', 'message' => 'Invalid data']);
    }
    
   // Get User Email
    $userQuery = mysqli_query($con, "SELECT * FROM users WHERE id='$uid'");
    $user = mysqli_fetch_assoc($userQuery);
    
    if (!$user) {
        json_response(['status' => 'error', 'message' => 'User not found']);
    }

    // Override Email Logic
    $recipientEmail = $user['email'];
    if (!empty($overrideEmail)) {
        if (filter_var($overrideEmail, FILTER_VALIDATE_EMAIL)) {
            $recipientEmail = $overrideEmail;
            // Temporarily swap email in user array for sendFallbackMail
            $user['email'] = $overrideEmail; 
        } else {
            json_response(['status' => 'error', 'message' => 'Invalid override email format']);
        }
    }
    
    // Save PDF temporarily
    $pdfContent = base64_decode($pdfData);
    $fileName = 'Certificate_' . $uid . '.pdf';
    $tempPath = sys_get_temp_dir() . '/' . $fileName;
    file_put_contents($tempPath, $pdfContent);
    
    // Send Email using Fallback (Standardized)
    // PHPMailer code removed to enforce fallback
    $result = sendFallbackMail($user, $tempPath, $fileName, $con);
    
    // Cleanup
    if (file_exists($tempPath)) {
        unlink($tempPath);
    }

    if ($result['status'] == 'success') {
        mysqli_begin_transaction($con);
        try {
            $updateQuery = "UPDATE users SET certificate_sent=1 WHERE id='$uid'";
            if (!mysqli_query($con, $updateQuery)) {
                throw new Exception(mysqli_error($con), mysqli_errno($con));
            }
            logEmailStatus($con, $uid, $user['email'], 'Certificate Sent', 'success');
            mysqli_commit($con);
            json_response(['status' => 'success', 'message' => 'Email sent (fallback)']);
        } catch (Exception $e) {
            mysqli_rollback($con);
            if ($e->getCode() == 1054 || mysqli_errno($con) == 1054) { // Unknown column
                // Try to add column
                mysqli_query($con, "ALTER TABLE users ADD COLUMN certificate_sent TINYINT(1) DEFAULT 0");
                mysqli_query($con, "ALTER TABLE users ADD INDEX idx_certificate_sent (certificate_sent)");
                
                // Retry update
                mysqli_begin_transaction($con);
                if (mysqli_query($con, $updateQuery)) {
                    logEmailStatus($con, $uid, $user['email'], 'Certificate Sent', 'success');
                    mysqli_commit($con);
                    json_response(['status' => 'success', 'message' => 'Email sent (fallback)']);
                } else {
                    mysqli_rollback($con);
                    logEmailStatus($con, $uid, $user['email'], 'Certificate Sent', 'failure', 'DB Update Failed');
                    json_response(['status' => 'error', 'message' => 'Status update failed']);
                }
            } else {
                logEmailStatus($con, $uid, $user['email'], 'Certificate Sent', 'failure', 'DB Update Failed: ' . $e->getMessage());
                json_response(['status' => 'error', 'message' => 'Status update failed']);
            }
        }
    } else {
        logEmailStatus($con, $uid, $user['email'], 'Certificate Sent', 'failure', $result['message']);
        json_response(['status' => 'error', 'message' => 'Email failed: ' . $result['message']]);
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
        $user['verificationLink'] = $protocol . $_SERVER['HTTP_HOST'] . "/icpm2026/verify.php?id=" . $user['id'] . "&hash=" . $hash;

        json_response(['status' => 'success', 'data' => $user]);
    } else {
        json_response(['status' => 'error', 'message' => 'User not found']);
    }

} elseif ($action == 'prepare_bulk_upload') {
    $batchId = $_POST['batch_id'];
    if (empty($batchId)) {
        json_response(['status' => 'error', 'message' => 'Missing batch ID']);
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
    
    json_response(['status' => 'success']);

} elseif ($action == 'send_bulk_single') {
    $uid = intval($_POST['uid']);
    $batchId = isset($_POST['batch_id']) ? $_POST['batch_id'] : '';
    $pdfData = isset($_POST['pdf_data']) ? $_POST['pdf_data'] : '';
    
    // Get User
    $userQuery = mysqli_query($con, "SELECT * FROM users WHERE id='$uid'");
    $user = mysqli_fetch_assoc($userQuery);
    
    if (!$user) {
        // Log failure
        if (isset($con)) {
             logEmailStatus($con, $uid, 'unknown', 'Bulk Certificate', 'failure', 'User not found');
        }
        json_response(['status' => 'error', 'message' => 'User not found']);
    }
    
    // Prepare Attachments
    $attachments = [];
    $tempFiles = [];

    // 1. Generated PDF
    if (!empty($pdfData)) {
        $pdfContent = base64_decode($pdfData);
        if ($pdfContent === false) {
             // throw new Exception("Failed to decode PDF data");
        } else {
            $fileName = 'Certificate_' . $uid . '.pdf';
            $pdfPath = sys_get_temp_dir() . '/' . $fileName;
            file_put_contents($pdfPath, $pdfContent);
            $tempFiles[] = $pdfPath;
            $attachments[] = ['path' => $pdfPath, 'name' => $fileName];
        }
    }
    
    // 2. Bulk Files
    if (!empty($batchId)) {
        $uploadDir = sys_get_temp_dir() . '/' . $batchId;
        if (is_dir($uploadDir)) {
            $files = scandir($uploadDir);
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $fullPath = $uploadDir . '/' . $file;
                    $attachments[] = ['path' => $fullPath, 'name' => $file];
                }
            }
        }
    }

    // Determine Transport
    // FORCE FALLBACK: The user environment (Windows/XAMPP/Sendmail) has issues with PHPMailer's mail() transport
    // and we do not have external SMTP credentials confirmed. 
    // We will prioritize the robust sendFallbackMail() function which avoids header folding issues.
    
    $sent = false;
    $error = '';
    
    // Find main PDF
    $mainPdfPath = '';
    $mainPdfName = '';
    $others = [];
    
    foreach ($attachments as $att) {
        if (strpos($att['name'], 'Certificate_') === 0 && substr($att['name'], -4) === '.pdf') {
            $mainPdfPath = $att['path'];
            $mainPdfName = $att['name'];
        } else {
            $others[] = $att;
        }
    }
    
    // If no main PDF found (e.g. only bulk files), use the first one as main
    if (empty($mainPdfPath) && count($others) > 0) {
        $first = array_shift($others);
        $mainPdfPath = $first['path'];
        $mainPdfName = $first['name'];
    }
    
    $result = sendFallbackMail($user, $mainPdfPath, $mainPdfName, $con, $others);
    if ($result['status'] == 'success') {
        $sent = true;
    } else {
        $error = 'Fallback mail failed: ' . $result['message'];
    }

    // Cleanup generated PDF only (bulk files stay for next user)
    foreach ($tempFiles as $f) {
        if (file_exists($f)) unlink($f);
    }

    if ($sent) {
        mysqli_begin_transaction($con);
        try {
            $updateQuery = "UPDATE users SET certificate_sent=1 WHERE id='$uid'";
            if (!mysqli_query($con, $updateQuery)) {
                throw new Exception(mysqli_error($con), mysqli_errno($con));
            }
            logEmailStatus($con, $uid, $user['email'], 'Bulk Certificate', 'success');
            mysqli_commit($con);
            json_response(['status' => 'success']);
        } catch (Exception $e) {
            mysqli_rollback($con);
            if ($e->getCode() == 1054 || mysqli_errno($con) == 1054) { // Unknown column
                mysqli_query($con, "ALTER TABLE users ADD COLUMN certificate_sent TINYINT(1) DEFAULT 0");
                mysqli_query($con, "ALTER TABLE users ADD INDEX idx_certificate_sent (certificate_sent)");
                // Retry
                mysqli_begin_transaction($con);
                if (mysqli_query($con, $updateQuery)) {
                    logEmailStatus($con, $uid, $user['email'], 'Bulk Certificate', 'success');
                    mysqli_commit($con);
                    json_response(['status' => 'success']);
                } else {
                    mysqli_rollback($con);
                    logEmailStatus($con, $uid, $user['email'], 'Bulk Certificate', 'failure', 'DB Update Failed');
                    json_response(['status' => 'error', 'message' => 'Status update failed']);
                }
            } else {
                logEmailStatus($con, $uid, $user['email'], 'Bulk Certificate', 'failure', 'DB Update Failed: ' . $e->getMessage());
                json_response(['status' => 'error', 'message' => 'Status update failed']);
            }
        }
    } else {
        logEmailStatus($con, $uid, $user['email'], 'Bulk Certificate', 'failure', $error);
        json_response(['status' => 'error', 'message' => $error]);
    }

} elseif ($action == 'delete_users') {
    // 1. Validate CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        json_response(['status' => 'error', 'message' => 'Invalid CSRF token']);
    }

    // 2. Validate Password
    $password = $_POST['password'];
    $adminId = $_SESSION['id'];
    
    // Fetch admin password hash
    // NOTE: Checking if 'admin' table exists in this DB
    $adminQuery = mysqli_query($con, "SELECT password FROM admin WHERE id='$adminId'");
    if (!$adminQuery || mysqli_num_rows($adminQuery) == 0) {
        // Fallback: If admin table doesn't exist or ID not found, maybe check 'users' if admin is a user?
        // Or hardcode a check if this is a separate participant DB?
        // For now, assuming standard admin table exists.
        json_response(['status' => 'error', 'message' => 'Admin not found']);
    }
    $adminRow = mysqli_fetch_assoc($adminQuery);
    $storedHash = $adminRow['password'];
    
    // Check password (MD5)
    if (md5($password) !== $storedHash) {
        json_response(['status' => 'error', 'message' => 'Incorrect password']);
    }
    
    // 3. Delete Users
    $ids = isset($_POST['ids']) ? $_POST['ids'] : [];
    if (!is_array($ids) || empty($ids)) {
        json_response(['status' => 'error', 'message' => 'No users selected']);
    }
    
    // Sanitize IDs
    $safeIds = array_map(function($id) {
        return intval($id);
    }, $ids);
    $idsStr = implode(',', $safeIds);
    
    // Begin Transaction
    mysqli_begin_transaction($con);
    
    try {
        // Delete
        $deleteQuery = "DELETE FROM users WHERE id IN ($idsStr)";
        if (!mysqli_query($con, $deleteQuery)) {
            throw new Exception(mysqli_error($con));
        }
        
        $deletedCount = mysqli_affected_rows($con);
        
        // 4. Audit Log
        $logAction = "Bulk Delete";
        $logDetails = "Deleted $deletedCount users. IDs: " . implode(',', $safeIds);
        
        if (function_exists('log_audit')) {
            log_audit($logAction, $logDetails);
        } else {
            // Fallback manual insert
            $ip = $_SERVER['REMOTE_ADDR'];
            // Ensure table exists (simplified check)
            $createSql = "CREATE TABLE IF NOT EXISTS admin_audit_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            @mysqli_query($con, $createSql);
            
            $logQuery = "INSERT INTO admin_audit_logs (admin_id, action, details, ip_address) VALUES ('$adminId', '$logAction', '$logDetails', '$ip')";
            @mysqli_query($con, $logQuery);
        }
        
        mysqli_commit($con);
        json_response(['status' => 'success', 'message' => "Deleted $deletedCount users"]);
        
    } catch (Exception $e) {
        mysqli_rollback($con);
        json_response(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }

} else {
    json_response(['status' => 'error', 'message' => 'Invalid action']);
}

} catch (Exception $e) {
    json_response(['status' => 'error', 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>
