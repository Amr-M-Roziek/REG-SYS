<?php
session_start();
include 'dbconnection.php';

if (!isset($_SESSION['id'])) {
    header("Location: index.php");
    exit();
}

mysqli_set_charset($con, 'utf8mb4');
$targetCategory = 'Participant';

// Update where category is NOT 'Participant' OR is NULL
$sql = "UPDATE users SET category = ? WHERE category != ? OR category IS NULL OR category = ''";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "ss", $targetCategory, $targetCategory);

if (mysqli_stmt_execute($stmt)) {
    $affected = mysqli_stmt_affected_rows($stmt);
    $_SESSION['msg'] = "Category standardization complete. Updated $affected users to '$targetCategory'.";
} else {
    $_SESSION['msg'] = "Error updating categories: " . mysqli_error($con);
}

header("Location: manage-users.php");
exit();
?>
