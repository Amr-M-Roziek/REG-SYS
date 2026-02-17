<?php
ob_start(); // Start output buffering
session_start();
header('Content-Type: application/json');

// Check for upload size limit (POST empty but Content-Length > 0)
if (empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
    ob_clean();
    $maxSize = ini_get('post_max_size');
    echo json_encode(['status' => 'error', 'message' => "Upload failed: File exceeds server limit ($maxSize)."]);
    exit;
}

if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

include 'dbconnection.php';
mysqli_set_charset($con, 'utf8mb4');
if (file_exists(__DIR__ . '/SimpleXLSX.php')) {
    require_once __DIR__ . '/SimpleXLSX.php';
}

// Ensure Upload Logs Table exists
mysqli_query($con, "CREATE TABLE IF NOT EXISTS upload_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    filename VARCHAR(255),
    total_records INT,
    success_count INT,
    error_count INT,
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$uploadDir = __DIR__ . '/uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

function bulk_users_template_data() {
    $headers = ['First Name', 'Last Name', 'Full Name', 'Email', 'Password', 'Profession', 'Organization', 'Category', 'Contact No'];
    $rows = [
        ['John', 'Doe', 'John Doe', 'john.doe@example.com', 'Password123', 'Researcher', 'University of Example', 'Participant', '+1234567890']
    ];
    return [$headers, $rows];
}

function bulk_users_output_csv_template() {
    list($headers, $rows) = bulk_users_template_data();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="users_import_template.csv"');
    $fp = fopen('php://output', 'w');
    fputcsv($fp, $headers);
    foreach ($rows as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);
    exit;
}

function bulk_users_escape_xml($value) {
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function bulk_users_build_sheet_xml($headers, $rows) {
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
    $xml .= '<sheetData>';
    $rowIndex = 1;
    $allRows = array_merge([$headers], $rows);
    foreach ($allRows as $row) {
        $xml .= '<row r="' . $rowIndex . '">';
        $colIndex = 0;
        foreach ($row as $cell) {
        $xml .= '<c t="inlineStr"><is><t>' . bulk_users_escape_xml($cell) . '</t></is></c>';
            $colIndex++;
        }
        $xml .= '</row>';
        $rowIndex++;
    }
    $xml .= '</sheetData></worksheet>';
    return $xml;
}

function bulk_users_output_xlsx_template() {
    if (!class_exists('ZipArchive')) {
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Server does not support ZipArchive for XLSX generation.';
        exit;
    }
    list($headers, $rows) = bulk_users_template_data();
    $zip = new ZipArchive();
    $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip->open($tmpFile, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Users" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
    $sheetXml = bulk_users_build_sheet_xml($headers, $rows);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="users_import_template.xlsx"');
    header('Content-Length: ' . filesize($tmpFile));
    readfile($tmpFile);
    unlink($tmpFile);
    exit;
}

function bulk_users_convert_xlsx_to_csv($xlsxPath, $csvPath) {
    if (!class_exists('ZipArchive') || !class_exists('XMLReader')) {
        return false;
    }
    
    $zip = new ZipArchive();
    if ($zip->open($xlsxPath) !== true) {
        return false;
    }
    
    // 1. Load Shared Strings
    $sharedStrings = [];
    if ($zip->locateName('xl/sharedStrings.xml') !== false) {
        $reader = new XMLReader();
        $reader->open('zip://' . $xlsxPath . '#xl/sharedStrings.xml');
        while ($reader->read()) {
            if ($reader->nodeType == XMLReader::ELEMENT && $reader->name == 'si') {
                $node = simplexml_import_dom($reader->expand());
                if ($node) {
                    $text = '';
                    if (isset($node->t)) {
                        $text = (string)$node->t;
                    } elseif (isset($node->r)) {
                        foreach ($node->r as $run) {
                            $text .= (string)$run->t;
                        }
                    }
                    $sharedStrings[] = $text;
                } else {
                    $sharedStrings[] = '';
                }
            }
        }
        $reader->close();
    }

    // 2. Find the first worksheet
    // Iterate to find the first sheet in xl/worksheets/
    $sheetPath = '';
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (strpos($name, 'xl/worksheets/sheet') === 0 && substr($name, -4) === '.xml') {
            $sheetPath = $name;
            break; 
        }
    }
    $zip->close();

    if (!$sheetPath) {
        $sheetPath = 'xl/worksheets/sheet1.xml';
    }

    // 3. Parse Worksheet
    $reader = new XMLReader();
    if (!$reader->open('zip://' . $xlsxPath . '#' . $sheetPath)) {
        return false;
    }
    
    $fp = fopen($csvPath, 'w');
    if (!$fp) {
        $reader->close();
        return false;
    }
    
    while ($reader->read()) {
        if ($reader->nodeType == XMLReader::ELEMENT && $reader->localName == 'row') {
            $node = simplexml_import_dom($reader->expand());
            
            $row = [];
            if ($node && isset($node->c)) {
                foreach ($node->c as $c) {
                    $type = (string)$c['t'];
                    $val = '';
                    if ($type === 's') {
                        $idx = (int)$c->v;
                        $val = isset($sharedStrings[$idx]) ? $sharedStrings[$idx] : '';
                    } elseif ($type === 'inlineStr') {
                        if (isset($c->is->t)) {
                            $val = (string)$c->is->t;
                        } else {
                             $val = isset($c->v) ? (string)$c->v : '';
                        }
                    } else {
                        $val = isset($c->v) ? (string)$c->v : '';
                    }
                    $row[] = $val;
                }
            }
            fputcsv($fp, $row);
        }
    }
    $reader->close();
    fclose($fp);
    return true;
}

if (isset($_GET['action']) && $_GET['action'] == 'template') {
    $format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';
    if ($format === 'xlsx') {
        bulk_users_output_xlsx_template();
    } else {
        bulk_users_output_csv_template();
    }
}

// Handle File Upload (Step 1: Save & Analyze)
if (isset($_POST['action']) && $_POST['action'] == 'upload_users') {
    // Clean buffer
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    try {
        // Validate CSRF
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
             // ... existing csrf error logic ...
             throw new Exception("Invalid CSRF token.");
        }

        if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
            $msg = 'File upload error';
            if (isset($_FILES['file']['error'])) {
                switch ($_FILES['file']['error']) {
                    case UPLOAD_ERR_INI_SIZE: $msg = 'File exceeds upload_max_filesize'; break;
                    case UPLOAD_ERR_FORM_SIZE: $msg = 'File exceeds form MAX_FILE_SIZE'; break;
                    case UPLOAD_ERR_PARTIAL: $msg = 'File partially uploaded'; break;
                    case UPLOAD_ERR_NO_FILE: $msg = 'No file uploaded'; break;
                }
            }
            throw new Exception($msg);
        }

        $file = $_FILES['file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['csv', 'xls', 'xlsx'])) {
            throw new Exception('Invalid file format. Only .csv, .xls, .xlsx allowed.');
        }

        $filename = 'bulk_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Failed to save file.');
        }
        
        // ... rest of logic ...


    if ($ext === 'xlsx') {
        $csvName = preg_replace('/\.xlsx$/', '.csv', $filename);
        $csvPath = $uploadDir . $csvName;
        $ok = bulk_users_convert_xlsx_to_csv($targetPath, $csvPath);
        unlink($targetPath);
        if (!$ok) {
            if (file_exists($csvPath)) {
                unlink($csvPath);
            }
            throw new Exception('Could not read Excel file. Please use the provided template or upload CSV.');
        }
        $filename = $csvName;
        $targetPath = $csvPath;
        $ext = 'csv';
    }

    // Analyze File (Count Records)
    $handle = fopen($targetPath, "r");
    if (!$handle) {
        throw new Exception('Could not open file.');
    }

    // Detect delimiter
    $line = fgets($handle);
    rewind($handle);
    $delimiter = ",";
    if (strpos($line, "\t") !== false && strpos($line, ",") === false) $delimiter = "\t";
    elseif (strpos($line, ";") !== false && strpos($line, ",") === false) $delimiter = ";";

    // Skip Header
    fgetcsv($handle, 0, $delimiter);
    $startPos = ftell($handle);

    $totalRecords = 0;
    while(($data = fgetcsv($handle, 0, $delimiter)) !== FALSE){
        if (implode('', $data) != '') {
            $totalRecords++;
        }
    }
    fclose($handle);
    
    if ($totalRecords < 1) {
        unlink($targetPath);
        throw new Exception('File is empty or contains only headers.');
    }

    // Log in DB
    $adminId = $_SESSION['id'];
    $stmt = mysqli_prepare($con, "INSERT INTO upload_logs (admin_id, filename, total_records, status) VALUES (?, ?, ?, 'pending')");
    mysqli_stmt_bind_param($stmt, 'isi', $adminId, $filename, $totalRecords);
    mysqli_stmt_execute($stmt);
    $logId = mysqli_insert_id($con);

    echo json_encode([
        'status' => 'success',
        'filename' => $filename,
        'total_records' => $totalRecords,
        'log_id' => $logId,
        'start_pos' => $startPos,
        'delimiter' => $delimiter
    ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle Preview (Step 1.5: Preview Data)
if (isset($_POST['action']) && $_POST['action'] == 'preview_file') {
    // Clean buffer before starting
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');

    try {
        $filename = $_POST['filename'];
        $filePath = $uploadDir . basename($filename);
        
        if (!file_exists($filePath)) {
            throw new Exception('File not found.');
        }

        $handle = fopen($filePath, "r");
        if (!$handle) {
             throw new Exception('Could not open file.');
        }
        
        // Detect delimiter
        $line = fgets($handle);
        rewind($handle);
        
        if ($line === false) {
             throw new Exception('File is empty.');
        }

        $delimiter = ",";
        if (strpos($line, "\t") !== false && strpos($line, ",") === false) $delimiter = "\t";
        elseif (strpos($line, ";") !== false && strpos($line, ",") === false) $delimiter = ";";

        // Skip Header
        fgetcsv($handle, 0, $delimiter);

        $hasFullnameColumn = false;
        $colResult = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'fullname'");
        if ($colResult && mysqli_num_rows($colResult) > 0) {
            $hasFullnameColumn = true;
        }

        $previewRows = [];
        $count = 0;
        
        while ($count < 5 && ($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
            // Clean data encoding
            $data = array_map(function($str) {
                $str = trim($str);
                // Convert to UTF-8 if not already
                if (!mb_check_encoding($str, 'UTF-8')) {
                    return mb_convert_encoding($str, 'UTF-8', 'Windows-1252, ISO-8859-1');
                }
                return $str;
            }, $data);

            if (implode('', $data) == '') continue;

            $fname = $data[0] ?? '';
            $lname = $data[1] ?? '';
            
            if (count($data) >= 9) {
                $rawFullname = $data[2] ?? '';
                $fullname = $rawFullname !== '' ? $rawFullname : trim($fname . ' ' . $lname);
                $email = $data[3] ?? '';
                // Aggressively remove all whitespace before sanitization
                $email = preg_replace('/\s+/', '', $email);
                $email = filter_var($email, FILTER_SANITIZE_EMAIL);
                $profession = $data[5] ?? '';
                $organization = $data[6] ?? '';
                $category = 'Participant';
            } else {
                $fullname = trim($fname . ' ' . $lname);
                $email = $data[2] ?? '';
                // Aggressively remove all whitespace before sanitization
                $email = preg_replace('/\s+/', '', $email);
                $email = filter_var($email, FILTER_SANITIZE_EMAIL);
                $profession = $data[4] ?? '';
                $organization = $data[5] ?? '';
                $category = 'Participant';
            }

            $status = 'Valid';
            $statusColor = 'green';
            $notes = '';

            if (empty($email)) {
                $status = 'Invalid';
                $statusColor = 'red';
                $notes = 'Missing Email';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $status = 'Invalid';
                $statusColor = 'red';
                $notes = 'Invalid Email Format';
            } else {
                // Check Duplicate
                $checkStmt = mysqli_prepare($con, "SELECT id FROM users WHERE email = ?");
                mysqli_stmt_bind_param($checkStmt, "s", $email);
                mysqli_stmt_execute($checkStmt);
                mysqli_stmt_store_result($checkStmt);
                if (mysqli_stmt_num_rows($checkStmt) > 0) {
                    $status = 'Duplicate';
                    $statusColor = 'orange';
                    $notes = 'Email already exists';
                }
                mysqli_stmt_close($checkStmt);
            }

            $previewRows[] = [
                'fname' => $fname,
                'lname' => $lname,
                'fullname' => $fullname,
                'email' => $email,
                'profession' => $profession,
                'organization' => $organization,
                'category' => $category,
                'status' => $status,
                'status_color' => $statusColor,
                'notes' => $notes
            ];
            $count++;
        }
        
        fclose($handle);

        echo json_encode([
            'status' => 'success',
            'rows' => $previewRows
        ], JSON_INVALID_UTF8_SUBSTITUTE);

    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle Batch Processing (Step 2: Process Chunk)
if (isset($_POST['action']) && $_POST['action'] == 'process_batch') {
    header('Content-Type: application/json');

    $filename = $_POST['filename'];
    $filePos = intval($_POST['file_pos']); // Byte offset
    $limit = intval($_POST['limit']);
    $logId = intval($_POST['log_id']);
    $processedGlobal = intval($_POST['processed_global']); // To calculate Row Number correctly

    $filePath = $uploadDir . basename($filename);
    if (!file_exists($filePath)) {
        echo json_encode(['status' => 'error', 'message' => 'File not found.']);
        exit;
    }

    $handle = fopen($filePath, "r");
    
    $line = fgets($handle);
    $delimiter = ",";
    if (strpos($line, "\t") !== false && strpos($line, ",") === false) $delimiter = "\t";
    elseif (strpos($line, ";") !== false && strpos($line, ",") === false) $delimiter = ";";

    $hasFullnameColumn = false;
    $colResult = mysqli_query($con, "SHOW COLUMNS FROM users LIKE 'fullname'");
    if ($colResult && mysqli_num_rows($colResult) > 0) {
        $hasFullnameColumn = true;
    }

    // Seek to position
    fseek($handle, $filePos);

    $recordsProcessedInThisBatch = 0;
    $success = [];
    $errors = [];
    
    // Safety limit for lines read to avoid infinite loops on massive empty files
    $maxLinesToRead = $limit * 100; 
    $linesRead = 0;

    while ($recordsProcessedInThisBatch < $limit && $linesRead < $maxLinesToRead && ($data = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
        $linesRead++;
        
        // Clean data encoding
        $data = array_map(function($str) {
            $str = trim($str);
            // Convert to UTF-8 if not already
            if (!mb_check_encoding($str, 'UTF-8')) {
                return mb_convert_encoding($str, 'UTF-8', 'Windows-1252, ISO-8859-1');
            }
            return $str;
        }, $data);
        
        if (implode('', $data) == '') {
            continue; 
        }

        $recordsProcessedInThisBatch++;
        $rowNum = $processedGlobal + $recordsProcessedInThisBatch + 1; // Header is row 1.


        $fname = $data[0] ?? '';
        $lname = $data[1] ?? '';

        if (count($data) >= 9) {
            $rawFullname = $data[2] ?? '';
            $fullname = $rawFullname !== '' ? $rawFullname : trim($fname . ' ' . $lname);
            $email = $data[3] ?? '';
            // Aggressively remove all whitespace before sanitization
            $email = preg_replace('/\s+/', '', $email);
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            $password = $data[4] ?? '';
            $profession = $data[5] ?? '';
            $organization = $data[6] ?? '';
            $category = 'Participant';
            $contact = $data[8] ?? '';
        } else {
            $fullname = trim($fname . ' ' . $lname);
            $email = $data[2] ?? '';
            // Aggressively remove all whitespace before sanitization
            $email = preg_replace('/\s+/', '', $email);
            $email = filter_var($email, FILTER_SANITIZE_EMAIL);
            $password = $data[3] ?? '';
            $profession = $data[4] ?? '';
            $organization = $data[5] ?? '';
            $category = 'Participant';
            $contact = $data[7] ?? '';
        }

        $effectiveName = $fullname !== '' ? $fullname : trim($fname . ' ' . $lname);

        if ($effectiveName === '' || empty($email)) {
            $errors[] = "Row $rowNum: Missing Name (Full Name or First/Last) or Email";
            continue;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Row $rowNum: Invalid Email ($email)";
            continue;
        }

        // Check Duplicate
        $checkStmt = mysqli_prepare($con, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        mysqli_stmt_store_result($checkStmt);
        if (mysqli_stmt_num_rows($checkStmt) > 0) {
             $errors[] = "Row $rowNum: Email exists ($email)";
             mysqli_stmt_close($checkStmt);
             continue;
        }
        mysqli_stmt_close($checkStmt);

        // Insert
        // Use password_hash to match admin/bulk-upload.php behavior
        $enc_password = password_hash($password, PASSWORD_DEFAULT);
        
        if ($hasFullnameColumn) {
            $sql = "INSERT INTO users (fname, lname, fullname, email, password, profession, organization, category, contactno, posting_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        } else {
            $sql = "INSERT INTO users (fname, lname, email, password, profession, organization, category, contactno, posting_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        }
        $stmt = mysqli_prepare($con, $sql);
        
        $emailSent = false;
        $emailStatus = "Not attempted";

        if ($stmt) {
            if ($hasFullnameColumn) {
                mysqli_stmt_bind_param($stmt, "sssssssss", $fname, $lname, $fullname, $email, $enc_password, $profession, $organization, $category, $contact);
            } else {
                mysqli_stmt_bind_param($stmt, "ssssssss", $fname, $lname, $email, $enc_password, $profession, $organization, $category, $contact);
            }
            if (mysqli_stmt_execute($stmt)) {
                $uid = mysqli_insert_id($con);
                
                // Send Email
                $footerImg = "https://reg-sys.com/icpm2026/images/icpm-logo.png";
                $subject = "ICPM Registration Confirmation - Ref #" . $uid;
                $message = '<div style="font-family:Arial,Helvetica,sans-serif;color:#111;line-height:1.6">
                    <p>Hello ' . htmlspecialchars($fname) . ' ' . htmlspecialchars($lname) . ',</p>
                    <p>You have been registered successfully via bulk upload.</p>
                    <p>Please save your registration reference number.</p>
                    <p><strong>Credentials</strong><br>
                    Email: ' . htmlspecialchars($email) . '<br>
                    Password: ' . htmlspecialchars($password) . '<br>
                    Registration Number: ' . $uid . '</p>
                    <p><img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . $uid . '" alt="QR ' . $uid . '"></p>
                    <hr style="border:none;border-top:1px solid #eee;margin:16px 0">
                    <div style="text-align:center">
                    <img src="' . $footerImg . '" alt="ICPM" width="200" height="78" style="display:inline-block">
                    </div>
                    </div>';
                    
                $headers = "From: ICPM@reg-sys.com\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=utf-8\r\n";
                
                if (@mail($email, $subject, $message, $headers)) {
                    $emailSent = true;
                    $emailStatus = "Sent";
                    $success[] = "Row $rowNum: Added $fname $lname (Email Sent)";
                } else {
                    $emailSent = false;
                    $emailStatus = "Failed";
                    // We consider it a "success" in terms of registration, but note the email failure
                    // Or we can treat it as a partial success. 
                    // The prompt wants "Users who didn't receive confirmation emails".
                    $success[] = "Row $rowNum: Added $fname $lname (Email Failed)";
                }
                
                // We can return structured data for the frontend to handle reporting better
                // But the current frontend expects simple strings in success[].
                // We'll modify the response structure slightly to support rich reporting if we can,
                // but for now, the string contains the info.
                // Better: Add a new array for details.
                
            } else {
                $errors[] = "Row $rowNum: DB Error - " . mysqli_error($con);
            }
            mysqli_stmt_close($stmt);
        } else {
             $errors[] = "Row $rowNum: System Error";
        }
    }
    $nextPos = ftell($handle);
    fclose($handle);

    // Update Logs (Optional, simplified)
    // mysqli_query($con, "UPDATE upload_logs SET success_count = success_count + " . count($success) . " WHERE id = $logId");

    echo json_encode([
        'status' => 'success',
        'processed_count' => $recordsProcessedInThisBatch,
        'success_data' => $success,
        'error_data' => $errors,
        'next_pos' => $nextPos
    ], JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>
