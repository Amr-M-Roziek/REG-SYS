<?php
// session_manager.php

/**
 * Initialize the session table if it doesn't exist
 */
function initialize_session_table($con) {
    // Active Sessions Table
    $sql = "CREATE TABLE IF NOT EXISTS user_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        session_id VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        login_time INT NOT NULL,
        last_activity INT NOT NULL,
        UNIQUE KEY unique_user (user_id),
        INDEX (session_id)
    )";
    mysqli_query($con, $sql);
    
    // Audit Logs Table
    $sql_audit = "CREATE TABLE IF NOT EXISTS user_audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        event_type VARCHAR(50) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id)
    )";
    mysqli_query($con, $sql_audit);
}

/**
 * Log session events
 */
function log_session_event($con, $user_id, $event_type, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $stmt = mysqli_prepare($con, "INSERT INTO user_audit_logs (user_id, event_type, details, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isss", $user_id, $event_type, $details, $ip);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

/**
 * Register a user's session in the database upon login
 * Handles duplicate session prevention by overwriting existing sessions
 */
function login_user_session($con, $user_id) {
    initialize_session_table($con); // Ensure table exists
    
    $session_id = session_id();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $current_time = time();
    
    // Check if session exists (to log duplicate login attempt if needed, 
    // but here we just overwrite, implying a new login)
    // Actually, if a row exists, it means we are kicking out a previous session.
    $check_stmt = mysqli_prepare($con, "SELECT id FROM user_sessions WHERE user_id = ?");
    mysqli_stmt_bind_param($check_stmt, "i", $user_id);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        log_session_event($con, $user_id, 'SESSION_OVERWRITE', "New login detected, terminating previous session.");
    }
    mysqli_stmt_close($check_stmt);

    // Insert or Update
    $stmt = mysqli_prepare($con, "INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, login_time, last_activity) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE session_id=?, ip_address=?, user_agent=?, login_time=?, last_activity=?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "isssiisssii", $user_id, $session_id, $ip_address, $user_agent, $current_time, $current_time, $session_id, $ip_address, $user_agent, $current_time, $current_time);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    
    log_session_event($con, $user_id, 'LOGIN', "User logged in successfully.");
    
    $_SESSION['login_time'] = $current_time;
    $_SESSION['last_activity'] = $current_time;
}

/**
 * Check if the current session is valid
 * Returns: 'valid', 'duplicate', 'timeout', 'invalid'
 */
function check_session_validity($con) {
    if (!isset($_SESSION['id'])) {
        return 'invalid'; // Not logged in
    }

    $user_id = $_SESSION['id'];
    $session_id = session_id();
    $max_inactive_time = 1800; // 30 minutes auto-logout
    
    // Check DB for active session
    $stmt = mysqli_prepare($con, "SELECT session_id, last_activity FROM user_sessions WHERE user_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        if ($row) {
            // 1. Duplicate Session Check
            if ($row['session_id'] !== $session_id) {
                log_session_event($con, $user_id, 'DUPLICATE_BLOCK', "Session ID mismatch (Concurrent login).");
                return 'duplicate';
            }
            
            // 2. Timeout Check
            if (time() - $row['last_activity'] > $max_inactive_time) {
                // Remove from DB
                $del_stmt = mysqli_prepare($con, "DELETE FROM user_sessions WHERE user_id = ?");
                mysqli_stmt_bind_param($del_stmt, "i", $user_id);
                mysqli_stmt_execute($del_stmt);
                mysqli_stmt_close($del_stmt);
                
                log_session_event($con, $user_id, 'TIMEOUT', "Session expired due to inactivity.");
                return 'timeout';
            }
            
            // Update last activity
            $update_stmt = mysqli_prepare($con, "UPDATE user_sessions SET last_activity = ? WHERE user_id = ?");
            $now = time();
            mysqli_stmt_bind_param($update_stmt, "ii", $now, $user_id);
            mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
            
            $_SESSION['last_activity'] = time();
            return 'valid';
        }
    }
    
    // No session in DB (maybe deleted manually or never created)
    return 'invalid_db';
}

/**
 * Check if user is allowed to logout based on 5-minute rule
 * Returns: true if allowed, or integer (seconds remaining) if not
 */
function can_logout() {
    if (!isset($_SESSION['login_time'])) {
        return true; // Should allow logout if session tracking is missing
    }
    
    $min_duration = 300; // 5 minutes
    $elapsed = time() - $_SESSION['login_time'];
    
    if ($elapsed < $min_duration) {
        return $min_duration - $elapsed; // Return seconds remaining
    }
    
    return true;
}

/**
 * Clear session from DB on logout
 */
function clear_user_session($con, $user_id) {
    $stmt = mysqli_prepare($con, "DELETE FROM user_sessions WHERE user_id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
    log_session_event($con, $user_id, 'LOGOUT', "User logged out manually.");
}
?>