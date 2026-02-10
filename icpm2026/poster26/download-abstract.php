<?php
ini_set('session.save_path', sys_get_temp_dir());
ini_set('session.cookie_secure', 0);
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_path', '/');
session_start();
include 'dbconnection.php';

// Check if user is logged in
if (!isset($_SESSION['id']) || strlen($_SESSION['id']) == 0) {
    header('location:index.php');
    exit();
}

// Security: Only allow downloading own abstract
$id = $_SESSION['id'];

// Fetch abstract data from database
$query = "SELECT abstract_filename, abstract_mime, abstract_blob FROM users WHERE id=? AND (source_system='poster' OR source_system='both')";
$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $filename = $row['abstract_filename'];
    $mime = $row['abstract_mime'];
    $content = $row['abstract_blob'];
    
    if ($content) {
        // Determine MIME type if not stored
        if (empty($mime)) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $map = array(
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'txt' => 'text/plain'
            );
            $mime = isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
        }
        
        // Set headers
        header("Content-Type: " . $mime);
        header("Content-Length: " . strlen($content));
        header("Content-Disposition: inline; filename=\"" . $filename . "\"");
        
        echo $content;
        exit();
    } else {
        die("No abstract file found.");
    }
} else {
    die("User not found.");
}
?>
