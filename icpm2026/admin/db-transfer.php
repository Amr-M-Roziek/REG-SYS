<?php
session_start();
include 'dbconnection.php';
require_once 'includes/auth_helper.php';

if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

require_permission('db_transfer');

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

    if ($module === 'poster26') {
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

    return null;
}

function get_user_columns_for_transfer($conn) {
    $cols = [];
    if (!$conn) {
        return $cols;
    }
    $res = mysqli_query($conn, "SHOW COLUMNS FROM users");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            if (!empty($row['Field'])) {
                $cols[] = $row['Field'];
            }
        }
    }
    return $cols;
}

function run_transfer_job($source, $target, $startId, $endId, $limit, $dryRun, $refNumber, $operation, &$errors) {
    global $con;

    $adminId = get_current_admin_id();
    $criteria = json_encode([
        'start_id' => $startId,
        'end_id' => $endId,
        'limit' => $limit,
        'dry_run' => $dryRun ? true : false,
        'ref_number' => $refNumber,
        'operation' => $operation
    ]);

    $jobId = null;
    $status = 'running';
    $totalRows = 0;
    $successRows = 0;
    $failedRows = 0;
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

        // Validate ref_number column existence if provided
        if (!empty($refNumber) && !in_array('ref_number', $srcCols)) {
            $errors[] = "Column 'ref_number' does not exist in source database.";
            $status = 'failed';
            $errorMessage = implode(' | ', $errors);
        } else {
            $common = array_values(array_intersect($srcCols, $dstCols));
            $common = array_values(array_diff($common, ['id']));

            if (empty($common)) {
                $errors[] = 'No common user columns found between source and target.';
                $status = 'failed';
                $errorMessage = implode(' | ', $errors);
            } else {
                $sql = "SELECT * FROM users WHERE 1=1";
                $params = [];
                $types = '';
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
                if (!empty($refNumber)) {
                    $sql .= " AND ref_number = ?";
                    $types .= 's';
                    $params[] = $refNumber;
                }
                $sql .= " ORDER BY id ASC";
                if ($limit > 0) {
                    $sql .= " LIMIT " . (int)$limit;
                }

                $stmt = mysqli_prepare($srcCon, $sql);
                if ($stmt) {
                    mysqli_stmt_execute_compat($stmt, $types, $params);
                    $res = mysqli_stmt_get_result($stmt);
                } else {
                    $res = false;
                }

                if (!$res) {
                    $errors[] = 'Failed to read from source database.';
                    $status = 'failed';
                    $errorMessage = implode(' | ', $errors);
                } else {
                    $insertSql = "INSERT INTO users (" . implode(',', $common) . ") VALUES (" . rtrim(str_repeat('?,', count($common)), ',') . ")";
                    $insertStmt = mysqli_prepare($dstCon, $insertSql);
                    
                    // Prepare delete statement for move operation
                    $deleteStmt = null;
                    if ($operation === 'move' && !$dryRun) {
                        $deleteStmt = mysqli_prepare($srcCon, "DELETE FROM users WHERE id = ?");
                    }

                    if (!$insertStmt && !$dryRun) {
                        $errors[] = 'Failed to prepare insert statement on target database.';
                        $status = 'failed';
                        $errorMessage = implode(' | ', $errors);
                    } else {
                        while ($row = mysqli_fetch_assoc($res)) {
                            $totalRows++;
                            if ($dryRun) {
                                $successRows++;
                                continue;
                            }
                            $params = [];
                            foreach ($common as $col) {
                                $params[] = array_key_exists($col, $row) ? $row[$col] : null;
                            }
                            $ok = mysqli_stmt_execute_compat($insertStmt, str_repeat('s', count($common)), $params);
                            if ($ok) {
                                $successRows++;
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
        'errors' => $errors
    ];
}

$modules = [
    'reg' => 'Registration',
    'participant' => 'Participant',
    'poster26' => 'Poster 26',
    'workshop' => 'Workshop'
];

$selectedSource = isset($_POST['source']) ? $_POST['source'] : 'reg';
$selectedTarget = isset($_POST['target']) ? $_POST['target'] : 'participant';
$startId = isset($_POST['start_id']) ? (int)$_POST['start_id'] : 0;
$endId = isset($_POST['end_id']) ? (int)$_POST['end_id'] : 0;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 500;
$dryRun = isset($_POST['dry_run']) ? true : false;
$refNumber = isset($_POST['ref_number']) ? trim($_POST['ref_number']) : '';
$operation = isset($_POST['operation']) && in_array($_POST['operation'], ['copy', 'move']) ? $_POST['operation'] : 'copy';

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
        $transferSummary = run_transfer_job($selectedSource, $selectedTarget, $startId, $endId, $limit, $dryRun, $refNumber, $operation, $errors);
    } else {
        $transferSummary = null;
    }
    if (!empty($errors)) {
        $transferErrorMessage = implode('<br>', array_map('escape_html_transfer', $errors));
    }
}

$jobs = [];
$jobsRes = mysqli_query($con, "SELECT j.*, a.username FROM db_transfer_jobs j LEFT JOIN admin a ON j.admin_id = a.id ORDER BY j.created_at DESC LIMIT 20");
if ($jobsRes) {
    while ($row = mysqli_fetch_assoc($jobsRes)) {
        $jobs[] = $row;
    }
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
                                <?php if ($transferSummary['failed'] > 0): ?>
                                    Failed rows: <?php echo (int)$transferSummary['failed']; ?>.
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
                                    <select name="source" class="form-control">
                                        <?php foreach ($modules as $key => $label): ?>
                                            <option value="<?php echo escape_html_transfer($key); ?>" <?php echo $selectedSource === $key ? 'selected' : ''; ?>>
                                                <?php echo escape_html_transfer($label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
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
                                <label class="col-sm-3 control-label">Ref Number</label>
                                <div class="col-sm-4">
                                    <input type="text" name="ref_number" class="form-control" placeholder="Ref Number (Optional)" value="<?php echo escape_html_transfer($refNumber); ?>">
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
</body>
</html>

