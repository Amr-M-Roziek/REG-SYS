<?php
session_start();
// Increase memory limit for large files
ini_set('memory_limit', '256M');
header('Content-Type: application/json');

// Check auth
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'File upload failed']);
    exit;
}

$file = $_FILES['file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

$recipients = [];

if ($ext === 'csv') {
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle !== FALSE) {
        $headers = fgetcsv($handle, 1000, ",");
        // Normalize headers to lowercase for flexible matching
        $headerMap = [];
        foreach ($headers as $index => $h) {
            $h = strtolower(trim($h));
            // Map common names to our keys
            if (strpos($h, 'phone') !== false || strpos($h, 'mobile') !== false || strpos($h, 'contact') !== false) {
                $headerMap['phone'] = $index;
            } elseif (strpos($h, 'name') !== false) {
                $headerMap['name'] = $index;
            }
        }
        
        if (!isset($headerMap['phone'])) {
            echo json_encode(['status' => 'error', 'message' => 'Could not find a "Phone" or "Mobile" column in CSV']);
            fclose($handle);
            exit;
        }

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $phone = isset($headerMap['phone']) && isset($data[$headerMap['phone']]) ? trim($data[$headerMap['phone']]) : '';
            $name = isset($headerMap['name']) && isset($data[$headerMap['name']]) ? trim($data[$headerMap['name']]) : 'Valued Participant';
            
            if (!empty($phone)) {
                // Basic cleanup of phone number
                $phone = preg_replace('/[^0-9]/', '', $phone);
                
                // Add country code if missing (defaulting to user input later, or assume intl format)
                // For now, just pass it through
                
                $recipients[] = [
                    'phone' => $phone,
                    'name' => $name,
                    'original_phone' => $data[$headerMap['phone']] // For reference
                ];
            }
        }
        fclose($handle);
    }
} elseif ($ext === 'xlsx') {
    // Basic XLSX support requires a library like PhpSpreadsheet or SimpleXLSX
    // Since we might not have it installed, we'll suggest converting to CSV or try to read if simple xml
    // For now, let's return error for XLSX asking for CSV
    echo json_encode(['status' => 'error', 'message' => 'Please save your Excel file as CSV (Comma Delimited) and try again.']);
    exit;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Unsupported file format. Please use CSV.']);
    exit;
}

echo json_encode([
    'status' => 'success',
    'count' => count($recipients),
    'recipients' => $recipients
]);
?>
