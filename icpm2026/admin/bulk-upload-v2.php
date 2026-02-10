<?php
session_start();

require_once 'dbconnection.php';
require_once 'includes/auth_helper.php';
$currentPage = 'bulk-upload';

if (!isset($_SESSION['id']) || strlen((string)$_SESSION['id']) === 0) {
    header('location:logout.php');
    exit();
}

require_permission('bulk_upload');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($con instanceof mysqli) {
    mysqli_set_charset($con, 'utf8mb4');
}

function v2_json($payload, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
    exit;
}

function v2_fail($message, $statusCode = 400, $extra = []) {
    v2_json(array_merge(['status' => 'error', 'message' => $message], $extra), $statusCode);
}

function v2_ok($extra = []) {
    v2_json(array_merge(['status' => 'success'], $extra));
}

function v2_token_is_valid($token) {
    return is_string($token) && preg_match('/^[a-f0-9]{32}$/', $token);
}

function v2_make_token() {
    return bin2hex(random_bytes(16));
}

function v2_dir() {
    $dir = __DIR__ . '/uploads/bulk_v2/';
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    return $dir;
}

function v2_paths($token) {
    $dir = v2_dir();
    return [
        'dir' => $dir,
        'part' => $dir . $token . '.part',
        'csv' => $dir . $token . '.csv',
        'meta' => $dir . $token . '.meta.json',
        'progress' => $dir . $token . '.progress.json',
        'errors' => $dir . $token . '.errors.csv',
    ];
}

function v2_read_json_file($path) {
    if (!file_exists($path)) return null;
    $raw = @file_get_contents($path);
    if ($raw === false) return null;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function v2_write_json_file($path, $data) {
    $tmp = $path . '.tmp';
    $json = json_encode($data);
    if ($json === false) return false;
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    return @rename($tmp, $path);
}

function v2_normalize_header($h) {
    $h = strtolower(trim((string)$h));
    return preg_replace('/[^a-z0-9]+/', '', $h);
}

function v2_map_headers($headers) {
    $map = [];
    foreach ($headers as $idx => $h) {
        $key = v2_normalize_header($h);
        if ($key === '') continue;
        if (!isset($map[$key])) {
            $map[$key] = $idx;
        }
    }

    $aliases = [
        'firstname' => ['firstname', 'first', 'fname', 'first_name', 'first-name'],
        'lastname' => ['lastname', 'last', 'lname', 'last_name', 'last-name', 'surname'],
        'nationality' => ['nationality', 'country', 'nation'],
        'email' => ['email', 'emailid', 'emailaddress', 'mail'],
        'profession' => ['profession', 'jobtitle', 'job', 'title'],
        'organization' => ['organization', 'organisation', 'company', 'institution'],
        'category' => ['category', 'type'],
        'password' => ['password', 'pass', 'pwd'],
        'contactno' => ['contactno', 'contact', 'phone', 'phoneno', 'mobile', 'mobileno'],
    ];

    $out = [];
    foreach ($aliases as $canon => $keys) {
        foreach ($keys as $k) {
            $nk = v2_normalize_header($k);
            if (isset($map[$nk])) {
                $out[$canon] = $map[$nk];
                break;
            }
        }
    }
    return $out;
}

function v2_append_error($errorPath, $rowNumber, $email, $reason) {
    $isNew = !file_exists($errorPath);
    $fp = @fopen($errorPath, 'ab');
    if (!$fp) return;
    if ($isNew) {
        fputcsv($fp, ['row', 'email', 'reason']);
    }
    fputcsv($fp, [$rowNumber, (string)$email, (string)$reason]);
    fclose($fp);
}

if (isset($_GET['action']) && $_GET['action'] === 'download_template') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="Exhibitor_registration_template.csv"');

    $fp = fopen('php://output', 'w');
    fputcsv($fp, ['First Name', 'Last Name', 'Nationality', 'Email', 'Profession', 'Organization', 'Category', 'Password', 'Contact No']);
    fputcsv($fp, ['John', 'Doe', 'United States', 'john.doe@example.com', 'Manager', 'Acme Corp', 'Delegate', 'securePass123', '+1234567890']);
    fputcsv($fp, ['Jane', 'Smith', 'United Kingdom', 'jane.smith@test.com', 'Director', 'Global Ltd', 'VIP', '', '+447700900000']);
    fclose($fp);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'download_error_report') {
    $token = isset($_GET['token']) ? (string)$_GET['token'] : '';
    if (!v2_token_is_valid($token)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Invalid token';
        exit;
    }
    $paths = v2_paths($token);
    if (!file_exists($paths['errors'])) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'No error report available';
        exit;
    }
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="bulk_upload_errors_' . $token . '.csv"');
    readfile($paths['errors']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        v2_fail('Invalid CSRF token', 403);
    }

    $action = (string)$_POST['action'];
    $maxBytes = 200 * 1024 * 1024;
    $maxChunkBytes = 5 * 1024 * 1024;

    if ($action === 'upload_chunk') {
        if (!isset($_FILES['chunk']) || !is_array($_FILES['chunk'])) {
            v2_fail('Missing chunk upload');
        }

        $chunk = $_FILES['chunk'];
        if ((int)($chunk['error'] ?? 1) !== 0) {
            v2_fail('Chunk upload error', 400, ['code' => (int)($chunk['error'] ?? 1)]);
        }

        $token = isset($_POST['token']) ? (string)$_POST['token'] : '';
        if ($token === '') {
            $token = v2_make_token();
        }
        if (!v2_token_is_valid($token)) {
            v2_fail('Invalid token');
        }

        $chunkIndex = isset($_POST['chunk_index']) ? (int)$_POST['chunk_index'] : -1;
        $totalChunks = isset($_POST['total_chunks']) ? (int)$_POST['total_chunks'] : 0;
        $totalSize = isset($_POST['total_size']) ? (int)$_POST['total_size'] : 0;
        $originalName = isset($_POST['original_name']) ? (string)$_POST['original_name'] : '';

        if ($chunkIndex < 0 || $totalChunks < 1 || $chunkIndex >= $totalChunks) {
            v2_fail('Invalid chunk index');
        }
        if ($totalSize < 1 || $totalSize > $maxBytes) {
            v2_fail('File too large');
        }
        if ($originalName === '' || strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'csv') {
            v2_fail('Only CSV files are allowed');
        }

        $tmpSize = (int)($chunk['size'] ?? 0);
        if ($tmpSize < 1 || $tmpSize > $maxChunkBytes) {
            v2_fail('Invalid chunk size');
        }

        $paths = v2_paths($token);
        $meta = v2_read_json_file($paths['meta']);
        if (!$meta) {
            $meta = [
                'token' => $token,
                'original_name' => $originalName,
                'total_chunks' => $totalChunks,
                'total_size' => $totalSize,
                'next_index' => 0,
                'created_at' => time(),
            ];
            v2_write_json_file($paths['meta'], $meta);
        } else {
            if ((int)$meta['total_chunks'] !== $totalChunks || (int)$meta['total_size'] !== $totalSize) {
                v2_fail('Upload metadata mismatch');
            }
        }

        if ((int)$meta['next_index'] !== $chunkIndex) {
            v2_fail('Out of order chunk', 409, ['expected' => (int)$meta['next_index']]);
        }

        $in = @fopen($chunk['tmp_name'], 'rb');
        if (!$in) {
            v2_fail('Failed reading chunk');
        }
        $out = @fopen($paths['part'], 'ab');
        if (!$out) {
            fclose($in);
            v2_fail('Failed writing chunk');
        }
        if (!flock($out, LOCK_EX)) {
            fclose($in);
            fclose($out);
            v2_fail('Failed locking upload');
        }

        $written = 0;
        while (!feof($in)) {
            $buf = fread($in, 1024 * 1024);
            if ($buf === false) break;
            $n = fwrite($out, $buf);
            if ($n === false) break;
            $written += $n;
        }
        fflush($out);
        flock($out, LOCK_UN);
        fclose($in);
        fclose($out);

        if ($written !== $tmpSize) {
            v2_fail('Chunk write failed');
        }

        $meta['next_index'] = (int)$meta['next_index'] + 1;
        v2_write_json_file($paths['meta'], $meta);

        $uploadDone = ((int)$meta['next_index'] >= (int)$meta['total_chunks']);
        if ($uploadDone) {
            if (file_exists($paths['csv'])) {
                @unlink($paths['csv']);
            }
            @rename($paths['part'], $paths['csv']);
        }

        v2_ok([
            'token' => $token,
            'received_chunks' => (int)$meta['next_index'],
            'total_chunks' => (int)$meta['total_chunks'],
            'upload_done' => $uploadDone,
        ]);
    }

    if ($action === 'start_processing') {
        $token = isset($_POST['token']) ? (string)$_POST['token'] : '';
        if (!v2_token_is_valid($token)) v2_fail('Invalid token');
        $paths = v2_paths($token);
        if (!file_exists($paths['csv'])) v2_fail('Uploaded file not found', 404);

        $fp = @fopen($paths['csv'], 'rb');
        if (!$fp) v2_fail('Failed opening CSV');
        $headers = fgetcsv($fp);
        if (!is_array($headers) || empty($headers)) {
            fclose($fp);
            v2_fail('Invalid CSV header row');
        }

        $headerMap = v2_map_headers($headers);
        $required = ['firstname', 'lastname', 'email', 'category'];
        $missing = [];
        foreach ($required as $r) {
            if (!isset($headerMap[$r])) $missing[] = $r;
        }
        $pos = ftell($fp);
        fclose($fp);

        if (!empty($missing)) {
            v2_fail('Missing required columns', 422, ['missing' => $missing]);
        }

        $totalBytes = filesize($paths['csv']);
        $progress = [
            'token' => $token,
            'status' => 'processing',
            'total_bytes' => (int)$totalBytes,
            'file_pos' => (int)$pos,
            'row_index' => 1,
            'processed_rows' => 0,
            'inserted' => 0,
            'duplicates' => 0,
            'errors' => 0,
            'done' => false,
            'started_at' => time(),
            'updated_at' => time(),
        ];
        v2_write_json_file($paths['progress'], $progress);
        v2_write_json_file($paths['meta'], array_merge(v2_read_json_file($paths['meta']) ?: [], [
            'header_map' => $headerMap,
            'headers' => $headers,
        ]));

        v2_ok(['token' => $token]);
    }

    if ($action === 'process_batch') {
        $token = isset($_POST['token']) ? (string)$_POST['token'] : '';
        if (!v2_token_is_valid($token)) v2_fail('Invalid token');
        $paths = v2_paths($token);

        $progress = v2_read_json_file($paths['progress']);
        if (!$progress) v2_fail('Processing not started', 409);
        if (!empty($progress['done'])) {
            v2_ok(['done' => true, 'progress' => $progress]);
        }

        $meta = v2_read_json_file($paths['meta']) ?: [];
        $headerMap = isset($meta['header_map']) && is_array($meta['header_map']) ? $meta['header_map'] : [];
        if (empty($headerMap)) v2_fail('Header mapping missing', 409);
        if (!file_exists($paths['csv'])) v2_fail('CSV missing', 404);

        $batchSize = isset($_POST['batch_size']) ? (int)$_POST['batch_size'] : 100;
        if ($batchSize < 10) $batchSize = 10;
        if ($batchSize > 500) $batchSize = 500;

        $fp = @fopen($paths['csv'], 'rb');
        if (!$fp) v2_fail('Failed opening CSV');
        $seek = isset($progress['file_pos']) ? (int)$progress['file_pos'] : 0;
        if ($seek > 0) {
            fseek($fp, $seek);
        } else {
            fgetcsv($fp);
            $progress['row_index'] = 1;
        }

        $checkStmt = mysqli_prepare($con, "SELECT id FROM users WHERE email = ?");
        $insertStmt = mysqli_prepare($con, "INSERT INTO users (fname, lname, nationality, email, profession, organization, category, password, contactno) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if (!$checkStmt || !$insertStmt) {
            fclose($fp);
            v2_fail('Database prepare failed', 500);
        }

        $processedThisBatch = 0;
        $totalBytes = filesize($paths['csv']);
        $rowNumber = (int)($progress['row_index'] ?? 1);

        set_time_limit(30);

        while ($processedThisBatch < $batchSize) {
            $row = fgetcsv($fp);
            if ($row === false) {
                $progress['done'] = true;
                $progress['status'] = 'completed';
                break;
            }

            $rowNumber++;
            $progress['row_index'] = $rowNumber;
            $progress['processed_rows'] = (int)$progress['processed_rows'] + 1;
            $processedThisBatch++;

            $get = function($key) use ($row, $headerMap) {
                if (!isset($headerMap[$key])) return '';
                $idx = (int)$headerMap[$key];
                return isset($row[$idx]) ? trim((string)$row[$idx]) : '';
            };

            $fname = $get('firstname');
            $lname = $get('lastname');
            $nationality = $get('nationality');
            $email = $get('email');
            $profession = $get('profession');
            $organization = $get('organization');
            $category = $get('category');
            $password = $get('password');
            $contactno = $get('contactno');

            if ($email === '' || $fname === '' || $lname === '' || $category === '') {
                $progress['errors'] = (int)$progress['errors'] + 1;
                v2_append_error($paths['errors'], $rowNumber, $email, 'Missing required fields');
                continue;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $progress['errors'] = (int)$progress['errors'] + 1;
                v2_append_error($paths['errors'], $rowNumber, $email, 'Invalid email format');
                continue;
            }

            mysqli_stmt_bind_param($checkStmt, 's', $email);
            mysqli_stmt_execute($checkStmt);
            mysqli_stmt_store_result($checkStmt);
            if (mysqli_stmt_num_rows($checkStmt) > 0) {
                $progress['duplicates'] = (int)$progress['duplicates'] + 1;
                v2_append_error($paths['errors'], $rowNumber, $email, 'Email already exists');
                mysqli_stmt_free_result($checkStmt);
                continue;
            }
            mysqli_stmt_free_result($checkStmt);

            if ($password === '') {
                $password = substr(bin2hex(random_bytes(8)), 0, 12);
            }
            $encPass = password_hash($password, PASSWORD_DEFAULT);

            mysqli_stmt_bind_param(
                $insertStmt,
                'sssssssss',
                $fname,
                $lname,
                $nationality,
                $email,
                $profession,
                $organization,
                $category,
                $encPass,
                $contactno
            );

            if (mysqli_stmt_execute($insertStmt)) {
                $progress['inserted'] = (int)$progress['inserted'] + 1;
            } else {
                $progress['errors'] = (int)$progress['errors'] + 1;
                v2_append_error($paths['errors'], $rowNumber, $email, 'DB error');
            }

            if ($processedThisBatch % 100 === 0) {
                usleep(20000);
            }
        }

        mysqli_stmt_close($checkStmt);
        mysqli_stmt_close($insertStmt);

        $progress['file_pos'] = (int)ftell($fp);
        $progress['total_bytes'] = (int)$totalBytes;
        $progress['updated_at'] = time();
        fclose($fp);

        v2_write_json_file($paths['progress'], $progress);

        $percent = 0;
        if ($totalBytes > 0) {
            $percent = (int)floor(min(100, max(0, ($progress['file_pos'] / $totalBytes) * 100)));
        }

        v2_ok([
            'done' => !empty($progress['done']),
            'progress_percent' => $percent,
            'progress' => $progress,
            'error_report_available' => file_exists($paths['errors']),
            'error_report_url' => 'bulk-upload-v2.php?action=download_error_report&token=' . $token,
        ]);
    }

    if ($action === 'get_progress') {
        $token = isset($_POST['token']) ? (string)$_POST['token'] : '';
        if (!v2_token_is_valid($token)) v2_fail('Invalid token');
        $paths = v2_paths($token);
        $progress = v2_read_json_file($paths['progress']);
        if (!$progress) v2_fail('No progress found', 404);
        v2_ok(['progress' => $progress]);
    }

    v2_fail('Unknown action', 400);
}

$pageTitle = "Admin | Bulk Upload V2";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
</head>
<body>
<section id="container">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <section id="main-content">
        <section class="wrapper">
            <h3><i class="fa fa-angle-right"></i> Bulk Upload (V2)</h3>

            <div class="row mt">
                <div class="col-lg-12">
                    <div class="content-panel" style="padding: 15px;">
                        <div class="alert alert-info" style="margin-bottom: 15px;">
                            <b>Steps:</b> Select files → Preview → Upload (chunked) → Import → Summary
                            <span class="pull-right">
                                <a href="bulk-upload-v2.php?action=download_template" class="btn btn-xs btn-default">Download Template</a>
                            </span>
                        </div>

                        <div id="step-1">
                            <h4><i class="fa fa-upload"></i> Step 1: Select CSV Files</h4>
                            <div id="dropzone" style="border: 2px dashed #aaa; border-radius: 6px; padding: 18px; text-align: center; background: #fafafa;">
                                <div style="font-size: 14px; margin-bottom: 8px;">Drag & drop CSV files here</div>
                                <div style="margin-bottom: 10px;">or</div>
                                <input type="file" id="file-input" accept=".csv" multiple class="form-control" style="max-width: 420px; margin: 0 auto;">
                                <div class="help-block" style="margin-top: 10px;">
                                    Max file size: <span id="max-file-size">200 MB</span>
                                </div>
                            </div>

                            <div id="file-errors" class="alert alert-danger" style="display:none; margin-top: 12px;"></div>

                            <div style="margin-top: 15px;">
                                <table class="table table-striped table-advance table-hover">
                                    <thead>
                                        <tr>
                                            <th>File</th>
                                            <th>Size</th>
                                            <th>Status</th>
                                            <th style="width: 240px;">Progress</th>
                                            <th>Inserted</th>
                                            <th>Duplicates</th>
                                            <th>Errors</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="file-list"></tbody>
                                </table>
                            </div>

                            <div style="margin-top: 10px;">
                                <button id="btn-preview" class="btn btn-theme" disabled>Preview & Confirm</button>
                                <a href="manage-users.php" class="btn btn-default">Back to Manage Users</a>
                            </div>
                        </div>

                        <div id="step-2" style="display:none;">
                            <h4><i class="fa fa-table"></i> Step 2: Preview & Confirm</h4>
                            <div class="alert alert-warning">
                                Preview shows the first 10 data rows from each file. Import starts only after confirmation.
                            </div>

                            <div id="preview-container"></div>

                            <div style="margin-top: 10px;">
                                <button id="btn-start" class="btn btn-success">Start Upload & Import</button>
                                <button id="btn-back" class="btn btn-default">Back</button>
                            </div>
                        </div>

                        <div id="step-3" style="display:none;">
                            <h4><i class="fa fa-refresh"></i> Step 3: Progress</h4>
                            <div class="alert alert-info">
                                Upload runs in chunks; import runs in batches to avoid timeouts.
                            </div>
                            <div id="overall-summary" class="alert alert-success" style="display:none;"></div>
                            <div>
                                <button id="btn-done" class="btn btn-theme" style="display:none;">Upload Another Batch</button>
                            </div>
                        </div>
                    </div>
                </div>
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
const CSRF_TOKEN = <?php echo json_encode($_SESSION['csrf_token']); ?>;
const MAX_BYTES = 200 * 1024 * 1024;
const CHUNK_BYTES = 2 * 1024 * 1024;

const state = {
  files: [],
  running: false,
  totals: { inserted: 0, duplicates: 0, errors: 0 }
};

function formatBytes(bytes) {
  const units = ['B','KB','MB','GB'];
  let b = bytes;
  let i = 0;
  while (b >= 1024 && i < units.length - 1) { b /= 1024; i++; }
  return `${b.toFixed(i === 0 ? 0 : 1)} ${units[i]}`;
}

function showError(msg) {
  const box = $('#file-errors');
  box.text(msg).show();
  setTimeout(() => box.fadeOut(200), 6000);
}

function normalizeHeader(h) {
  return String(h || '').trim().toLowerCase().replace(/[^a-z0-9]+/g, '');
}

function parseCSV(text, maxRows) {
  const rows = [];
  let row = [];
  let field = '';
  let i = 0;
  let inQuotes = false;

  while (i < text.length) {
    const c = text[i];
    if (inQuotes) {
      if (c === '"') {
        if (text[i + 1] === '"') { field += '"'; i += 2; continue; }
        inQuotes = false; i++; continue;
      }
      field += c; i++; continue;
    }

    if (c === '"') { inQuotes = true; i++; continue; }
    if (c === ',') { row.push(field); field = ''; i++; continue; }
    if (c === '\n' || c === '\r') {
      if (c === '\r' && text[i + 1] === '\n') i++;
      row.push(field); field = '';
      if (row.length > 1 || (row.length === 1 && row[0] !== '')) rows.push(row);
      row = [];
      i++;
      if (maxRows && rows.length >= maxRows) break;
      continue;
    }
    field += c; i++;
  }

  if (!maxRows || rows.length < maxRows) {
    if (field.length || row.length) {
      row.push(field);
      if (row.length > 1 || (row.length === 1 && row[0] !== '')) rows.push(row);
    }
  }
  return rows;
}

async function readPreview(file) {
  const slice = file.slice(0, Math.min(file.size, 250000));
  const text = await slice.text();
  const rows = parseCSV(text, 12);
  if (!rows.length) throw new Error('CSV appears empty');
  const headers = rows[0] || [];
  const data = rows.slice(1, 11);
  return { headers, data };
}

function validateFile(file) {
  const name = (file.name || '').toLowerCase();
  if (!name.endsWith('.csv')) return 'Only .csv files are allowed';
  if (file.size <= 0) return 'File is empty';
  if (file.size > MAX_BYTES) return 'File exceeds 200 MB';
  return '';
}

function addFiles(fileList) {
  const incoming = Array.from(fileList || []);
  for (const file of incoming) {
    const err = validateFile(file);
    if (err) { showError(`${file.name}: ${err}`); continue; }
    const id = `${Date.now()}_${Math.random().toString(16).slice(2)}`;
    state.files.push({
      id,
      file,
      status: 'Ready',
      uploadPercent: 0,
      importPercent: 0,
      token: null,
      inserted: 0,
      duplicates: 0,
      errors: 0,
      errorReportUrl: null,
      preview: null
    });
  }
  renderList();
}

function removeFile(id) {
  if (state.running) return;
  state.files = state.files.filter(f => f.id !== id);
  renderList();
}

function renderProgressBar(pct, label) {
  const safe = Math.max(0, Math.min(100, pct || 0));
  return `
    <div class="progress" style="margin-bottom: 6px;">
      <div class="progress-bar" role="progressbar" aria-valuenow="${safe}" aria-valuemin="0" aria-valuemax="100" style="width: ${safe}%;">
        ${label ? label : `${safe}%`}
      </div>
    </div>
  `;
}

function renderList() {
  const tbody = $('#file-list');
  tbody.empty();
  for (const f of state.files) {
    const actions = state.running
      ? ''
      : `<button class="btn btn-xs btn-danger" onclick="removeFile(${JSON.stringify(f.id)})">Remove</button>`;
    const errorBtn = f.errorReportUrl
      ? `<a class="btn btn-xs btn-default" href="${f.errorReportUrl}" target="_blank">Error Report</a>`
      : '';
    tbody.append(`
      <tr>
        <td>${$('<div>').text(f.file.name).html()}</td>
        <td>${formatBytes(f.file.size)}</td>
        <td>${$('<div>').text(f.status).html()}</td>
        <td>
          ${renderProgressBar(f.uploadPercent, `Upload: ${f.uploadPercent}%`)}
          ${renderProgressBar(f.importPercent, `Import: ${f.importPercent}%`)}
        </td>
        <td>${f.inserted}</td>
        <td>${f.duplicates}</td>
        <td>${f.errors}</td>
        <td>${errorBtn} ${actions}</td>
      </tr>
    `);
  }
  $('#btn-preview').prop('disabled', state.files.length === 0 || state.running);
}

async function buildPreviews() {
  for (const f of state.files) {
    try {
      f.status = 'Reading preview...';
      renderList();
      f.preview = await readPreview(f.file);

      const headers = f.preview.headers.map(normalizeHeader);
      const required = ['firstname','lastname','email','category'];
      const missing = required.filter(r => !headers.includes(r));
      if (missing.length) {
        f.status = `Missing columns: ${missing.join(', ')}`;
        f.errors = 1;
      } else {
        f.status = 'Preview ready';
      }
      renderList();
    } catch (e) {
      f.status = 'Preview failed';
      f.errors = 1;
      renderList();
      showError(`${f.file.name}: ${e.message || e}`);
    }
  }
}

function renderPreviewsUI() {
  const container = $('#preview-container');
  container.empty();
  for (const f of state.files) {
    const p = f.preview;
    const headerHtml = p ? p.headers.map(h => `<th>${$('<div>').text(h).html()}</th>`).join('') : '';
    const rowsHtml = p
      ? p.data.map(r => `<tr>${r.map(c => `<td>${$('<div>').text(c).html()}</td>`).join('')}</tr>`).join('')
      : '';
    container.append(`
      <div class="panel panel-default">
        <div class="panel-heading">
          <b>${$('<div>').text(f.file.name).html()}</b>
          <span class="pull-right">${$('<div>').text(f.status).html()}</span>
        </div>
        <div class="panel-body" style="overflow-x:auto;">
          ${p ? `
            <table class="table table-bordered table-condensed">
              <thead><tr>${headerHtml}</tr></thead>
              <tbody>${rowsHtml}</tbody>
            </table>
          ` : `<div class="text-danger">No preview available.</div>`}
        </div>
      </div>
    `);
  }
}

function goStep(step) {
  $('#step-1').toggle(step === 1);
  $('#step-2').toggle(step === 2);
  $('#step-3').toggle(step === 3);
}

async function uploadFileInChunks(fileItem) {
  const file = fileItem.file;
  const totalChunks = Math.ceil(file.size / CHUNK_BYTES);
  fileItem.status = 'Uploading...';
  fileItem.uploadPercent = 0;
  renderList();

  for (let i = 0; i < totalChunks; i++) {
    const start = i * CHUNK_BYTES;
    const end = Math.min(file.size, start + CHUNK_BYTES);
    const blob = file.slice(start, end);

    const fd = new FormData();
    fd.append('action', 'upload_chunk');
    fd.append('csrf_token', CSRF_TOKEN);
    if (fileItem.token) fd.append('token', fileItem.token);
    fd.append('chunk_index', String(i));
    fd.append('total_chunks', String(totalChunks));
    fd.append('total_size', String(file.size));
    fd.append('original_name', file.name);
    fd.append('chunk', blob, file.name + `.part${i}`);

    const res = await $.ajax({
      url: 'bulk-upload-v2.php',
      type: 'POST',
      data: fd,
      contentType: false,
      processData: false,
      dataType: 'json'
    });

    if (!res || res.status !== 'success') {
      throw new Error((res && res.message) ? res.message : 'Upload failed');
    }
    fileItem.token = res.token;
    fileItem.uploadPercent = Math.floor(((i + 1) / totalChunks) * 100);
    renderList();
  }
}

async function startProcessing(fileItem) {
  fileItem.status = 'Starting import...';
  fileItem.importPercent = 0;
  renderList();
  const res = await $.ajax({
    url: 'bulk-upload-v2.php',
    type: 'POST',
    dataType: 'json',
    data: { action: 'start_processing', csrf_token: CSRF_TOKEN, token: fileItem.token }
  });
  if (!res || res.status !== 'success') {
    throw new Error((res && res.message) ? res.message : 'Failed to start processing');
  }
}

async function processBatches(fileItem) {
  fileItem.status = 'Importing...';
  renderList();
  while (true) {
    const res = await $.ajax({
      url: 'bulk-upload-v2.php',
      type: 'POST',
      dataType: 'json',
      data: { action: 'process_batch', csrf_token: CSRF_TOKEN, token: fileItem.token, batch_size: 200 }
    });
    if (!res || res.status !== 'success') {
      throw new Error((res && res.message) ? res.message : 'Batch processing failed');
    }
    const p = res.progress || {};
    fileItem.importPercent = res.progress_percent || 0;
    fileItem.inserted = p.inserted || 0;
    fileItem.duplicates = p.duplicates || 0;
    fileItem.errors = p.errors || 0;
    if (res.error_report_available) {
      fileItem.errorReportUrl = res.error_report_url;
    }
    renderList();
    if (res.done) break;
    await new Promise(r => setTimeout(r, 120));
  }
}

async function runAll() {
  state.running = true;
  state.totals = { inserted: 0, duplicates: 0, errors: 0 };
  renderList();
  goStep(3);

  for (const f of state.files) {
    try {
      if (f.status.startsWith('Missing columns') || f.status === 'Preview failed') {
        f.status = 'Skipped';
        renderList();
        continue;
      }
      await uploadFileInChunks(f);
      await startProcessing(f);
      await processBatches(f);
      f.status = (f.errors || f.duplicates) ? 'Completed with warnings' : 'Completed';
    } catch (e) {
      f.status = 'Failed';
      f.errors = Math.max(1, f.errors || 0);
      showError(`${f.file.name}: ${e.message || e}`);
    }
    state.totals.inserted += (f.inserted || 0);
    state.totals.duplicates += (f.duplicates || 0);
    state.totals.errors += (f.errors || 0);
    renderList();
  }

  state.running = false;
  const summary = $('#overall-summary');
  summary.html(
    `All done. Inserted: <b>${state.totals.inserted}</b>, ` +
    `Duplicates: <b>${state.totals.duplicates}</b>, ` +
    `Errors: <b>${state.totals.errors}</b>.`
  ).show();
  $('#btn-done').show();
}

$(function() {
  const dz = $('#dropzone');
  dz.on('dragover', function(e) { e.preventDefault(); e.originalEvent.dataTransfer.dropEffect = 'copy'; dz.css('background', '#f0f8ff'); });
  dz.on('dragleave', function() { dz.css('background', '#fafafa'); });
  dz.on('drop', function(e) {
    e.preventDefault();
    dz.css('background', '#fafafa');
    addFiles(e.originalEvent.dataTransfer.files);
  });

  $('#file-input').on('change', function() {
    addFiles(this.files);
    this.value = '';
  });

  $('#btn-preview').on('click', async function() {
    if (state.running) return;
    await buildPreviews();
    renderPreviewsUI();
    goStep(2);
  });

  $('#btn-back').on('click', function() { goStep(1); });

  $('#btn-start').on('click', async function() {
    if (state.running) return;
    $('#btn-start').prop('disabled', true);
    try {
      await runAll();
    } finally {
      $('#btn-start').prop('disabled', false);
    }
  });

  $('#btn-done').on('click', function() {
    window.location.href = 'bulk-upload-v2.php';
  });

  renderList();
});
</script>
</body>
</html>
