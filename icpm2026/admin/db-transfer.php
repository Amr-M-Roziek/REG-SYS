<?php
session_start();
include 'dbconnection.php';
require_once 'includes/auth_helper.php';

if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

require_permission('db_transfer');

if (isset($_GET['action']) && $_GET['action'] === 'get_columns' && isset($_GET['module'])) {
    $mod = $_GET['module'];
    $conn = get_module_connection_for_transfer($mod);
    $cols = get_user_columns_for_transfer($conn);
    header('Content-Type: application/json');
    echo json_encode($cols);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_categories' && isset($_GET['module'])) {
    $mod = $_GET['module'];
    $conn = get_module_connection_for_transfer($mod);
    $categories = [];
    if ($conn) {
        $res = mysqli_query($conn, "SELECT DISTINCT category FROM users WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $categories[] = $row['category'];
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode($categories);
    exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'get_users' && isset($_GET['module'])) {
    $mod = $_GET['module'];
    $conn = get_module_connection_for_transfer($mod);
    $users = [];
    if ($conn) {
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';
        $catFilter = isset($_GET['category']) ? trim($_GET['category']) : '';
        
        $sql = "SELECT id, fname, lname, email, category FROM users WHERE 1=1";
        $params = [];
        $types = "";
        
        if (!empty($q)) {
            $sql .= " AND (fname LIKE ? OR lname LIKE ? OR email LIKE ? OR id LIKE ? OR category LIKE ?)";
            $pattern = "%$q%";
            $params = array_merge($params, [$pattern, $pattern, $pattern, $pattern, $pattern]);
            $types .= "sssss";
        }
        
        if (!empty($catFilter)) {
            $sql .= " AND category = ?";
            $params[] = $catFilter;
            $types .= "s";
        }
        
        $sql .= " ORDER BY id DESC LIMIT 200";
        
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            if (!empty($params)) {
                mysqli_stmt_bind_param($stmt, $types, ...$params);
            }
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $users[] = [
                    'id' => $row['id'],
                    'fname' => $row['fname'],
                    'lname' => $row['lname'],
                    'email' => $row['email'],
                    'category' => $row['category'],
                    'display' => "ID: " . $row['id'] . " - " . $row['fname'] . " " . $row['lname'] . " (" . $row['email'] . ") [" . $row['category'] . "]"
                ];
            }
        }
    }
    header('Content-Type: application/json');
    echo json_encode($users);
    exit;
}

$currentPage = 'db-transfer';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function escape_html_transfer($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function get_module_connection_for_transfer($module) {
    static $cache = [];
    if (isset($cache[$module])) {
        return $cache[$module];
    }

    $conn = null;

    try {
        if ($module === 'reg') {
            global $con;
            $conn = $con;
            if ($conn) {
                mysqli_set_charset($conn, 'utf8mb4');
            }
            $cache[$module] = $conn;
            return $conn;
        }

        if ($module === 'participant') {
            $whitelist = ['127.0.0.1', '::1', 'localhost'];
            $dockerHost = getenv('DB_HOST');
            if ($dockerHost) {
                $host = $dockerHost;
                $user = 'regsys_part';
                $pass = 'regsys@2025';
            } elseif (in_array($_SERVER['SERVER_NAME'] ?? 'localhost', $whitelist)) {
                $host = '127.0.0.1';
                $user = 'root';
                $pass = '';
            } else {
                $host = 'localhost';
                $user = 'regsys_part';
                $pass = 'regsys@2025';
            }
            $db = 'regsys_participant';
            $conn = @mysqli_connect($host, $user, $pass, $db);
            if ($conn) {
                mysqli_set_charset($conn, 'utf8mb4');
            }
            $cache[$module] = $conn;
            return $conn;
        }

        if ($module === 'poster26' || $module === 'scientific') {
            $host = getenv('DB_HOST') ?: 'localhost';
            $isDocker = getenv('DB_HOST') ? true : false;
            $user = 'regsys_poster';
            $pass = 'regsys@2025';
            $isLocal = false;
            if (!$isDocker) {
                if (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
                    $isLocal = true;
                }
                if (php_sapi_name() === 'cli') {
                    $isLocal = true;
                }
            }
            if ($isLocal) {
                $user = 'root';
                $pass = '';
            }
            $db = 'regsys_poster26';
            $conn = @mysqli_connect($host, $user, $pass, $db);
            if (!$conn) {
                if ($isLocal) {
                    $user = 'regsys_poster';
                    $pass = 'regsys@2025';
                } else {
                    $user = 'root';
                    $pass = '';
                }
                $conn = @mysqli_connect($host, $user, $pass, $db);
            }
            if ($conn) {
                mysqli_set_charset($conn, 'utf8mb4');
            }
            $cache[$module] = $conn;
            return $conn;
        }

        if ($module === 'workshop') {
            $whitelist = ['127.0.0.1', '::1', 'localhost'];
            $dockerHost = getenv('DB_HOST');
            if ($dockerHost) {
                $host = $dockerHost;
                $user = 'regsys_ws';
                $pass = 'regsys@2025';
            } elseif (in_array($_SERVER['SERVER_NAME'] ?? 'localhost', $whitelist)) {
                $host = '127.0.0.1';
                $user = 'root';
                $pass = '';
            } else {
                $host = 'localhost';
                $user = 'regsys_ws';
                $pass = 'regsys@2025';
            }
            $db = 'regsys_workshop';
            $conn = @mysqli_connect($host, $user, $pass, $db);
            if ($conn) {
                mysqli_set_charset($conn, 'utf8mb4');
            }
            $cache[$module] = $conn;
            return $conn;
        }
    } catch (Throwable $e) {
        // Log error if needed, but return null/false so we don't crash
        return null;
    }

    return null;
}

function get_user_columns_for_transfer($conn) {
    $cols = [];
    if (!$conn) {
        return $cols;
    }
    try {
        $res = mysqli_query($conn, "SHOW COLUMNS FROM users");
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                if (!empty($row['Field'])) {
                    $cols[] = $row['Field'];
                }
            }
        }
    } catch (Throwable $e) {
        return [];
    }
    return $cols;
}

function run_transfer_job($source, $target, $startId, $endId, $limit, $dryRun, $filterCol, $filterVal, $operation, $targetCategory, $selectedIds, &$errors) {
    global $con;

    $adminId = get_current_admin_id();
    $criteria = json_encode([
        'start_id' => $startId,
        'end_id' => $endId,
        'limit' => $limit,
        'dry_run' => $dryRun ? true : false,
        'filter_col' => $filterCol,
        'filter_val' => $filterVal,
        'operation' => $operation,
        'target_category' => $targetCategory,
        'specific_ids_count' => count($selectedIds)
    ]);

    $jobId = null;
    $status = 'running';
    $totalRows = 0;
    $successRows = 0;
    $failedRows = 0;
    $skippedRows = 0;
    $newIds = [];
    $skippedEmails = [];
    $errorMessage = '';

    $stmt = mysqli_prepare($con, "INSERT INTO db_transfer_jobs (admin_id, source_db, target_db, criteria, status) VALUES (?, ?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_execute_compat($stmt, 'issss', [(int)$adminId, (string)$source, (string)$target, (string)$criteria, (string)$status]);
        $jobId = mysqli_insert_id($con);
    }

    $srcCon = get_module_connection_for_transfer($source);
    $dstCon = get_module_connection_for_transfer($target);

    if (!$srcCon || !$dstCon) {
        $errors[] = 'Could not connect to one of the selected databases.';
        $status = 'failed';
        $errorMessage = implode(' | ', $errors);
    } else {
        $srcCols = get_user_columns_for_transfer($srcCon);
        $dstCols = get_user_columns_for_transfer($dstCon);

        // Validate filter column existence if provided
        if (!empty($filterVal)) {
            if (empty($filterCol)) {
                $errors[] = "Please select a column to filter by.";
                $status = 'failed';
                $errorMessage = implode(' | ', $errors);
            } elseif (!in_array($filterCol, $srcCols)) {
                $errors[] = "Column '$filterCol' does not exist in source database.";
                $status = 'failed';
                $errorMessage = implode(' | ', $errors);
            }
        }
        
        if ($status !== 'failed') {
            $common = array_values(array_intersect($srcCols, $dstCols));
            $common = array_values(array_diff($common, ['id']));

            // Logic to include 'category' if we are forcing a target category, even if not in source
            $finalCols = $common;
            $categoryOverride = false;
            if (!empty($targetCategory) && in_array('category', $dstCols)) {
                $categoryOverride = true;
                if (!in_array('category', $finalCols)) {
                    $finalCols[] = 'category';
                }
            }
            
            // Logic to ensure 'userip' is populated if present in target
            $useripOverride = false;
            if (in_array('userip', $dstCols)) {
                $useripOverride = true;
                if (!in_array('userip', $finalCols)) {
                    $finalCols[] = 'userip';
                }
            }

            // Logic to ensure 'certificate_sent' is reset if present in target
            if (in_array('certificate_sent', $dstCols)) {
                if (!in_array('certificate_sent', $finalCols)) {
                    $finalCols[] = 'certificate_sent';
                }
            }

            if (empty($common) && empty($finalCols)) {
                 // Only check for empty common if we are NOT overriding category or userip
            }

            if (empty($finalCols)) {
                $errors[] = 'No common user columns found between source and target.';
                $status = 'failed';
                $errorMessage = implode(' | ', $errors);
            } else {
                $sql = "SELECT * FROM users WHERE 1=1";
                $params = [];
                $types = '';
                
                // PRIORITIZE SPECIFIC IDS
                if (!empty($selectedIds)) {
                    // Create placeholders for IN clause
                    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                    $sql .= " AND id IN ($placeholders)";
                    $types .= str_repeat('i', count($selectedIds));
                    foreach ($selectedIds as $id) {
                        $params[] = (int)$id;
                    }
                } elseif (!empty($filterVal) && !empty($filterCol)) {
                    $sql .= " AND `$filterCol` = ?";
                    $types .= 's';
                    $params[] = $filterVal;
                } else {
                    if ($startId > 0) {
                        $sql .= " AND id >= ?";
                        $types .= 'i';
                        $params[] = $startId;
                    }
                    if ($endId > 0) {
                        $sql .= " AND id <= ?";
                        $types .= 'i';
                        $params[] = $endId;
                    }
                }
                
                $sql .= " ORDER BY id ASC";
                if ($limit > 0) {
                    $sql .= " LIMIT " . (int)$limit;
                }

                $stmt = mysqli_prepare($srcCon, $sql);
                if ($stmt) {
                    if (!empty($params)) {
                        mysqli_stmt_execute_compat($stmt, $types, $params);
                    } else {
                        mysqli_stmt_execute($stmt);
                    }
                    $res = mysqli_stmt_get_result($stmt);
                } else {
                    $res = false;
                }

                if (!$res) {
                    $errors[] = 'Failed to read from source database.';
                    $status = 'failed';
                    $errorMessage = implode(' | ', $errors);
                } else {
                    $insertSql = "INSERT INTO users (" . implode(',', $finalCols) . ") VALUES (" . rtrim(str_repeat('?,', count($finalCols)), ',') . ")";
                    $insertStmt = mysqli_prepare($dstCon, $insertSql);
                    
                    // Prepare delete statement for move operation
                    $deleteStmt = null;
                    if ($operation === 'move' && !$dryRun) {
                        $deleteStmt = mysqli_prepare($srcCon, "DELETE FROM users WHERE id = ?");
                    }

                    // Prepare duplicate check statement if email column exists in target
                    $checkStmt = null;
                    if (in_array('email', $dstCols)) {
                        $checkStmt = mysqli_prepare($dstCon, "SELECT id FROM users WHERE email = ? LIMIT 1");
                    }

                    if (!$insertStmt && !$dryRun) {
                        $errors[] = 'Failed to prepare insert statement on target database.';
                        $status = 'failed';
                        $errorMessage = implode(' | ', $errors);
                    } else {
                        while ($row = mysqli_fetch_assoc($res)) {
                            $totalRows++;
                            
                            // Check for duplicate
                            if ($checkStmt && !empty($row['email'])) {
                                mysqli_stmt_bind_param($checkStmt, 's', $row['email']);
                                mysqli_stmt_execute($checkStmt);
                                mysqli_stmt_store_result($checkStmt);
                                if (mysqli_stmt_num_rows($checkStmt) > 0) {
                                    $skippedRows++;
                                    $skippedEmails[] = $row['email'];
                                    continue;
                                }
                            }

                            if ($dryRun) {
                                $successRows++;
                                continue;
                            }
                            $params = [];
                            foreach ($finalCols as $col) {
                                if ($col === 'category' && $categoryOverride) {
                                    $params[] = $targetCategory;
                                } elseif ($col === 'userip') {
                                    // Handle userip logic: if exists in source and not empty, use it. Otherwise default.
                                    $val = (array_key_exists($col, $row) && !empty($row[$col])) ? $row[$col] : 'internal system user transfer-copy';
                                    $params[] = $val;
                                } elseif ($col === 'certificate_sent' || substr($col, -5) === '_sent') {
                                    // Force certificate_sent and any other *_sent columns to '0' (not sent)
                                    // This ensures that transferred users are not marked as having received communications in the new system
                                    $params[] = '0';
                                } else {
                                    $params[] = array_key_exists($col, $row) ? $row[$col] : null;
                                }
                            }
                            $ok = mysqli_stmt_execute_compat($insertStmt, str_repeat('s', count($finalCols)), $params);
                            if ($ok) {
                                $successRows++;
                                $newIds[] = mysqli_insert_id($dstCon);
                                // Handle Move Operation
                                if ($operation === 'move' && $deleteStmt) {
                                    $delOk = mysqli_stmt_execute_compat($deleteStmt, 'i', [(int)$row['id']]);
                                    if (!$delOk) {
                                        $errors[] = "Failed to delete moved row ID {$row['id']} from source.";
                                    }
                                }
                            } else {
                                $failedRows++;
                                $err = mysqli_error($dstCon);
                                if ($err && count($errors) < 10) {
                                    $errors[] = $err;
                                }
                            }
                        }
                        if ($status !== 'failed') {
                            $status = $failedRows > 0 ? 'completed_with_errors' : 'completed';
                            if (!empty($errors)) {
                                $errorMessage = implode(' | ', $errors);
                            }
                        }
                    }
                }
            }
        }
    }

    if ($jobId) {
        $updateStmt = mysqli_prepare($con, "UPDATE db_transfer_jobs SET total_rows = ?, success_rows = ?, failed_rows = ?, status = ?, error_message = ?, completed_at = NOW() WHERE id = ?");
        if ($updateStmt) {
            mysqli_stmt_execute_compat($updateStmt, 'iiissi', [$totalRows, $successRows, $failedRows, $status, $errorMessage, $jobId]);
        }
    }

    if ($jobId && $status === 'completed') {
        log_audit('db_transfer', 'Job ' . $jobId . ' from ' . $source . ' to ' . $target . ' transferred ' . $successRows . ' rows');
    } elseif ($jobId && $status !== 'completed') {
        log_audit('db_transfer_error', 'Job ' . $jobId . ' from ' . $source . ' to ' . $target . ' ended with status ' . $status);
    }

    return [
        'job_id' => $jobId,
        'status' => $status,
        'total' => $totalRows,
        'success' => $successRows,
        'failed' => $failedRows,
        'skipped' => $skippedRows,
        'new_ids' => $newIds,
        'skipped_emails' => $skippedEmails,
        'errors' => $errors
    ];
}

$modules = [
    'reg' => 'Registration',
    'participant' => 'Participant',
    'poster26' => 'Poster 26',
    'scientific' => 'Scientific',
    'workshop' => 'Workshop'
];

$selectedSource = isset($_POST['source']) ? $_POST['source'] : 'reg';
$selectedTarget = isset($_POST['target']) ? $_POST['target'] : 'participant';
$startId = isset($_POST['start_id']) ? (int)$_POST['start_id'] : 0;
$endId = isset($_POST['end_id']) ? (int)$_POST['end_id'] : 0;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 500;
$dryRun = isset($_POST['dry_run']) ? true : false;
$filterCol = isset($_POST['filter_col']) ? trim($_POST['filter_col']) : '';
$filterVal = isset($_POST['filter_val']) ? trim($_POST['filter_val']) : '';
// Backward compatibility / Fallback
if (empty($filterVal) && isset($_POST['ref_number'])) {
    $filterVal = trim($_POST['ref_number']);
}

$targetCategory = isset($_POST['target_category']) ? trim($_POST['target_category']) : '';
$operation = isset($_POST['operation']) && in_array($_POST['operation'], ['copy', 'move']) ? $_POST['operation'] : 'copy';

$selectedIds = isset($_POST['selected_users']) ? $_POST['selected_users'] : [];

$transferSummary = null;
$transferErrorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_action']) && $_POST['transfer_action'] === 'start') {
    $errors = [];
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Security token mismatch. Please reload the page and try again.';
    }
    if (!isset($modules[$selectedSource]) || !isset($modules[$selectedTarget])) {
        $errors[] = 'Invalid source or target database.';
    }
    if ($selectedSource === $selectedTarget) {
        $errors[] = 'Source and target databases must be different.';
    }
    if ($limit <= 0) {
        $limit = 500;
    }
    if (empty($errors)) {
        try {
            $transferSummary = run_transfer_job($selectedSource, $selectedTarget, $startId, $endId, $limit, $dryRun, $filterCol, $filterVal, $operation, $targetCategory, $selectedIds, $errors);
        } catch (Throwable $e) {
            $errors[] = 'System Error: ' . $e->getMessage();
            $transferSummary = null;
        }
    } else {
        $transferSummary = null;
    }
    if (!empty($errors)) {
        $transferErrorMessage = implode('<br>', array_map('escape_html_transfer', $errors));
    }
}

$jobs = [];
try {
    $jobsRes = mysqli_query($con, "SELECT j.*, a.username FROM db_transfer_jobs j LEFT JOIN admin a ON j.admin_id = a.id ORDER BY j.created_at DESC LIMIT 20");
    if ($jobsRes) {
        while ($row = mysqli_fetch_assoc($jobsRes)) {
            $jobs[] = $row;
        }
    }
} catch (Throwable $e) {
    // Ignore error for jobs listing to prevent page crash
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Transfer</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
    <style>
        /* Table Selection Panel */
        .table-selection-panel {
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f9f9f9;
        }
        .table-selection-header {
            padding: 10px;
            background: #eee;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-list {
            max-height: 250px;
            overflow-y: auto;
            padding: 10px;
        }
        .table-item {
            margin-bottom: 5px;
        }
        .table-item label {
            font-weight: normal;
            cursor: pointer;
            margin-bottom: 0;
            display: block;
        }
        .table-select-checkbox {
            margin-right: 8px !important;
            vertical-align: top;
            margin-top: 2px;
        }
        .selection-count {
            font-size: 0.9em;
            color: #666;
        }
        .loading-tables, .empty-tables {
            padding: 20px;
            text-align: center;
            color: #777;
            font-style: italic;
        }
    </style>
</head>
<body>
<section id="container">
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <section id="main-content">
        <section class="wrapper">
            <h3><i class="fa fa-exchange"></i> Database Transfer</h3>

            <div class="row mt">
                <div class="col-lg-8">
                    <div class="content-panel" style="padding:20px;">
                        <h4><i class="fa fa-shield"></i> Transfer Data Between Databases</h4>
                        <p>Select a source and target database and define the range of records to transfer. Only matching columns in the users table are copied. Primary keys are regenerated on the target.</p>

                        <?php if ($transferSummary): ?>
                            <div class="alert alert-<?php echo $transferSummary['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                <strong>Job <?php echo (int)$transferSummary['job_id']; ?> status:</strong> <?php echo escape_html_transfer($transferSummary['status']); ?>.
                                Transferred <?php echo (int)$transferSummary['success']; ?> of <?php echo (int)$transferSummary['total']; ?> rows.
                                <?php if (isset($transferSummary['skipped']) && $transferSummary['skipped'] > 0): ?>
                                    Skipped <?php echo (int)$transferSummary['skipped']; ?> rows (already exist).
                                    <?php if (!empty($transferSummary['skipped_emails'])): ?>
                                        <br><strong>Skipped Emails:</strong> <?php echo implode(', ', array_map('escape_html_transfer', $transferSummary['skipped_emails'])); ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($transferSummary['failed'] > 0): ?>
                                    Failed rows: <?php echo (int)$transferSummary['failed']; ?>.
                                <?php endif; ?>
                                <?php if (!empty($transferSummary['new_ids'])): ?>
                                    <br><strong>New User IDs:</strong> <?php echo implode(', ', $transferSummary['new_ids']); ?>
                                <?php endif; ?>
                                <?php if (!empty($transferErrorMessage)): ?>
                                    <br><?php echo $transferErrorMessage; ?>
                                <?php endif; ?>
                            </div>
                        <?php elseif (!empty($transferErrorMessage)): ?>
                            <div class="alert alert-danger">
                                <?php echo $transferErrorMessage; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" class="form-horizontal">
                            <input type="hidden" name="transfer_action" value="start">
                            <input type="hidden" name="csrf_token" value="<?php echo escape_html_transfer($_SESSION['csrf_token']); ?>">

                            <div class="form-group">
                                <label class="col-sm-3 control-label">Source Database</label>
                                <div class="col-sm-9">
                                    <select name="source" class="form-control" id="source-db-select">
                                        <?php foreach ($modules as $key => $label): ?>
                                            <option value="<?php echo escape_html_transfer($key); ?>" <?php echo $selectedSource === $key ? 'selected' : ''; ?>>
                                                <?php echo escape_html_transfer($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label">Select Users</label>
                                <div class="col-sm-9">
                                    <div class="table-selection-panel">
                                        <div class="table-selection-header" style="flex-wrap: wrap; gap: 10px;">
                                            <div style="display:flex; align-items:center; flex-grow:1; gap: 10px;">
                                                <select id="user-category-filter" class="form-control input-sm" style="width: 150px;">
                                                    <option value="">All Categories</option>
                                                </select>
                                                <input type="text" id="user-search-input" class="form-control input-sm" placeholder="Search by name, email or ID..." style="width: 100%;">
                                            </div>
                                            <div style="display:flex; align-items:center; gap: 10px;">
                                                <label style="font-weight:bold; margin-bottom:0; cursor:pointer;">
                                                    <input type="checkbox" id="select-all-users" class="table-select-checkbox"> Select All Loaded
                                                </label>
                                                <span class="selection-count" id="user-selection-count">0 selected</span>
                                            </div>
                                        </div>
                                        <div class="table-list" id="source-user-list">
                                            <div class="loading-tables"><i class="fa fa-spinner fa-spin"></i> Loading users...</div>
                                        </div>
                                    </div>
                                    <p class="help-block">Select specific users to transfer. This overrides ID Range and Filters. Lists last 200 users by default.</p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label">Target Database</label>
                                <div class="col-sm-9">
                                    <select name="target" class="form-control">
                                        <?php foreach ($modules as $key => $label): ?>
                                            <option value="<?php echo escape_html_transfer($key); ?>" <?php echo $selectedTarget === $key ? 'selected' : ''; ?>>
                                                <?php echo escape_html_transfer($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label">ID Range</label>
                                <div class="col-sm-4">
                                    <input type="number" name="start_id" class="form-control" placeholder="From ID" value="<?php echo (int)$startId; ?>">
                                </div>
                                <div class="col-sm-4">
                                    <input type="number" name="end_id" class="form-control" placeholder="To ID" value="<?php echo (int)$endId; ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label">Filter Column</label>
                                <div class="col-sm-4">
                                    <select name="filter_col" id="filter_col" class="form-control">
                                        <option value="">-- Select Column --</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label">Filter Value</label>
                                <div class="col-sm-4">
                                    <input type="text" name="filter_val" class="form-control" placeholder="Value (Optional)" value="<?php echo escape_html_transfer($filterVal); ?>">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label">Target Category</label>
                                <div class="col-sm-4">
                                    <input type="text" name="target_category" class="form-control" placeholder="Optional: Set specific category" value="<?php echo escape_html_transfer($targetCategory); ?>">
                                    <span class="help-block">If set, this value will be assigned to all transferred users. If empty, original category will be copied if available.</span>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label">Operation</label>
                                <div class="col-sm-9">
                                    <label class="radio-inline">
                                        <input type="radio" name="operation" value="copy" <?php echo $operation === 'copy' ? 'checked' : ''; ?>> Copy
                                    </label>
                                    <label class="radio-inline">
                                        <input type="radio" name="operation" value="move" <?php echo $operation === 'move' ? 'checked' : ''; ?>> Move (Delete from source)
                                    </label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="col-sm-3 control-label">Max Rows</label>
                                <div class="col-sm-4">
                                    <input type="number" name="limit" class="form-control" value="<?php echo (int)$limit; ?>" min="1" max="10000">
                                </div>
                                <div class="col-sm-5">
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="dry_run" value="1" <?php echo $dryRun ? 'checked' : ''; ?>>
                                            Dry run only (no data written)
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-sm-offset-3 col-sm-9">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-play"></i> Start Transfer
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="content-panel" style="padding:20px;">
                        <h4><i class="fa fa-list"></i> Recent Transfer Jobs</h4>
                        <div class="table-responsive">
                            <table class="table table-condensed table-striped">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Admin</th>
                                    <th>Source</th>
                                    <th>Target</th>
                                    <th>Status</th>
                                    <th>Rows</th>
                                    <th>Created</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (empty($jobs)): ?>
                                    <tr>
                                        <td colspan="7">No transfer jobs recorded.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td><?php echo (int)$job['id']; ?></td>
                                            <td><?php echo escape_html_transfer($job['username']); ?></td>
                                            <td><?php echo escape_html_transfer($job['source_db']); ?></td>
                                            <td><?php echo escape_html_transfer($job['target_db']); ?></td>
                                            <td><?php echo escape_html_transfer($job['status']); ?></td>
                                            <td><?php echo (int)$job['success_rows']; ?>/<?php echo (int)$job['total_rows']; ?></td>
                                            <td><?php echo escape_html_transfer($job['created_at']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
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
<script src="assets/js/jquery.nicescroll.js" type="text/javascript"></script>
<script src="assets/js/common-scripts.js"></script>
<script>
$(document).ready(function() {
    var selectedFilterCol = "<?php echo escape_html_transfer($filterCol); ?>";
    var selectedUsersMap = {}; // Store selected users: {id: {id, display}}

    function loadColumns(module, preselect) {
        if (!module) return;
        $.getJSON('?action=get_columns&module=' + module, function(data) {
             var $el = $('select[name="filter_col"]');
             if ($el.length === 0) $el = $('#filter_col');

            $el.empty(); 
            $el.append($("<option></option>").attr("value", "").text("-- Select Column --"));
            $.each(data, function(key, value) {
                var option = $("<option></option>").attr("value", value).text(value);
                if (value === preselect) {
                    option.attr("selected", "selected");
                }
                $el.append(option);
            });
        });
    }

    function loadCategories(module) {
        if (!module) return;
        $.getJSON('?action=get_categories&module=' + module, function(data) {
             var $el = $('#user-category-filter');
             $el.empty();
             $el.append($("<option></option>").attr("value", "").text("All Categories"));
             $.each(data, function(i, val) {
                 $el.append($("<option></option>").attr("value", val).text(val));
             });
        });
    }

    function loadUsers(module, query, category) {
        var $list = $('#source-user-list');
        var $count = $('#user-selection-count');
        
        if (!query && !category && Object.keys(selectedUsersMap).length === 0) {
             $list.html('<div class="loading-tables"><i class="fa fa-spinner fa-spin"></i> Loading users...</div>');
        }
        
        if (!module) {
            $list.html('<div class="empty-tables">Please select a source database.</div>');
            return;
        }

        var url = '?action=get_users&module=' + module;
        if (query) {
            url += '&q=' + encodeURIComponent(query);
        }
        if (category) {
            url += '&category=' + encodeURIComponent(category);
        }

        $.getJSON(url, function(data) {
            $list.empty();
            var renderedIds = {};

            function renderUserItem(user, isSelected) {
                if (renderedIds[user.id]) return;
                renderedIds[user.id] = true;

                var item = $('<div class="table-item"></div>');
                if (isSelected) item.addClass('bg-info').css('background-color', '#e8f0fe'); // Highlight selected

                var label = $('<label></label>').text(' ' + user.display);
                var checkbox = $('<input type="checkbox" name="selected_users[]" class="user-checkbox table-select-checkbox">')
                    .val(user.id)
                    .data('display', user.display);
                
                if (isSelected) {
                    checkbox.prop('checked', true);
                }

                label.prepend(checkbox);
                item.append(label);
                $list.append(item);
            }

            // 1. Render currently selected users first (Persistence)
            $.each(selectedUsersMap, function(id, user) {
                renderUserItem(user, true);
            });

            // 2. Render search results
            if (data && data.length > 0) {
                $.each(data, function(i, user) {
                    // Check if already in map (already rendered)
                    if (!selectedUsersMap[user.id]) {
                        renderUserItem(user, false);
                    }
                });
            } 
            
            if (Object.keys(renderedIds).length === 0) {
                $list.html('<div class="empty-tables">No users found matching query.</div>');
            }
            
            updateUserCount();
        }).fail(function() {
            $list.html('<div class="empty-tables" style="color:red;">Failed to load users.</div>');
        });
    }

    function updateUserCount() {
        var count = Object.keys(selectedUsersMap).length;
        $('#user-selection-count').text(count + ' selected');
        
        var visibleCheckboxes = $('.user-checkbox');
        var visibleChecked = $('.user-checkbox:checked');
        
        if (visibleCheckboxes.length > 0 && visibleCheckboxes.length === visibleChecked.length) {
            $('#select-all-users').prop('checked', true).prop('indeterminate', false);
        } else if (visibleChecked.length > 0) {
            $('#select-all-users').prop('checked', false).prop('indeterminate', true);
        } else {
            $('#select-all-users').prop('checked', false).prop('indeterminate', false);
        }
    }

    var searchTimeout;
    $('#user-search-input').on('input', function() {
        var query = $(this).val();
        var category = $('#user-category-filter').val();
        var mod = $('#source-db-select').val();
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            loadUsers(mod, query, category);
        }, 500);
    });
    
    $('#user-category-filter').change(function() {
        var query = $('#user-search-input').val();
        var category = $(this).val();
        var mod = $('#source-db-select').val();
        loadUsers(mod, query, category);
    });

    $('#source-db-select').change(function() {
        var mod = $(this).val();
        selectedUsersMap = {}; // Clear selections on DB change
        updateUserCount();
        loadColumns(mod, '');
        loadCategories(mod);
        $('#user-search-input').val('');
        $('#user-category-filter').val('');
        loadUsers(mod, '', '');
    });

    // Select All
    $('#select-all-users').change(function() {
        var isChecked = $(this).is(':checked');
        $('.user-checkbox').each(function() {
            var $cb = $(this);
            if ($cb.is(':checked') !== isChecked) {
                $cb.prop('checked', isChecked).trigger('change');
            }
        });
    });

    // Individual Checkbox
    $(document).on('change', '.user-checkbox', function() {
        var id = $(this).val();
        var display = $(this).data('display');
        // Fallback
        if (!display) display = $(this).parent().text().trim();

        if ($(this).is(':checked')) {
            selectedUsersMap[id] = {id: id, display: display};
            $(this).closest('.table-item').addClass('bg-info').css('background-color', '#e8f0fe');
        } else {
            delete selectedUsersMap[id];
            $(this).closest('.table-item').removeClass('bg-info').css('background-color', '');
        }
        updateUserCount();
    });

    var currentSource = $('#source-db-select').val();
    if (currentSource) {
        loadColumns(currentSource, selectedFilterCol);
        loadCategories(currentSource);
        loadUsers(currentSource, '', '');
    }
});
</script>
</body>
</html>
