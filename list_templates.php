<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    echo "Attempting connection 1 (localhost, root, empty, regsys_reg)...\n";
    $con = mysqli_connect('localhost', 'root', '', 'regsys_reg');
    echo "Connected successfully.\n";
} catch (Exception $e) {
    echo "Connection 1 failed: " . $e->getMessage() . "\n";
    try {
        echo "Attempting connection 2 (localhost, regsys_part, regsys@2025, regsys_participant)...\n";
        $con = mysqli_connect('localhost', 'regsys_part', 'regsys@2025', 'regsys_participant');
        echo "Connected successfully.\n";
    } catch (Exception $ex) {
        echo "Connection 2 failed: " . $ex->getMessage() . "\n";
        exit;
    }
}

$query = "SELECT id, name, content FROM certificate_templates";
$result = mysqli_query($con, $query);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "ID: " . $row['id'] . "\n";
        echo "Name: " . $row['name'] . "\n";
        echo "Content: " . $row['content'] . "\n";
        echo "----------------------------------------\n";
    }
}
?>
