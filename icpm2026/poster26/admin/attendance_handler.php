<?php
require_once 'session_setup.php';
include 'dbconnection.php';
require_once 'permission_helper.php';

// Auth Check
if (empty($_SESSION['id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized');
}

// Audit Logging Helper
function log_attendance_action($con, $action, $details) {
    $admin_id = $_SESSION['id'];
    $admin_username = isset($_SESSION['login']) ? $_SESSION['login'] : 'Unknown';
    $ip = $_SERVER['REMOTE_ADDR'];
    $system_context = 'poster_attendance';
    
    $stmt = mysqli_prepare($con, "INSERT INTO admin_audit_logs (admin_id, admin_username, action, details, ip_address, system_context) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'isssss', $admin_id, $admin_username, $action, $details, $ip, $system_context);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Set JSON header
header('Content-Type: application/json');

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'add_lecture') {
    $title = $_POST['title'];
    $lecturer = $_POST['lecturer'];
    $start = $_POST['start_time'];
    $end = $_POST['end_time'];
    $location = $_POST['location'];
    
    $stmt = mysqli_prepare($con, "INSERT INTO lectures (title, lecturer_name, start_time, end_time, location) VALUES (?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssss", $title, $lecturer, $start, $end, $location);
    
    if (mysqli_stmt_execute($stmt)) {
        log_attendance_action($con, 'add_lecture', "Added lecture: $title");
        echo json_encode(['status' => 'success', 'message' => 'Lecture added successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($con)]);
    }
} 
elseif ($action === 'get_lectures') {
    $result = mysqli_query($con, "SELECT *, (SELECT COUNT(*) FROM attendance WHERE lecture_id = lectures.id) as attendee_count FROM lectures ORDER BY start_time DESC");
    $lectures = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $lectures[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $lectures]);
}
elseif ($action === 'record_attendance') {
    $lecture_id = $_POST['lecture_id'];
    // QR code might be "123-CO1" or just "123". We need to parse it.
    // Assuming QR format is "ID" or "ID-SUFFIX".
    $qr_data = $_POST['qr_data'];
    
    // Log for debugging
    // error_log("Received QR scan: " . $qr_data);

    $parts = explode('-', $qr_data);
    $user_id = intval($parts[0]);
    
    if ($user_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid QR Code Format: ' . $qr_data]);
        exit;
    }
    
    // Verify user exists
    $user_query = mysqli_query($con, "SELECT id, fname, email, postertitle FROM users WHERE id = $user_id");
    if (mysqli_num_rows($user_query) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }
    $user = mysqli_fetch_assoc($user_query);
    
    // Check if already attended
    $check_stmt = mysqli_prepare($con, "SELECT id FROM attendance WHERE lecture_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($check_stmt, "ii", $lecture_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        echo json_encode(['status' => 'warning', 'message' => 'Already checked in', 'user' => $user]);
    } else {
        // Record attendance
        // Check time for status (Late?)
        $lec_query = mysqli_query($con, "SELECT start_time FROM lectures WHERE id = $lecture_id");
        $lec = mysqli_fetch_assoc($lec_query);
        $start_time = strtotime($lec['start_time']);
        $current_time = time();
        $status = 'present';
        
        // If more than 15 mins late
        if ($current_time > $start_time + (15 * 60)) {
            $status = 'late';
        }
        
        $insert_stmt = mysqli_prepare($con, "INSERT INTO attendance (lecture_id, user_id, status) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($insert_stmt, "iis", $lecture_id, $user_id, $status);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            log_attendance_action($con, 'record_attendance', "Recorded attendance for User ID: $user_id at Lecture ID: $lecture_id ($status)");
            echo json_encode(['status' => 'success', 'message' => 'Attendance recorded', 'user' => $user, 'att_status' => $status]);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($con)]);
        }
    }
}
elseif ($action === 'manual_attendance') {
    $lecture_id = $_POST['lecture_id'];
    $email_or_id = $_POST['email_or_id'];
    
    // Find user by ID or Email
    $stmt = mysqli_prepare($con, "SELECT id, fname, email, postertitle FROM users WHERE (id = ? OR email = ?) AND (source_system='poster' OR source_system='both') LIMIT 1");
    // bind params: id is int, email is string. But here we treat both as string or check if numeric
    // Simpler: just bind both as string, MySQL handles type conversion for ID
    mysqli_stmt_bind_param($stmt, "ss", $email_or_id, $email_or_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($res) == 0) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }
    $user = mysqli_fetch_assoc($res);
    $user_id = $user['id'];
    
    // Check if already attended
    $check_stmt = mysqli_prepare($con, "SELECT id FROM attendance WHERE lecture_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($check_stmt, "ii", $lecture_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        echo json_encode(['status' => 'warning', 'message' => 'User already checked in', 'user' => $user]);
    } else {
        $status = 'present'; // Manual add is typically "present"
        
        $insert_stmt = mysqli_prepare($con, "INSERT INTO attendance (lecture_id, user_id, status) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($insert_stmt, "iis", $lecture_id, $user_id, $status);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            log_attendance_action($con, 'manual_attendance', "Manually added User ID: $user_id to Lecture ID: $lecture_id");
            echo json_encode(['status' => 'success', 'message' => 'Attendance recorded manually', 'user' => $user]);
        } else {
            echo json_encode(['status' => 'error', 'message' => mysqli_error($con)]);
        }
    }
}
elseif ($action === 'get_report') {
    $lecture_id = isset($_POST['lecture_id']) ? $_POST['lecture_id'] : null;
    $sql = "SELECT a.*, u.fname, u.email, u.organization, l.title as lecture_title 
            FROM attendance a 
            JOIN users u ON a.user_id = u.id 
            JOIN lectures l ON a.lecture_id = l.id";
            
    if ($lecture_id) {
        $sql .= " WHERE a.lecture_id = " . intval($lecture_id);
    }
    $sql .= " ORDER BY a.scan_time DESC";
    
    $result = mysqli_query($con, $sql);
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    echo json_encode(['status' => 'success', 'data' => $data]);
}
?>
