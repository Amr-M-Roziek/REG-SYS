<?php
// admin/bulk_process_ajax.php
session_start();
include 'dbconnection.php';
require_once 'includes/auth_helper.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Check Permissions
if (!isset($_SESSION['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$uploadDir = __DIR__ . '/uploads/';

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// --- ACTION 1: UPLOAD FILE ---
if ($action == 'upload') {
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != 0) {
        echo json_encode(['status' => 'error', 'message' => 'File upload failed']);
        exit;
    }

    $file = $_FILES['csv_file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($ext != 'csv') {
        echo json_encode(['status' => 'error', 'message' => 'Only CSV files are allowed']);
        exit;
    }

    $filename = 'bulk_process_' . time() . '_' . rand(1000,9999) . '.csv';
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        // Count rows to tell frontend how many iterations to run
        $lineCount = 0;
        $handle = fopen($targetPath, "r");
        while(!feof($handle)){
            $line = fgets($handle);
            if(trim($line) != '') $lineCount++;
        }
        fclose($handle);
        
        // Subtract header
        $totalRecords = max(0, $lineCount - 1);

        echo json_encode([
            'status' => 'success',
            'filename' => $filename,
            'total_records' => $totalRecords,
            'message' => "File uploaded. Found $totalRecords records. Starting process..."
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save file']);
    }
    exit;
}

// --- ACTION 2: PROCESS SINGLE ROW ---
if ($action == 'process_row') {
    $filename = $_POST['filename'];
    $rowIndex = intval($_POST['index']); // 0-based index relative to data rows (excluding header)
    
    $filePath = $uploadDir . basename($filename);
    
    if (!file_exists($filePath)) {
        echo json_encode(['status' => 'error', 'message' => 'File not found']);
        exit;
    }

    $handle = fopen($filePath, "r");
    $headers = fgetcsv($handle); // Read header
    
    // Map headers
    $headerMap = array_flip(array_map('strtolower', array_map('trim', $headers)));
    
    // Seek to the specific row
    // We start at 0, so we skip $rowIndex lines.
    // Note: This is O(N) seek, which is fine for batch sizes < 1000. 
    // For very large files, maintaining a pointer is better, but this is stateless and robust.
    for ($i = 0; $i < $rowIndex; $i++) {
        if (feof($handle)) break;
        fgets($handle);
    }
    
    $data = fgetcsv($handle);
    fclose($handle);

    if (!$data) {
        echo json_encode(['status' => 'error', 'message' => 'End of file or empty row']);
        exit;
    }

    // Extract Data
    $email = isset($headerMap['email']) && isset($data[$headerMap['email']]) ? trim($data[$headerMap['email']]) : '';
    $fname = isset($headerMap['first name']) && isset($data[$headerMap['first name']]) ? trim($data[$headerMap['first name']]) : '';
    $lname = isset($headerMap['last name']) && isset($data[$headerMap['last name']]) ? trim($data[$headerMap['last name']]) : '';
    $category = isset($headerMap['category']) && isset($data[$headerMap['category']]) ? trim($data[$headerMap['category']]) : '';
    $password = isset($headerMap['password']) && !empty($data[$headerMap['password']]) ? $data[$headerMap['password']] : substr(md5(uniqid()), 0, 8);
    
    $nationality = isset($headerMap['nationality']) ? ($data[$headerMap['nationality']] ?? '') : '';
    $profession = isset($headerMap['profession']) ? ($data[$headerMap['profession']] ?? '') : '';
    $organization = isset($headerMap['organization']) ? ($data[$headerMap['organization']] ?? '') : '';
    $contact = isset($headerMap['contact no']) ? ($data[$headerMap['contact no']] ?? '') : '';

    $rowDisplayNum = $rowIndex + 1;

    // Validation
    if (empty($email) || empty($fname)) {
        echo json_encode(['status' => 'error', 'message' => "Row $rowDisplayNum: Skipped - Missing Email or Name"]);
        exit;
    }

    // Check Duplicate
    $stmt = mysqli_prepare($con, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_execute($stmt, [$email]);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        echo json_encode(['status' => 'warning', 'message' => "Row $rowDisplayNum: Skipped - Email $email already exists"]);
        exit;
    }
    mysqli_stmt_close($stmt);

    // Insert User
    $enc_pass = password_hash($password, PASSWORD_DEFAULT);
    $ins = mysqli_prepare($con, "INSERT INTO users (fname, lname, nationality, email, profession, organization, category, password, contactno) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if (mysqli_stmt_execute($ins, [$fname, $lname, $nationality, $email, $profession, $organization, $category, $enc_pass, $contact])) {
        $uid = mysqli_insert_id($con);
        
        // --- SEND EMAIL ---
        $sendResult = sendRegistrationEmail($email, $fname, $lname, $uid, $password, $category);
        
        $emailStatus = $sendResult ? "Email Sent" : "Email Failed";
        
        echo json_encode([
            'status' => 'success', 
            'message' => "Row $rowDisplayNum: Registered $fname $lname ($email) - ID: $uid - $emailStatus"
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Row $rowDisplayNum: Database Error - " . mysqli_error($con)]);
    }
    exit;
}

// --- EMAIL FUNCTION ---
function sendRegistrationEmail($to, $fname, $lname, $uid, $password, $category) {
    $subject = "ICPM 2026 Registration Confirmation - Ref #" . $uid;
    
    // QR Code API (Public)
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($uid);
    
    $logoUrl = "https://reg-sys.com/icpm2026/images/icpm-logo.png"; // Adjust if needed
    
    $message = '
    <html>
    <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
        <div style="max-width: 600px; margin: 0 auto; border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
            <div style="text-align: center; margin-bottom: 20px;">
                <h2 style="color: #4ecdc4;">Registration Confirmed</h2>
            </div>
            
            <p>Dear ' . htmlspecialchars($fname . ' ' . $lname) . ',</p>
            
            <p>Thank you for registering for ICPM 2026. Your registration has been successfully processed.</p>
            
            <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin: 20px 0;">
                <h3 style="margin-top: 0;">Registration Details</h3>
                <p><strong>Reference ID:</strong> ' . $uid . '</p>
                <p><strong>Category:</strong> ' . htmlspecialchars($category) . '</p>
                <p><strong>Email:</strong> ' . htmlspecialchars($to) . '</p>
                <p><strong>Password:</strong> ' . htmlspecialchars($password) . '</p>
            </div>
            
            <div style="text-align: center; margin: 20px 0;">
                <p><strong>Your Registration QR Code:</strong></p>
                <img src="' . $qrUrl . '" alt="QR Code" style="border: 1px solid #ccc; padding: 5px;">
                <p><small>Please present this code at the event entrance.</small></p>
            </div>
            
            <p>If you have any questions, please contact the administration.</p>
            
            <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
            <p style="font-size: 12px; color: #777; text-align: center;">&copy; 2026 ICPM Registration System</p>
        </div>
    </body>
    </html>
    ';

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: ICPM Admin <admin@reg-sys.com>" . "\r\n";

    // Use @ to suppress output if mail server not configured on localhost
    return @mail($to, $subject, $message, $headers);
}
?>
