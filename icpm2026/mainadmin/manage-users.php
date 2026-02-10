<?php
// Redirect to the correct location in the admin directory
// Preserves query parameters if any
$query = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header("Location: ../admin/manage-users.php" . $query);
exit();
?>
