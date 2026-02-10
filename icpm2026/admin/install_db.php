<?php
// install_db.php
// Helper to run the SQL setup
include 'dbconnection.php';

echo "<h1>Installing Admin System Database...</h1>";

if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

$sqlFile = 'setup_rbac.sql';
if (!file_exists($sqlFile)) {
    die("SQL file not found: $sqlFile");
}

$sql = file_get_contents($sqlFile);
$queries = explode(';', $sql);

foreach ($queries as $query) {
    $query = trim($query);
    if (!empty($query)) {
        if (mysqli_query($con, $query)) {
            echo "<p style='color:green'>Success: " . substr($query, 0, 50) . "...</p>";
        } else {
            // Ignore "Duplicate column name" errors which happen if running multiple times
            if (strpos(mysqli_error($con), "Duplicate column name") !== false) {
                echo "<p style='color:orange'>Skipped (Exists): " . substr($query, 0, 50) . "...</p>";
            } else {
                echo "<p style='color:red'>Error: " . mysqli_error($con) . "</p>";
            }
        }
    }
}

echo "<h3>Done! You can now <a href='manage-users.php'>go to the dashboard</a>.</h3>";
echo "<p><em>Note: If you can't login, ensure your admin user has a role assigned.</em></p>";
?>
