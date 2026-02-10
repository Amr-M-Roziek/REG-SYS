<?php
// Try multiple connection strategies
$strategies = [
    ['127.0.0.1', 'root', '', 'regsys_reg'],
    ['localhost', 'root', '', 'regsys_reg'],
    ['127.0.0.1', 'root', '', 'regsys_reg', 3306],
    ['localhost', 'root', '', 'regsys_reg', 3306],
    ['localhost', 'regsys_reg', 'regsys@2025', 'regsys_reg'], // Production fallback
];

$con = null;
foreach ($strategies as $s) {
    echo "Trying " . $s[0] . " user=" . $s[1] . " ... ";
    try {
        if (isset($s[4])) {
            $con = @mysqli_connect($s[0], $s[1], $s[2], $s[3], $s[4]);
        } else {
            $con = @mysqli_connect($s[0], $s[1], $s[2], $s[3]);
        }
        
        if ($con) {
            echo "Success!\n";
            break;
        } else {
            echo "Failed: " . mysqli_connect_error() . "\n";
        }
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
    }
}

if (!$con) {
    die("All connections failed.\n");
}

$res = mysqli_query($con, "SELECT * FROM certificate_templates WHERE name='Final' ORDER BY id DESC LIMIT 1");
if ($row = mysqli_fetch_assoc($res)) {
    echo "TEMPLATE_FOUND\n";
    echo $row['data'];
} else {
    // Try Default
    $res = mysqli_query($con, "SELECT * FROM certificate_templates WHERE name='Default' ORDER BY id DESC LIMIT 1");
    if ($row = mysqli_fetch_assoc($res)) {
        echo "DEFAULT_TEMPLATE_FOUND\n";
        echo $row['data'];
    } else {
        echo "Not Found";
    }
}
?>
