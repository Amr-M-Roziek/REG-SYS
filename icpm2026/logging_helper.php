<?php
// Helper functions for email and logging

if (!function_exists('log_submission')) {
    function log_submission($email, $status, $message, $filename, $refId) {
        $line = date('c') . " | $status | $email | $refId | $filename | $message\n";
        @file_put_contents(__DIR__ . '/submissions.log', $line, FILE_APPEND);
    }
}

if (!function_exists('send_mail_with_attachment_ex')) {
    function send_mail_with_attachment_ex($to, $subject, $bodyText, $filePath, $fileName, $from, $replyTo=null, $isHtml=false) {
        $transport = 'mail';
        $error = '';
        $ok = false;
        $ts = date('c');
        $linePrefix = $ts . " | EMAIL_ATTEMPT | $to | " . ($fileName ?: '') . " | ";
        @file_put_contents(__DIR__ . '/submissions.log', $linePrefix . "transport=$transport; subject=" . $subject . "\n", FILE_APPEND);
        
        // SMTP Configuration
        $host = getenv('SMTP_HOST');
        $user = getenv('SMTP_USER');
        $pass = getenv('SMTP_PASS');
        $port = getenv('SMTP_PORT');
        $secure = getenv('SMTP_SECURE') ?: 'tls';
        $fromAddr = getenv('SMTP_FROM') ?: 'ICPM@reg-sys.com';
        $fromName = getenv('SMTP_FROM_NAME') ?: 'ICPM';
        
        $useSmtp = ($host && $user && $pass);
        
        if ($useSmtp && file_exists(__DIR__ . '/smtp/PHPMailerAutoload.php')) {
            require_once(__DIR__ . '/smtp/PHPMailerAutoload.php');
            $mail = new PHPMailer();
            $mail->IsSMTP();
            $mail->SMTPAuth = true;
            if ($secure) { $mail->SMTPSecure = $secure; }
            $mail->Host = $host;
            $mail->Port = $port ? intval($port) : 587;
            $mail->IsHTML($isHtml);
            $mail->CharSet = 'UTF-8';
            $mail->Username = $user;
            $mail->Password = $pass;
            $mail->SetFrom($fromAddr, $fromName);
            $mail->Subject = $subject;
            $mail->Body = $bodyText;
            if (!$isHtml) {
                 // If text mode, ensure newlines are handled if needed, but usually Body is enough
            } else {
                 $mail->AltBody = strip_tags($bodyText);
            }
            
            if ($replyTo) { $mail->addReplyTo($replyTo); }
            $mail->AddAddress($to);
            if ($filePath && is_file($filePath)) {
                $mail->AddAttachment($filePath, $fileName ?: basename($filePath));
            }
            $transport = 'smtp';
            try {
                $ok = $mail->Send();
            } catch (Exception $e) {
                $ok = false;
                $error = $e->getMessage();
            }
            if (!$ok && empty($error)) { $error = $mail->ErrorInfo; }
        } else {
            // Fallback to mail()
            $headers = "From: " . ($from ?: 'ICPM@reg-sys.com') . "\r\n";
            if ($replyTo) $headers .= "Reply-To: $replyTo\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $boundary = md5(uniqid(time(), true));
            
            if ($isHtml || ($filePath && is_file($filePath))) {
                 $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";
                 $body = "--$boundary\r\n";
                 $body .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=\"utf-8\"\r\n";
                 $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
                 $body .= $bodyText . "\r\n";
                 
                 if ($filePath && is_file($filePath)) {
                    $data = chunk_split(base64_encode(file_get_contents($filePath)));
                    $body .= "--$boundary\r\n";
                    $body .= "Content-Type: application/octet-stream; name=\"$fileName\"\r\n";
                    $body .= "Content-Transfer-Encoding: base64\r\n";
                    $body .= "Content-Disposition: attachment; filename=\"$fileName\"\r\n\r\n";
                    $body .= $data . "\r\n";
                 }
                 $body .= "--$boundary--";
            } else {
                 $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
                 $body = $bodyText;
            }
            
            $ok = mail($to, $subject, $body, $headers);
            if (!$ok) { $error = 'mail() returned false'; }
        }
        
        $status = $ok ? 'EMAIL_SENT' : 'EMAIL_FAIL';
        $msg = $ok ? "transport=$transport" : ("transport=$transport; error=" . $error);
        @file_put_contents(__DIR__ . '/submissions.log', $ts . " | $status | $to | " . ($fileName ?: '') . " | " . $msg . "\n", FILE_APPEND);
        return array($ok, $transport, $error);
    }
}

if (!function_exists('log_deduplication')) {
    function log_deduplication($email, $reason, $details = '') {
        $line = date('c') . " | DEDUPLICATE | $email | $reason | $details\n";
        @file_put_contents(__DIR__ . '/deduplication.log', $line, FILE_APPEND);
    }
}

if (!function_exists('log_error')) {
    function log_error($email, $error_type, $message) {
        $line = date('c') . " | ERROR | $email | $error_type | $message\n";
        @file_put_contents(__DIR__ . '/error.log', $line, FILE_APPEND);
    }
}
?>