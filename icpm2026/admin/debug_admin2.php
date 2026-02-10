<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Adjust path if needed, assuming admin2 is at same level as admin
include 'dbconnection.php'; 

echo "<h1>Admin2 Debugger</h1>";

if (!$con) {
    die("Connection Failed: " . mysqli_connect_error());
}
echo "Connected to database: " . DB_NAME . "<br>";

// 1. Check Table Columns
echo "<h2>1. Checking Users Table Schema</h2>";
$columns = [];
$res = mysqli_query($con, "SHOW COLUMNS FROM users");
while($row = mysqli_fetch_assoc($res)) {
    $columns[] = $row['Field'];
}

$required = ['profession', 'organization', 'category', 'created_at'];
$missing = [];

foreach($required as $req) {
    if (in_array($req, $columns)) {
        echo "<span style='color:green'>Found column: $req</span><br>";
    } else {
        echo "<span style='color:red; font-weight:bold'>MISSING column: $req</span><br>";
        $missing[] = $req;
    }
}

// 2. Run the Query from manage-users.php
echo "<h2>2. Testing Main Query</h2>";
if (empty($missing)) {
    $sql = "SELECT * FROM users LIMIT 5";
    $result = mysqli_query($con, $sql);
    
    if ($result) {
        $count = mysqli_num_rows($result);
        echo "Query Successful. Found $count users.<br>";
        if ($count > 0) {
            $row = mysqli_fetch_assoc($result);
            echo "First User: " . $row['fname'] . " " . $row['lname'] . "<br>";
        } else {
            echo "Table is empty.<br>";
        }
    } else {
        echo "Query Failed: " . mysqli_error($con) . "<br>";
    }
} else {
    echo "Skipping query test because columns are missing.<br>";
    echo "<strong>Please run update_schema.php again!</strong>";
}
?>
