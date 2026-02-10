<?php
session_start();
include 'dbconnection.php';
mysqli_set_charset($con, 'utf8mb4');

// Auth Check
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

// Create Upload Logs Table if not exists
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

function participant_bulk_template_data() {
    $headers = ['First Name', 'Last Name', 'Nationality', 'Email', 'Profession', 'Organization', 'Category', 'Password', 'Contact No'];
    $rows = [
        ['John', 'Doe', 'United States', 'john.doe@example.com', 'Manager', 'Acme Corp', 'Delegate', 'securePass123', '+1234567890'],
        ['Jane', 'Smith', 'United Kingdom', 'jane.smith@test.com', 'Director', 'Global Ltd', 'VIP', '', '+447700900000']
    ];
    return [$headers, $rows];
}

function participant_bulk_output_csv_template() {
    list($headers, $rows) = participant_bulk_template_data();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="user_registration_template.csv"');
    $fp = fopen('php://output', 'w');
    fputcsv($fp, $headers);
    foreach ($rows as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);
    exit;
}

function participant_bulk_escape_xml($value) {
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function participant_bulk_build_sheet_xml($headers, $rows) {
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">';
    $xml .= '<sheetData>';
    $rowIndex = 1;
    $allRows = array_merge([$headers], $rows);
    foreach ($allRows as $row) {
        $xml .= '<row r="' . $rowIndex . '">';
        foreach ($row as $cell) {
            $xml .= '<c t="inlineStr"><is><t>' . participant_bulk_escape_xml($cell) . '</t></is></c>';
        }
        $xml .= '</row>';
        $rowIndex++;
    }
    $xml .= '</sheetData></worksheet>';
    return $xml;
}

function participant_bulk_output_xlsx_template() {
    if (!class_exists('ZipArchive')) {
        header('HTTP/1.1 500 Internal Server Error');
        echo 'Server does not support ZipArchive for XLSX generation.';
        exit;
    }
    list($headers, $rows) = participant_bulk_template_data();
    $zip = new ZipArchive();
    $tmpFile = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip->open($tmpFile, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Users" sheetId="1" r:id="rId1"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
    $sheetXml = participant_bulk_build_sheet_xml($headers, $rows);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="user_registration_template.xlsx"');
    header('Content-Length: ' . filesize($tmpFile));
    readfile($tmpFile);
    unlink($tmpFile);
    exit;
}

function participant_bulk_convert_xlsx_to_csv($xlsxPath, $csvPath) {
    if (!class_exists('ZipArchive')) {
        return false;
    }
    $zip = new ZipArchive();
    if ($zip->open($xlsxPath) !== true) {
        return false;
    }
    $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if ($sheetXml === false) {
        $zip->close();
        return false;
    }
    $sharedStrings = [];
    $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
    if ($sharedXml !== false) {
        $sx = simplexml_load_string($sharedXml);
        if ($sx !== false && isset($sx->si)) {
            foreach ($sx->si as $idx => $si) {
                $text = '';
                if (isset($si->t)) {
                    $text = (string)$si->t;
                } elseif (isset($si->r)) {
                    foreach ($si->r as $run) {
                        $text .= (string)$run->t;
                    }
                }
                $sharedStrings[(int)$idx] = $text;
            }
        }
    }
    $zip->close();
    $sheet = simplexml_load_string($sheetXml);
    if ($sheet === false) {
        return false;
    }
    $rows = [];
    if (isset($sheet->sheetData->row)) {
        foreach ($sheet->sheetData->row as $row) {
            $rowData = [];
            if (isset($row->c)) {
                foreach ($row->c as $c) {
                    $type = isset($c['t']) ? (string)$c['t'] : '';
                    $value = '';
                    if ($type === 's') {
                        $idx = isset($c->v) ? (int)$c->v : -1;
                        $value = $idx >= 0 && isset($sharedStrings[$idx]) ? $sharedStrings[$idx] : '';
                    } elseif ($type === 'inlineStr') {
                        if (isset($c->is->t)) {
                            $value = (string)$c->is->t;
                        } else {
                            $value = isset($c->v) ? (string)$c->v : '';
                        }
                    } else {
                        $value = isset($c->v) ? (string)$c->v : '';
                    }
                    $rowData[] = $value;
                }
            }
            $rows[] = $rowData;
        }
    }
    $fp = fopen($csvPath, 'w');
    if (!$fp) {
        return false;
    }
    foreach ($rows as $row) {
        fputcsv($fp, $row);
    }
    fclose($fp);
    return true;
}

// --- Action: Download Template ---
if (isset($_GET['action']) && $_GET['action'] == 'download_template') {
    $format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';
    if ($format === 'xlsx') {
        participant_bulk_output_xlsx_template();
    } else {
        participant_bulk_output_csv_template();
    }
}

// --- Action: Upload File ---
if (isset($_POST['action']) && $_POST['action'] == 'upload_file') {
    header('Content-Type: application/json');
    
    try {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] != 0) {
            throw new Exception("File upload failed.");
        }
        
        $file = $_FILES['csv_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['csv', 'xlsx'])) {
            throw new Exception("Only CSV or Excel (.xlsx) files are allowed.");
        }
        
        $filename = 'bulk_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception("Failed to save file.");
        }
        
        if ($ext === 'xlsx') {
            $csvName = preg_replace('/\.xlsx$/', '.csv', $filename);
            $csvPath = $uploadDir . $csvName;
            $ok = participant_bulk_convert_xlsx_to_csv($targetPath, $csvPath);
            unlink($targetPath);
            if (!$ok) {
                if (file_exists($csvPath)) {
                    unlink($csvPath);
                }
                throw new Exception("Could not read Excel file. Please use the provided template or upload CSV.");
            }
            $filename = $csvName;
            $targetPath = $csvPath;
        }
        
        // Count total rows
        $lineCount = 0;
        $handle = fopen($targetPath, "r");
        while(!feof($handle)){
            $line = fgets($handle);
            if(trim($line) != '') $lineCount++;
        }
        fclose($handle);
        $totalRecords = $lineCount - 1; // Subtract header
        
        if ($totalRecords < 1) {
            unlink($targetPath);
            throw new Exception("File is empty or contains only headers.");
        }

        // Log attempt
        $adminId = $_SESSION['id'];
        $stmt = mysqli_prepare($con, "INSERT INTO upload_logs (admin_id, filename, total_records, status) VALUES (?, ?, ?, 'processing')");
        mysqli_stmt_bind_param($stmt, 'isi', $adminId, $filename, $totalRecords);
        mysqli_stmt_execute($stmt);
        $logId = mysqli_insert_id($con);
        
        echo json_encode([
            'status' => 'success',
            'filename' => $filename,
            'total_records' => $totalRecords,
            'log_id' => $logId
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// --- Action: Process Batch ---
if (isset($_POST['action']) && $_POST['action'] == 'process_batch') {
    header('Content-Type: application/json');
    
    $filename = $_POST['filename'];
    $offset = intval($_POST['offset']);
    $limit = intval($_POST['limit']);
    $logId = intval($_POST['log_id']);
    
    $filePath = $uploadDir . basename($filename);
    
    if (!file_exists($filePath)) {
        echo json_encode(['status' => 'error', 'message' => 'File not found.']);
        exit;
    }
    
    $handle = fopen($filePath, "r");
    
    // Get Headers
    $headers = fgetcsv($handle);
    $headerMap = array_flip(array_map('strtolower', array_map('trim', $headers)));
    
    // Required columns
    $required = ['first name', 'last name', 'email', 'category'];
    foreach ($required as $req) {
        if (!isset($headerMap[$req])) {
            echo json_encode(['status' => 'error', 'message' => "Missing required column: $req"]);
            fclose($handle);
            exit;
        }
    }
    
    // Seek to offset
    // fseek doesn't work well with CSV lines, so we skip lines loop
    // But for performance with large files, we should use line numbers.
    // Since we opened the file fresh, we are at line 1 (after header).
    // We need to skip $offset lines.
    
    for ($i = 0; $i < $offset; $i++) {
        fgets($handle);
    }
    
    $processed = 0;
    $success = [];
    $errors = [];
    
    while ($processed < $limit && ($data = fgetcsv($handle)) !== FALSE) {
        $processed++;
        $rowNum = $offset + $processed + 1; // +1 for header
        
        // Map data
        $row = [];
        foreach ($headerMap as $key => $index) {
            $row[$key] = isset($data[$index]) ? trim($data[$index]) : '';
        }
        
        $fname = $row['first name'];
        $lname = $row['last name'];
        $email = $row['email'];
        $category = $row['category'];
        $password = !empty($row['password']) ? $row['password'] : substr(md5(uniqid()), 0, 8);
        $nationality = isset($row['nationality']) ? $row['nationality'] : '';
        $profession = isset($row['profession']) ? $row['profession'] : '';
        $organization = isset($row['organization']) ? $row['organization'] : '';
        $contact = isset($row['contact no']) ? $row['contact no'] : '';
        
        // Validation
        if (empty($fname) || empty($lname) || empty($email) || empty($category)) {
            $errors[] = ['row' => $rowNum, 'email' => $email, 'reason' => 'Missing required fields'];
            continue;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['row' => $rowNum, 'email' => $email, 'reason' => 'Invalid email format'];
            continue;
        }
        
        // Check Duplicate
        $stmt = mysqli_prepare($con, "SELECT id FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $errors[] = ['row' => $rowNum, 'email' => $email, 'reason' => 'Email already exists'];
            continue;
        }
        mysqli_stmt_close($stmt);
        
        // Insert
        $enc_password = password_hash($password, PASSWORD_DEFAULT);
        
        if ($hasFullname) {
             $insert = mysqli_prepare($con, "INSERT INTO users (fname, lname, fullname, email, profession, organization, category, password, contactno, posting_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
             mysqli_stmt_bind_param($insert, 'sssssssss', $fname, $lname, $fullname, $email, $profession, $organization, $category, $enc_password, $contact);
        } else {
             $insert = mysqli_prepare($con, "INSERT INTO users (fname, lname, email, profession, organization, category, password, contactno, posting_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
             mysqli_stmt_bind_param($insert, 'ssssssss', $fname, $lname, $email, $profession, $organization, $category, $enc_password, $contact);
        }
        
        if (mysqli_stmt_execute($insert)) {
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
            @mail($email, $subject, $message, $headers);
            
            $success[] = ['row' => $rowNum, 'id' => $uid, 'email' => $email, 'name' => "$fname $lname"];
        } else {
            $errors[] = ['row' => $rowNum, 'email' => $email, 'reason' => 'Database error: ' . mysqli_error($con)];
        }
    }
    
    fclose($handle);
    
    // Update Log stats
    // We need to fetch current stats and add
    // Ideally we update at the end, but for now we just return batch stats
    // The frontend aggregates them.
    
    echo json_encode([
        'status' => 'success',
        'processed_count' => $processed,
        'success_data' => $success,
        'error_data' => $errors,
        'is_complete' => ($processed < $limit) // If we read fewer than limit, we are done
    ]);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Bulk User Upload</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
    <style>
        .step-container { border: 1px solid #eee; padding: 20px; margin-bottom: 20px; border-radius: 5px; background: #fff; }
        .step-title { font-size: 18px; font-weight: bold; margin-bottom: 15px; border-bottom: 2px solid #4ecdc4; padding-bottom: 10px; display: inline-block; }
        .progress { height: 25px; margin-top: 10px; display: none; }
        .log-box { max-height: 300px; overflow-y: auto; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; display: none; margin-top: 15px; }
        .report-box { max-height: 400px; overflow-y: auto; overflow-x: auto; background: #fff; padding: 10px; border: 1px solid #ddd; display: none; margin-top: 15px; }
        .log-entry { padding: 5px; border-bottom: 1px solid #eee; font-size: 12px; }
        .log-success { color: green; }
        .log-error { color: red; }
        .stats-box { display: flex; gap: 20px; margin-top: 10px; }
        .stat-item { padding: 10px; background: #eee; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>
<section id="container">
    <header class="header black-bg">
        <div class="sidebar-toggle-box">
            <div class="fa fa-bars tooltips" data-placement="right" data-original-title="Toggle Navigation"></div>
        </div>
        <a href="#" class="logo"><b>Admin Dashboard</b></a>
        <div class="top-menu">
            <ul class="nav pull-right top-menu">
                <li><a class="logout" href="logout.php">Logout</a></li>
            </ul>
        </div>
    </header>
    
    <aside>
        <div id="sidebar" class="nav-collapse ">
            <ul class="sidebar-menu" id="nav-accordion">
                <p class="centered"><a href="#"><img src="assets/img/ui-sam.jpg" class="img-circle" width="60"></a></p>
                <h5 class="centered"><?php echo $_SESSION['login'];?></h5>
                <li class="mt"><a href="change-password.php"><i class="fa fa-file"></i><span>Change Password</span></a></li>
                <li class="sub-menu"><a href="manage-users.php"><i class="fa fa-users"></i><span>Manage Users</span></a></li>
                <li class="sub-menu"><a href="bulk-upload.php" class="active"><i class="fa fa-upload"></i><span>Bulk Upload</span></a></li>
            </ul>
        </div>
    </aside>

    <section id="main-content">
        <section class="wrapper">
            <h3><i class="fa fa-angle-right"></i> Bulk User Upload</h3>
            
            <!-- Step 1: Template -->
            <div class="step-container">
                <div class="step-title">1. Download Template</div>
                <p>Download the official template to ensure your data is formatted correctly.</p>
                <a href="bulk-upload.php?action=download_template&amp;format=csv" class="btn btn-theme"><i class="fa fa-download"></i> Download CSV Template</a>
                <a href="bulk-upload.php?action=download_template&amp;format=xlsx" class="btn btn-theme" style="margin-left:10px;"><i class="fa fa-download"></i> Download Excel Template (.xlsx)</a>
                <br><br>
                <div class="alert alert-info">
                    <strong>Instructions:</strong><br>
                    - Do not change the header names.<br>
                    - <b>Email</b> and <b>Category</b> are mandatory.<br>
                    - If <b>Password</b> is left empty, a secure random password will be generated.<br>
                    - Dates should be formatted as YYYY-MM-DD (if applicable).
                </div>
            </div>

            <!-- Step 2: Upload -->
            <div class="step-container">
                <div class="step-title">2. Upload & Process</div>
                <p>Select your filled CSV or Excel file to begin the bulk upload process.</p>
                
                <form id="uploadForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_file">
                    <div class="form-group">
                        <input type="file" name="csv_file" id="csv_file" accept=".csv,.xlsx" class="form-control" style="width: 50%;">
                    </div>
                    <button type="submit" class="btn btn-success" id="btnUpload"><i class="fa fa-upload"></i> Upload & Start Processing</button>
                </form>

                <div class="progress">
                    <div class="progress-bar progress-bar-striped active" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%">
                        <span id="progress-text">0%</span>
                    </div>
                </div>
                
                <div class="stats-box" id="statsBox" style="display:none;">
                    <div class="stat-item" style="color:green">Success: <span id="countSuccess">0</span></div>
                    <div class="stat-item" style="color:red">Failed: <span id="countFailed">0</span></div>
                    <div class="stat-item">Processed: <span id="countProcessed">0</span> / <span id="countTotal">0</span></div>
                </div>

                <div class="log-box" id="logBox"></div>
                <div id="reportBox" class="report-box"></div>
                
                <button type="button" class="btn btn-primary" id="btnDownloadReport" style="display:none; margin-top:15px;"><i class="fa fa-list"></i> Show Report</button>
            </div>

        </section>
    </section>
</section>

<script src="assets/js/jquery.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script class="include" type="text/javascript" src="assets/js/jquery.dcjqaccordion.2.7.js"></script>
<script src="assets/js/jquery.scrollTo.min.js"></script>
<script src="assets/js/jquery.nicescroll.js"></script>
<script src="assets/js/common-scripts.js"></script>

<script>
jQuery(document).ready(function($) {
    console.log("Bulk Upload Script Loaded");

    let totalRecords = 0;
    let processedRecords = 0;
    let successCount = 0;
    let failCount = 0;
    let currentFilename = '';
    let currentLogId = 0;
    let allErrors = [];
    let allSuccess = [];
    
    function escapeHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
    
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();
        console.log("Form submitted");
        
        let fileInput = $('#csv_file')[0];
        if (fileInput.files.length === 0) {
            alert("Please select a file");
            return;
        }
        
        // Reset UI
        $('.progress').show();
        $('.progress-bar').css('width', '0%').text('Uploading...');
        $('.progress-bar').addClass('active').removeClass('progress-bar-success progress-bar-danger');
        $('#logBox').show().html('');
        $('#statsBox').show();
        $('#btnUpload').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Uploading...');
        $('#btnDownloadReport').hide();
        $('#reportBox').hide().html('');
        
        successCount = 0;
        failCount = 0;
        processedRecords = 0;
        allErrors = [];
        allSuccess = [];
        updateStats();
        
        let formData = new FormData(this);
        
        console.log("Sending upload request...");
        $.ajax({
            url: 'bulk-upload.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                console.log("Upload response:", response);
                if (response.status === 'success') {
                    currentFilename = response.filename;
                    totalRecords = response.total_records;
                    currentLogId = response.log_id;
                    $('#countTotal').text(totalRecords);
                    $('.progress-bar').text('Processing 0%');
                    $('#btnUpload').html('<i class="fa fa-spinner fa-spin"></i> Processing...');
                    
                    processBatch(0);
                } else {
                    console.error("Upload error:", response.message);
                    alert("Upload failed: " + response.message);
                    $('#btnUpload').prop('disabled', false).html('<i class="fa fa-upload"></i> Upload & Start Processing');
                    $('.progress-bar').text('Failed').removeClass('active').addClass('progress-bar-danger');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                console.log("Response Text:", xhr.responseText);
                alert("System error during upload. Check console for details.");
                $('#btnUpload').prop('disabled', false).html('<i class="fa fa-upload"></i> Upload & Start Processing');
            }
        });
    });
    
    function processBatch(offset) {
        let limit = 20; // Process 20 at a time
        console.log("Processing batch: Offset " + offset + ", Limit " + limit);
        
        $.ajax({
            url: 'bulk-upload.php',
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'process_batch',
                filename: currentFilename,
                offset: offset,
                limit: limit,
                log_id: currentLogId
            },
            success: function(response) {
                console.log("Batch response:", response);
                if (response.status === 'success') {
                    // Update stats
                    processedRecords += response.processed_count;
                    
                    // Handle Successes
                    if (response.success_data) {
                        response.success_data.forEach(item => {
                            successCount++;
                            allSuccess.push(item);
                            $('#logBox').append('<div class="log-entry log-success"><i class="fa fa-check"></i> Row ' + item.row + ': Registered ' + item.email + ' (Ref: ' + item.id + ')</div>');
                        });
                    }
                    
                    // Handle Errors
                    if (response.error_data) {
                        response.error_data.forEach(item => {
                            failCount++;
                            allErrors.push(item);
                            $('#logBox').append('<div class="log-entry log-error"><i class="fa fa-times"></i> Row ' + item.row + ': Failed ' + item.email + ' - ' + item.reason + '</div>');
                        });
                    }
                    
                    updateStats();
                    
                    // Scroll log to bottom
                    let logBox = document.getElementById("logBox");
                    logBox.scrollTop = logBox.scrollHeight;
                    
                    // Update Progress
                    let percent = 0;
                    if (totalRecords > 0) {
                        percent = Math.round((processedRecords / totalRecords) * 100);
                    }
                    $('.progress-bar').css('width', percent + '%').text(percent + '%');
                    
                    if (!response.is_complete && processedRecords < totalRecords) {
                        // Next batch
                        processBatch(offset + response.processed_count);
                    } else {
                        // Complete
                        finishProcessing();
                    }
                } else {
                    console.error("Batch error:", response.message);
                    $('#logBox').append('<div class="log-entry log-error">System Error: ' + response.message + '</div>');
                    finishProcessing();
                }
            },
            error: function(xhr, status, error) {
                console.error("Batch AJAX Error:", status, error);
                console.log("Response Text:", xhr.responseText);
                $('#logBox').append('<div class="log-entry log-error">Network Error. Retrying...</div>');
                // Simple retry mechanism could be added here, for now just stop
                finishProcessing();
            }
        });
    }
    
    function updateStats() {
        $('#countSuccess').text(successCount);
        $('#countFailed').text(failCount);
        $('#countProcessed').text(processedRecords);
    }
    
    function finishProcessing() {
        console.log("Processing finished");
        $('.progress-bar').removeClass('active').addClass('progress-bar-success').text('Completed');
        $('#btnUpload').prop('disabled', false).html('<i class="fa fa-upload"></i> Upload & Start Processing');
        $('#btnDownloadReport').show();
        alert("Processing Complete!");
    }
    
    $('#btnDownloadReport').click(function() {
        let html = '';
        html += '<div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">';
        html += '<strong>Bulk Upload Report</strong>';
        html += '<a href="#" id="downloadCsv" class="btn btn-xs btn-default"><i class="fa fa-download"></i> Download CSV</a>';
        html += '</div>';
        html += '<table class="table table-bordered table-striped table-condensed">';
        html += '<thead><tr><th>#</th><th>Type</th><th>Row</th><th>Email</th><th>Details</th><th>RefID</th></tr></thead><tbody>';
        let idx = 1;
        allSuccess.forEach(function(item) {
            html += '<tr><td>'+idx+'</td><td style="color:green">Success</td><td>'+escapeHtml(item.row)+'</td><td>'+escapeHtml(item.email)+'</td><td>'+escapeHtml(item.name)+'</td><td>'+escapeHtml(item.id)+'</td></tr>';
            idx++;
        });
        allErrors.forEach(function(item) {
            html += '<tr><td>'+idx+'</td><td style="color:red">Error</td><td>'+escapeHtml(item.row)+'</td><td>'+escapeHtml(item.email)+'</td><td>'+escapeHtml(item.reason)+'</td><td>N/A</td></tr>';
            idx++;
        });
        html += '</tbody></table>';
        $('#reportBox').html(html).show();
        $('html, body').animate({ scrollTop: $('#reportBox').offset().top - 100 }, 300);
        
        $('#downloadCsv').off('click').on('click', function(e){
            e.preventDefault();
            let csv = "data:text/csv;charset=utf-8,";
            csv += "Type,Row,Email,Details,RefID\n";
            allSuccess.forEach(function(item) {
                csv += 'Success,'+item.row+','+item.email+','+String(item.name).replace(/,/g,';')+','+item.id+"\n";
            });
            allErrors.forEach(function(item) {
                csv += 'Error,'+item.row+','+item.email+','+String(item.reason).replace(/,/g,';')+',N/A'+"\n";
            });
            var encoded = encodeURI(csv);
            var a = document.createElement('a');
            a.setAttribute('href', encoded);
            a.setAttribute('download', 'bulk_upload_report_' + new Date().getTime() + '.csv');
            document.body.appendChild(a);
            a.click();
            a.remove();
        });
    });
});
</script>

</body>
</html>
