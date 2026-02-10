<?php
session_start();
include 'dbconnection.php';
require_once 'includes/auth_helper.php';

if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:logout.php');
    exit();
}

$currentPage = 'attendance-dashboard';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function escape_html_att($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function get_module_connection_for_attendance($module) {
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

function get_qr_secret() {
    $secret = getenv('ATTENDANCE_QR_SECRET');
    if (!$secret && isset($_SERVER['ATTENDANCE_QR_SECRET'])) {
        $secret = $_SERVER['ATTENDANCE_QR_SECRET'];
    }
    return $secret;
}

function decrypt_attendance_token($token) {
    $secret = get_qr_secret();
    if (!$secret || !$token) {
        return null;
    }
    $token = str_replace([' ', "\n", "\r", "\t"], '', $token);
    $raw = base64_decode(strtr($token, '-_', '+/'), true);
    if ($raw === false || strlen($raw) < 17) {
        return null;
    }
    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $key = hash('sha256', $secret, true);
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if ($plain === false) {
        return null;
    }
    $data = json_decode($plain, true);
    if (!is_array($data)) {
        return null;
    }
    return $data;
}

function build_attendance_token($module, $userId, $sessionId) {
    $secret = get_qr_secret();
    if (!$secret) {
        return null;
    }
    $payload = [
        'module' => $module,
        'user_id' => (int)$userId,
        'session_id' => (int)$sessionId,
        'ts' => time()
    ];
    $plain = json_encode($payload);
    $key = hash('sha256', $secret, true);
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    if ($cipher === false) {
        return null;
    }
    return rtrim(strtr(base64_encode($iv . $cipher), '+/', '-_'), '=');
}

function find_user_for_attendance($module, $ref) {
    $conn = get_module_connection_for_attendance($module);
    if (!$conn) {
        return null;
    }
    $ref = trim($ref);
    if ($ref === '') {
        return null;
    }
    $isId = ctype_digit($ref);
    if ($isId) {
        $sql = "SELECT * FROM users WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return null;
        }
        mysqli_stmt_execute_compat($stmt, 'i', [(int)$ref]);
    } else {
        $sql = "SELECT * FROM users WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return null;
        }
        mysqli_stmt_execute_compat($stmt, 's', [$ref]);
    }
    $res = mysqli_stmt_get_result($stmt);
    if (!$res) {
        return null;
    }
    $row = mysqli_fetch_assoc($res);
    if (!$row) {
        return null;
    }
    $fullName = trim(($row['fname'] ?? '') . ' ' . ($row['lname'] ?? ''));
    $group = isset($row['category']) ? $row['category'] : '';
    return [
        'id' => (int)$row['id'],
        'name' => $fullName !== '' ? $fullName : ($row['fname'] ?? ''),
        'email' => $row['email'] ?? '',
        'group' => $group,
        'raw' => $row
    ];
}

function record_attendance_event($sessionId, $module, $userRef, $userName, $userEmail, $userGroup, $eventType, $status, $source) {
    global $con;
    $adminId = get_current_admin_id();
    $stmt = mysqli_prepare($con, "INSERT INTO attendance_events (session_id, module, user_ref, user_name, user_email, user_group, event_type, status, source, admin_id, event_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        return false;
    }
    return mysqli_stmt_execute_compat($stmt, 'issssssssi', [
        (int)$sessionId,
        (string)$module,
        (string)$userRef,
        (string)$userName,
        (string)$userEmail,
        (string)$userGroup,
        (string)$eventType,
        (string)$status,
        (string)$source,
        (int)$adminId
    ]);
}

function get_last_event_type($sessionId, $module, $userRef) {
    global $con;
    $stmt = mysqli_prepare($con, "SELECT event_type FROM attendance_events WHERE session_id = ? AND module = ? AND user_ref = ? ORDER BY event_time DESC LIMIT 1");
    if (!$stmt) {
        return null;
    }
    mysqli_stmt_execute_compat($stmt, 'iss', [(int)$sessionId, (string)$module, (string)$userRef]);
    $res = mysqli_stmt_get_result($stmt);
    if (!$res) {
        return null;
    }
    $row = mysqli_fetch_assoc($res);
    if (!$row) {
        return null;
    }
    return $row['event_type'];
}

if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $csrf = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid security token']);
        exit;
    }

    if ($action === 'add_session') {
        if (!check_permission('attendance_manage')) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            exit;
        }
        $name = trim($_POST['name'] ?? '');
        $module = trim($_POST['module'] ?? '');
        $start = $_POST['start_time'] ?? '';
        $end = $_POST['end_time'] ?? '';
        $location = trim($_POST['location'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($name === '') {
            echo json_encode(['status' => 'error', 'message' => 'Session name is required']);
            exit;
        }
        $stmt = mysqli_prepare($con, "INSERT INTO attendance_sessions (name, module, description, start_time, end_time, location, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
            exit;
        }
        mysqli_stmt_execute_compat($stmt, 'ssssssi', [
            $name,
            $module,
            $description,
            $start !== '' ? $start : null,
            $end !== '' ? $end : null,
            $location,
            get_current_admin_id()
        ]);
        $id = mysqli_insert_id($con);
        log_audit('attendance_session_create', 'Session ' . $id . ' ' . $name);
        echo json_encode(['status' => 'success', 'session_id' => $id]);
        exit;
    }

    if ($action === 'record_attendance') {
        if (!check_permission('attendance_scan') && !check_permission('attendance_manage')) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            exit;
        }
        $sessionId = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
        $token = $_POST['qr_data'] ?? '';
        if ($sessionId <= 0 || $token === '') {
            echo json_encode(['status' => 'error', 'message' => 'Missing session or QR data']);
            exit;
        }
        $sessionRes = mysqli_query($con, "SELECT * FROM attendance_sessions WHERE id = " . $sessionId . " LIMIT 1");
        $sessionRow = $sessionRes ? mysqli_fetch_assoc($sessionRes) : null;
        if (!$sessionRow) {
            echo json_encode(['status' => 'error', 'message' => 'Session not found']);
            exit;
        }
        $data = decrypt_attendance_token($token);
        
        // Support for simple "source:id" QR codes (e.g., "reg:123", "participant:456")
        if (!$data && strpos($token, ':') !== false) {
            $parts = explode(':', $token);
            if (count($parts) === 2) {
                $rawModule = trim($parts[0]);
                $rawId = trim($parts[1]);
                
                // Map short names to module keys
                $moduleMap = [
                    'reg' => 'reg',
                    'participant' => 'participant',
                    'poster' => 'poster26',
                    'poster26' => 'poster26',
                    'workshop' => 'workshop',
                    'scientific' => 'poster26',
                    'exhibitor' => 'reg'
                ];
                
                if (array_key_exists($rawModule, $moduleMap) && is_numeric($rawId)) {
                    $data = [
                        'module' => $moduleMap[$rawModule],
                        'user_id' => (int)$rawId
                    ];
                }
            }
        }
        
        // Support for raw numeric IDs (Auto-detect module with Session priority)
        if (!$data && ctype_digit($token)) {
             $sessionModule = $sessionRow['module'] ?? '';
             
             // 1. Try Session Module (Prioritize the module this session belongs to)
             if ($sessionModule && find_user_for_attendance($sessionModule, $token)) {
                 $data = [
                    'module' => $sessionModule,
                    'user_id' => (int)$token
                 ];
             }
             // 2. Try Participant (Most common category)
             elseif ($sessionModule !== 'participant' && find_user_for_attendance('participant', $token)) {
                 $data = [
                    'module' => 'participant',
                    'user_id' => (int)$token
                 ];
             } 
             // 3. Try Main Registration (Exhibitors, Organizers, etc.)
             elseif ($sessionModule !== 'reg' && find_user_for_attendance('reg', $token)) {
                 $data = [
                    'module' => 'reg',
                    'user_id' => (int)$token
                 ];
             }
             // 4. Default to session module or participant if neither found (for error reporting)
             else {
                 $data = [
                    'module' => $sessionModule ?: 'participant',
                    'user_id' => (int)$token
                 ];
             }
        }

        if (!$data || !isset($data['module'], $data['user_id'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid or expired QR code']);
            exit;
        }
        $module = $data['module'];
        $userId = (int)$data['user_id'];
        if (isset($data['session_id']) && (int)$data['session_id'] !== $sessionId) {
            echo json_encode(['status' => 'error', 'message' => 'QR code does not match selected session']);
            exit;
        }
        $user = find_user_for_attendance($module, (string)$userId);
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'User not found in module database']);
            exit;
        }
        
        $scanMode = $_POST['scan_mode'] ?? 'auto';
        $lastType = get_last_event_type($sessionId, $module, (string)$user['id']);
        
        if ($scanMode === 'in') {
            if ($lastType === 'in') {
                log_audit('attendance_duplicate_scan', 'Session ' . $sessionId . ' ' . $module . ' user ' . $user['id'] . ' duplicate in blocked');
                echo json_encode(['status' => 'error', 'message' => 'This QR code has already been used for sign-in during this session']);
                exit;
            }
            $eventType = 'in';
        } elseif ($scanMode === 'out') {
            if ($lastType !== 'in') {
                // If user is not signed in, they can't sign out (or we can just log it/error)
                // Assuming strict workflow:
                if ($lastType === 'out') {
                     echo json_encode(['status' => 'error', 'message' => 'User is already signed out']);
                     exit;
                }
                // If lastType is null, they never signed in.
                echo json_encode(['status' => 'error', 'message' => 'User must sign in first']);
                exit;
            }
            $eventType = 'out';
        } else {
            // Legacy Auto-Toggle (Fallback)
            $eventType = $lastType === 'in' ? 'out' : 'in';
        }

        $status = $eventType === 'in' ? 'present' : 'signed_out';
        $ok = record_attendance_event($sessionId, $module, (string)$user['id'], $user['name'], $user['email'], $user['group'], $eventType, $status, 'qr');
        if (!$ok) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to record attendance']);
            exit;
        }
        log_audit('attendance_scan', 'Session ' . $sessionId . ' ' . $module . ' user ' . $user['id'] . ' ' . $eventType);
        echo json_encode([
            'status' => 'success',
            'event_type' => $eventType,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'group' => $user['group']
            ]
        ]);
        exit;
    }

    if ($action === 'manual_attendance') {
        if (!check_permission('attendance_manage')) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            exit;
        }
        $sessionId = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
        $module = $_POST['module'] ?? '';
        $ref = $_POST['user_ref'] ?? '';
        $eventType = $_POST['event_type'] ?? 'in';
        if ($sessionId <= 0 || $module === '' || $ref === '') {
            echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
            exit;
        }
        $sessionRes = mysqli_query($con, "SELECT * FROM attendance_sessions WHERE id = " . $sessionId . " LIMIT 1");
        $sessionRow = $sessionRes ? mysqli_fetch_assoc($sessionRes) : null;
        if (!$sessionRow) {
            echo json_encode(['status' => 'error', 'message' => 'Session not found']);
            exit;
        }
        $user = find_user_for_attendance($module, $ref);
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'User not found']);
            exit;
        }
        $status = $eventType === 'in' ? 'present' : 'signed_out';
        $ok = record_attendance_event($sessionId, $module, (string)$user['id'], $user['name'], $user['email'], $user['group'], $eventType, $status, 'manual');
        if (!$ok) {
            echo json_encode(['status' => 'error', 'message' => 'Failed to record attendance']);
            exit;
        }
        log_audit('attendance_manual', 'Session ' . $sessionId . ' ' . $module . ' user ' . $user['id'] . ' ' . $eventType);
        echo json_encode([
            'status' => 'success',
            'event_type' => $eventType,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'group' => $user['group']
            ]
        ]);
        exit;
    }

    if ($action === 'get_report') {
        if (!check_permission('attendance_reports') && !check_permission('attendance_manage')) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            exit;
        }
        $sessionId = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
        $module = $_POST['module'] ?? '';
        $from = $_POST['from'] ?? '';
        $to = $_POST['to'] ?? '';
        $group = $_POST['user_group'] ?? '';

        $sql = "SELECT e.*, s.name AS session_name FROM attendance_events e LEFT JOIN attendance_sessions s ON e.session_id = s.id WHERE 1=1";
        $params = [];
        $types = '';
        if ($sessionId > 0) {
            $sql .= " AND e.session_id = ?";
            $types .= 'i';
            $params[] = $sessionId;
        }
        if ($module !== '') {
            $sql .= " AND e.module = ?";
            $types .= 's';
            $params[] = $module;
        }
        if ($group !== '') {
            $sql .= " AND e.user_group LIKE ?";
            $types .= 's';
            $params[] = '%' . $group . '%';
        }
        if ($from !== '') {
            $sql .= " AND DATE(e.event_time) >= ?";
            $types .= 's';
            $params[] = $from;
        }
        if ($to !== '') {
            $sql .= " AND DATE(e.event_time) <= ?";
            $types .= 's';
            $params[] = $to;
        }
        $sql .= " ORDER BY e.event_time DESC LIMIT 1000";

        $stmt = mysqli_prepare($con, $sql);
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
            echo json_encode(['status' => 'error', 'message' => 'Failed to load report']);
            exit;
        }
        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = [
                'time' => $row['event_time'],
                'session' => $row['session_name'],
                'module' => $row['module'],
                'user' => $row['user_name'],
                'email' => $row['user_email'],
                'group' => $row['user_group'],
                'type' => $row['event_type'],
                'status' => $row['status'],
                'source' => $row['source']
            ];
        }
        echo json_encode(['status' => 'success', 'data' => $rows]);
        exit;
    }

    if ($action === 'get_recent_scans') {
        if (!check_permission('attendance_scan') && !check_permission('attendance_manage')) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied']);
            exit;
        }
        $sessionId = isset($_POST['session_id']) ? (int)$_POST['session_id'] : 0;
        if ($sessionId <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Session ID required']);
            exit;
        }
        
        // Fetch last 50 scans for this session
        $sql = "SELECT e.* FROM attendance_events e WHERE e.session_id = ? ORDER BY e.event_time DESC LIMIT 50";
        $stmt = mysqli_prepare($con, $sql);
        if ($stmt) {
            mysqli_stmt_execute_compat($stmt, 'i', [$sessionId]);
            $res = mysqli_stmt_get_result($stmt);
            $rows = [];
            while ($row = mysqli_fetch_assoc($res)) {
                $rows[] = [
                    'id' => $row['id'],
                    'time' => date('H:i:s', strtotime($row['event_time'])),
                    'module' => $row['module'],
                    'user_id' => $row['user_ref'],
                    'name' => $row['user_name'],
                    'group' => $row['user_group'],
                    'type' => $row['event_type'],
                    'status' => $row['status']
                ];
            }
            echo json_encode(['status' => 'success', 'data' => $rows]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error']);
        }
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Unknown action']);
    exit;
}

if (!check_permission('attendance_manage') && !check_permission('attendance_reports') && !check_permission('attendance_scan')) {
    header('location:manage-users.php?error=access_denied');
    exit();
}

$modules = [
    'reg' => 'Registration',
    'participant' => 'Participant',
    'poster26' => 'Poster 26',
    'workshop' => 'Workshop'
];

$sessions = [];
$sessionsRes = mysqli_query($con, "SELECT * FROM attendance_sessions ORDER BY start_time DESC, id DESC");
if ($sessionsRes) {
    while ($row = mysqli_fetch_assoc($sessionsRes)) {
        $sessions[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet">
    <link href="assets/font-awesome/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/style-responsive.css" rel="stylesheet">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" type="text/javascript"></script>
    <style>
        .nav-tabs { margin-bottom: 20px; }
        #reader { width: 100%; max-width: 600px; margin: 0 auto; border: 1px solid #ccc; }
        .scan-result-card {
            border: 1px solid #ddd;
            padding: 20px;
            margin-top: 20px;
            border-radius: 5px;
            text-align: center;
            display: none;
        }
        .scan-success { background-color: #dff0d8; border-color: #d6e9c6; color: #3c763d; }
        .scan-error { background-color: #f2dede; border-color: #ebccd1; color: #a94442; }
        .scan-warning { background-color: #fcf8e3; border-color: #faebcc; color: #8a6d3b; }
        .scan-feedback { 
            position: fixed; 
            top: 50%; 
            left: 50%; 
            transform: translate(-50%, -50%); 
            background: rgba(0,0,0,0.8); 
            color: white; 
            padding: 20px; 
            border-radius: 10px; 
            z-index: 9999; 
            display: none; 
            font-size: 1.5em;
        }
        .nav-tabs > li > a { color: #666; }
        .nav-tabs > li.active > a, .nav-tabs > li.active > a:hover, .nav-tabs > li.active > a:focus {
            color: #555;
            cursor: default;
            background-color: #fff;
            border: 1px solid #ddd;
            border-bottom-color: transparent;
        }
    </style>
</head>
<body>
<section id="container">
    <div id="scan-feedback" class="scan-feedback">
        <i class="fa fa-spinner fa-spin"></i> Processing...
    </div>
    <?php include 'includes/header.php'; ?>
    <?php include 'includes/sidebar.php'; ?>

    <section id="main-content">
        <section class="wrapper">
            <h3><i class="fa fa-qrcode"></i> Central Attendance Management</h3>

            <input type="hidden" id="attendance-csrf" value="<?php echo escape_html_att($_SESSION['csrf_token']); ?>">

            <div class="row mt">
                <div class="col-lg-12">
                    <div class="content-panel" style="padding:20px;">
                        <ul class="nav nav-tabs" id="attendanceTabs">
                            <li class="active"><a href="#sessions" data-toggle="tab">Sessions</a></li>
                            <li><a href="#scan" data-toggle="tab">Live Scan</a></li>
                            <li><a href="#reports" data-toggle="tab">Reports</a></li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane active" id="sessions">
                                <div style="margin-top:15px;margin-bottom:15px;">
                                    <?php if (check_permission('attendance_manage')): ?>
                                    <button class="btn btn-success" data-toggle="modal" data-target="#addSessionModal">
                                        <i class="fa fa-plus"></i> Add Session
                                    </button>
                                    <?php endif; ?>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-striped table-advance table-hover">
                                        <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Module</th>
                                            <th>Time</th>
                                            <th>Location</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php if (empty($sessions)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No sessions defined yet.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($sessions as $s): ?>
                                                <tr>
                                                    <td><?php echo (int)$s['id']; ?></td>
                                                    <td><?php echo escape_html_att($s['name']); ?></td>
                                                    <td><?php echo escape_html_att($s['module']); ?></td>
                                                    <td>
                                                        <?php echo escape_html_att($s['start_time']); ?>
                                                        <?php if (!empty($s['end_time'])): ?>
                                                            to <?php echo escape_html_att($s['end_time']); ?>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo escape_html_att($s['location']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane" id="scan">
                                <div class="row" style="margin-top:20px;">
                                    <div class="col-md-6 col-md-offset-3">
                                        <div class="form-group">
                                            <label>Session</label>
                                            <select class="form-control" id="scan-session-select">
                                                <option value="">Select session</option>
                                                <?php foreach ($sessions as $s): ?>
                                                    <option value="<?php echo (int)$s['id']; ?>">
                                                        <?php echo escape_html_att($s['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group text-center">
                                            <label style="display:block;margin-bottom:10px;">Scan Mode</label>
                                            <div class="btn-group" data-toggle="buttons">
                                                <label class="btn btn-primary active" style="min-width:120px;">
                                                    <input type="radio" name="scan_mode" id="mode_in" value="in" autocomplete="off" checked> 
                                                    <i class="fa fa-sign-in"></i> Sign In
                                                </label>
                                                <label class="btn btn-warning" style="min-width:120px;">
                                                    <input type="radio" name="scan_mode" id="mode_out" value="out" autocomplete="off"> 
                                                    <i class="fa fa-sign-out"></i> Sign Out
                                                </label>
                                            </div>
                                        </div>

                                        <div class="panel panel-default">
                                            <div class="panel-heading">QR Code Scan</div>
                                            <div class="panel-body">
                                                <div id="reader-container" style="display:none;margin-bottom:20px;">
                                                    <div id="reader"></div>
                                                    <div class="text-center mt">
                                                        <button class="btn btn-danger" id="stop-scan" style="margin-top:10px;">Stop Scanning</button>
                                                    </div>
                                                </div>
                                                <div class="text-center mt" id="start-scan-container">
                                                    <button class="btn btn-primary btn-lg" id="start-scan" disabled>
                                                        <i class="fa fa-camera"></i> Start Camera Scan
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="panel panel-default" style="margin-top:20px;">
                                            <div class="panel-heading">Manual Entry</div>
                                            <div class="panel-body">
                                                <div class="row">
                                                    <div class="col-sm-4">
                                                        <select class="form-control" id="manual-module">
                                                            <?php foreach ($modules as $key => $label): ?>
                                                                <option value="<?php echo escape_html_att($key); ?>"><?php echo escape_html_att($label); ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="col-sm-5">
                                                        <input type="text" class="form-control" id="manual-ref" placeholder="User ID or Email">
                                                    </div>
                                                    <div class="col-sm-3">
                                                        <button class="btn btn-warning btn-block" id="btn-manual-add" type="button" disabled>Add Manual</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="scan-result" class="scan-result-card">
                                            <h4 id="scan-status-title"></h4>
                                            <p id="scan-message" style="font-size:1.2em;"></p>
                                            <div id="user-details" style="font-size:1.1em;margin-top:10px;"></div>
                                        </div>

                                        <div class="panel panel-primary" style="margin-top:20px;">
                                            <div class="panel-heading">
                                                <div class="row">
                                                    <div class="col-xs-6">
                                                        <i class="fa fa-list"></i> Live Attendee List
                                                        <span class="badge bg-inverse" id="live-count">0</span>
                                                    </div>
                                                    <div class="col-xs-6 text-right">
                                                        <input type="text" id="live-filter" class="form-control input-sm" placeholder="Filter..." style="display:inline-block;width:150px;color:#333;">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                                <table class="table table-striped table-hover" id="live-scan-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Time</th>
                                                            <th>Name</th>
                                                            <th>Ref ID</th>
                                                            <th>Group</th>
                                                            <th>Type</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr id="no-scans-row">
                                                            <td colspan="5" class="text-center">Waiting for scans...</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane" id="reports">
                                <div class="row mb" style="margin-top:20px;margin-bottom:20px;">
                                    <div class="col-md-3">
                                        <select class="form-control" id="report-session">
                                            <option value="">All sessions</option>
                                            <?php foreach ($sessions as $s): ?>
                                                <option value="<?php echo (int)$s['id']; ?>"><?php echo escape_html_att($s['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-control" id="report-module">
                                            <option value="">All modules</option>
                                            <?php foreach ($modules as $key => $label): ?>
                                                <option value="<?php echo escape_html_att($key); ?>"><?php echo escape_html_att($label); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" class="form-control" id="report-from">
                                    </div>
                                    <div class="col-md-2">
                                        <input type="date" class="form-control" id="report-to">
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="report-group" placeholder="User group">
                                            <span class="input-group-btn">
                                                <button class="btn btn-primary" id="btn-filter-report">Filter</button>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="row" style="margin-bottom:30px;">
                                    <div class="col-md-12">
                                        <div style="width:100%;height:300px;">
                                            <canvas id="attendanceChart"></canvas>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Session</th>
                                            <th>Module</th>
                                            <th>User</th>
                                            <th>Email</th>
                                            <th>Group</th>
                                            <th>Type</th>
                                            <th>Status</th>
                                            <th>Source</th>
                                        </tr>
                                        </thead>
                                        <tbody id="report-list">
                                        <tr><td colspan="9" class="text-center">Use filters to load attendance data.</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </section>
</section>

<div class="modal fade" id="addSessionModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Add Session</h4>
            </div>
            <div class="modal-body">
                <form id="add-session-form">
                    <input type="hidden" name="action" value="add_session">
                    <input type="hidden" name="csrf_token" value="<?php echo escape_html_att($_SESSION['csrf_token']); ?>">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Module</label>
                        <select name="module" class="form-control">
                            <option value="">General</option>
                            <?php foreach ($modules as $key => $label): ?>
                                <option value="<?php echo escape_html_att($key); ?>"><?php echo escape_html_att($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start time</label>
                        <input type="datetime-local" name="start_time" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>End time</label>
                        <input type="datetime-local" name="end_time" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="btn-save-session">Save</button>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/jquery.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/chart-master/Chart.js"></script>
<script class="include" type="text/javascript" src="assets/js/jquery.dcjqaccordion.2.7.js"></script>
<script src="assets/js/jquery.scrollTo.min.js"></script>
<script src="assets/js/jquery.nicescroll.js" type="text/javascript"></script>
<script src="assets/js/common-scripts.js"></script>

<script>
$(document).ready(function () {
    var csrf = $('#attendance-csrf').val();

    $('#live-filter').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $("#live-scan-table tbody tr").filter(function() {
            if (this.id === 'no-scans-row') return false;
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    $('#scan-session-select').change(function () {
        var sid = $(this).val();
        if (sid) {
            $('#start-scan').prop('disabled', false);
            loadRecentScans(sid);
        } else {
            $('#start-scan').prop('disabled', true);
            $('#live-scan-table tbody').html('<tr id="no-scans-row"><td colspan="5" class="text-center">Select a session to view scans</td></tr>');
            $('#live-count').text('0');
        }
    });

    function loadRecentScans(sessionId) {
        $.post('attendance-dashboard.php', {
            action: 'get_recent_scans',
            csrf_token: csrf,
            session_id: sessionId
        }, function (res) {
            if (res.status === 'success') {
                var tbody = $('#live-scan-table tbody');
                tbody.empty();
                var count = res.data.length;
                $('#live-count').text(count);
                
                if (count === 0) {
                    tbody.html('<tr id="no-scans-row"><td colspan="5" class="text-center">No scans yet</td></tr>');
                } else {
                    // Reverse to show newest at top when prepending
                    res.data.reverse().forEach(function (row) {
                        addScanRow(row, false);
                    });
                }
            }
        }, 'json');
    }

    function addScanRow(data, animate) {
        var tbody = $('#live-scan-table tbody');
        if ($('#no-scans-row').length > 0) {
            $('#no-scans-row').remove();
        }
        
        var typeLabel = data.type === 'in' ? '<span class="label label-success">IN</span>' : '<span class="label label-warning">OUT</span>';
        var rowHtml = '<tr class="' + (animate ? 'success' : '') + '">' +
            '<td>' + data.time + '</td>' +
            '<td>' + (data.name || 'Unknown') + '</td>' +
            '<td>' + data.user_id + '</td>' +
            '<td>' + (data.group || '-') + '</td>' +
            '<td>' + typeLabel + '</td>' +
            '</tr>';
            
        var $row = $(rowHtml);
        tbody.prepend($row);
        
        // Update count
        var currentCount = parseInt($('#live-count').text()) || 0;
        if (animate) $('#live-count').text(currentCount + 1);
        
        if (animate) {
            setTimeout(function() {
                $row.removeClass('success');
            }, 2000);
        }
    }

    $('#manual-ref').on('input', function () {
        var hasSession = $('#scan-session-select').val() !== '';
        $('#btn-manual-add').prop('disabled', !hasSession || $(this).val().trim() === '');
    });

    $('#btn-save-session').click(function () {
        $.post('attendance-dashboard.php', $('#add-session-form').serialize(), function (res) {
            if (res.status === 'success') {
                alert('Session created.');
                location.reload();
            } else {
                alert(res.message || 'Error creating session.');
            }
        }, 'json').fail(function () {
            alert('Network error');
        });
    });

    var html5QrcodeScanner = null;
    var isProcessing = false;

    $('#start-scan').click(function () {
        var sessionId = $('#scan-session-select').val();
        if (!sessionId) {
            alert('Select a session first.');
            return;
        }
        $('#start-scan-container').hide();
        $('#reader-container').show();
        html5QrcodeScanner = new Html5Qrcode('reader');
        var config = { 
            fps: 10, 
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0
        };
        html5QrcodeScanner.start(
            { facingMode: 'environment' },
            config,
            onScanSuccess,
            onScanFailure
        ).catch(function (err) {
            alert('Error starting camera: ' + err + '\nPlease ensure you are using HTTPS or localhost.');
            $('#reader-container').hide();
            $('#start-scan-container').show();
        });
    });

    $('#stop-scan').click(function () {
        stopScanner();
    });

    function stopScanner() {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(function () {
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
                $('#reader-container').hide();
                $('#start-scan-container').show();
            }).catch(function () {});
        }
    }

    function onScanSuccess(decodedText, decodedResult) {
        if (isProcessing) return;
        isProcessing = true;
        
        // Show feedback
        $('#scan-feedback').show();
        console.log("Scanned:", decodedText);

        var sessionId = $('#scan-session-select').val();
        var scanMode = $('input[name="scan_mode"]:checked').val();
        $.post('attendance-dashboard.php', {
            action: 'record_attendance',
            csrf_token: csrf,
            session_id: sessionId,
            qr_data: decodedText,
            scan_mode: scanMode
        }, function (res) {
            $('#scan-feedback').hide();
            showScanResult(res);
            setTimeout(function () {
                isProcessing = false;
                $('#scan-result').fadeOut();
            }, 3000);
        }, 'json').fail(function (xhr, status, error) {
            $('#scan-feedback').hide();
            isProcessing = false;
            console.error("Ajax error:", error);
            alert('Network error or server error');
        });
    }

    function onScanFailure(error) {}

    function showScanResult(res) {
        var el = $('#scan-result');
        el.removeClass('scan-success scan-error scan-warning').show();
        if (res.status === 'success') {
            var type = res.event_type === 'in' ? 'Check-in' : 'Check-out';
            el.addClass('scan-success');
            $('#scan-status-title').html('<i class="fa fa-check-circle"></i> ' + type + ' recorded');
            $('#scan-message').text(res.user.name);
            $('#user-details').html(
                (res.user.email ? res.user.email + '<br>' : '') +
                (res.user.group ? res.user.group : '')
            );
            
            // Add to table
            var now = new Date();
            var timeStr = now.getHours().toString().padStart(2, '0') + ':' + 
                          now.getMinutes().toString().padStart(2, '0') + ':' + 
                          now.getSeconds().toString().padStart(2, '0');
                          
            addScanRow({
                time: timeStr,
                name: res.user.name,
                user_id: res.user.id,
                group: res.user.group,
                type: res.event_type
            }, true);
            
        } else {
            el.addClass('scan-error');
            $('#scan-status-title').html('<i class="fa fa-times-circle"></i> Error');
            $('#scan-message').text(res.message || 'Unable to record attendance');
            $('#user-details').empty();
        }
    }

    $('#btn-manual-add').click(function () {
        var sessionId = $('#scan-session-select').val();
        var module = $('#manual-module').val();
        var ref = $('#manual-ref').val().trim();
        if (!sessionId || !module || !ref) {
            alert('Select session, module and provide user reference.');
            return;
        }
        $.post('attendance-dashboard.php', {
            action: 'manual_attendance',
            csrf_token: csrf,
            session_id: sessionId,
            module: module,
            user_ref: ref,
            event_type: 'in'
        }, function (res) {
            showScanResult(res);
            if (res.status === 'success') {
                $('#manual-ref').val('');
                $('#btn-manual-add').prop('disabled', true);
            }
        }, 'json').fail(function () {
            alert('Network error');
        });
    });

    $('#btn-filter-report').click(function () {
        loadReport();
    });

    function loadReport() {
        var params = {
            action: 'get_report',
            csrf_token: csrf,
            session_id: $('#report-session').val(),
            module: $('#report-module').val(),
            from: $('#report-from').val(),
            to: $('#report-to').val(),
            user_group: $('#report-group').val()
        };
        $.post('attendance-dashboard.php', params, function (res) {
            if (res.status !== 'success') {
                alert(res.message || 'Error loading report');
                return;
            }
            var rows = res.data || [];
            var tbody = $('#report-list');
            tbody.empty();
            if (rows.length === 0) {
                tbody.append('<tr><td colspan="9" class="text-center">No records found.</td></tr>');
            } else {
                rows.forEach(function (r) {
                    var tr = '<tr>' +
                        '<td>' + r.time + '</td>' +
                        '<td>' + r.session + '</td>' +
                        '<td>' + r.module + '</td>' +
                        '<td>' + r.user + '</td>' +
                        '<td>' + r.email + '</td>' +
                        '<td>' + (r.group || '') + '</td>' +
                        '<td>' + r.type + '</td>' +
                        '<td>' + (r.status || '') + '</td>' +
                        '<td>' + r.source + '</td>' +
                        '</tr>';
                    tbody.append(tr);
                });
            }
            updateChart(rows);
        }, 'json').fail(function () {
            alert('Network error');
        });
    }

    function updateChart(data) {
        var ctx = document.getElementById('attendanceChart').getContext('2d');
        var counts = { in: 0, out: 0 };
        data.forEach(function (r) {
            if (r.type === 'in') counts.in++;
            if (r.type === 'out') counts.out++;
        });
        var chartData = {
            labels: ['Check-in', 'Check-out'],
            datasets: [{
                data: [counts.in, counts.out],
                backgroundColor: ['#5cb85c', '#f0ad4e'],
                hoverBackgroundColor: ['#4cae4c', '#ec971f']
            }]
        };
        if (window.attendanceChartInstance) {
            window.attendanceChartInstance.destroy();
        }
        window.attendanceChartInstance = new Chart(ctx).Pie(chartData);
    }
});
</script>

</body>
</html>

