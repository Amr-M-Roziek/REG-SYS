<?php
// Deployment Verification Script for Local XAMPP Environment

header('Content-Type: text/html; charset=utf-8');

function check_db_connection($name, $host, $user, $pass, $dbname) {
    echo "<div style='margin-bottom: 10px; padding: 10px; border: 1px solid #ccc; border-radius: 4px;'>";
    echo "<strong>Checking Database: $name</strong><br>";
    echo "Host: $host | User: $user | DB: $dbname<br>";
    
    $start = microtime(true);
    try {
        $con = @mysqli_connect($host, $user, $pass, $dbname);
        if ($con) {
            $latency = round((microtime(true) - $start) * 1000, 2);
            echo "<span style='color: green; font-weight: bold;'>[SUCCESS] Connected successfully ($latency ms)</span>";
            
            // Check table count
            $result = mysqli_query($con, "SHOW TABLES");
            $tableCount = mysqli_num_rows($result);
            echo "<br>Found $tableCount tables.";
            
            mysqli_close($con);
            return true;
        } else {
            echo "<span style='color: red; font-weight: bold;'>[FAILED] Connection error: " . mysqli_connect_error() . "</span>";
            return false;
        }
    } catch (Exception $e) {
        echo "<span style='color: red; font-weight: bold;'>[FAILED] Exception: " . $e->getMessage() . "</span>";
        return false;
    }
    echo "</div>";
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Local Deployment Check</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; line-height: 1.6; }
        h1 { border-bottom: 2px solid #333; padding-bottom: 10px; }
        .summary { background: #f9f9f9; padding: 15px; border-left: 5px solid #007bff; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>Local Deployment Verification</h1>
    
    <div class="summary">
        <p>This script verifies that your local XAMPP environment is correctly configured to run the reg-sys.com project.</p>
        <p><strong>Environment:</strong> <?php echo php_uname(); ?></p>
        <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
    </div>

    <h2>Database Connections</h2>
    
    <?php
    $host = '127.0.0.1';
    $user = 'root';
    $pass = ''; // Default XAMPP password is empty

    $results = [];

    // 1. Check regsys_reg (Admin)
    $results['regsys_reg'] = check_db_connection('Admin DB (regsys_reg)', $host, $user, $pass, 'regsys_reg');

    // 2. Check regsys_participant (Participant)
    $results['regsys_participant'] = check_db_connection('Participant DB (regsys_participant)', $host, $user, $pass, 'regsys_participant');

    // 3. Check regsys_poster26 (Scientific/Poster)
    $results['regsys_poster26'] = check_db_connection('Poster/Scientific DB (regsys_poster26)', $host, $user, $pass, 'regsys_poster26');
    ?>

    <h2>Next Steps</h2>
    <ul>
        <?php if (in_array(false, $results)): ?>
        <li style="color: red;"><strong>One or more database connections failed.</strong> Please check if the databases are created and imported in phpMyAdmin.</li>
        <li>You can find SQL dumps in <code>exports/</code> or <code>icpm2026/scientific/schema.sql</code>.</li>
        <?php else: ?>
        <li style="color: green;"><strong>All systems go!</strong> Your database connections are working.</li>
        <li>You can access the admin panel at <a href="/reg-sys.com/icpm2026/admin/">/reg-sys.com/icpm2026/admin/</a></li>
        <li>You can access the participant portal at <a href="/reg-sys.com/icpm2026/participant/">/reg-sys.com/icpm2026/participant/</a></li>
        <?php endif; ?>
    </ul>

</body>
</html>
