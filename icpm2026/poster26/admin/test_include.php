<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "Current Dir: " . __DIR__ . "<br>";
echo "Trying to include: " . __DIR__ . '/../../logging_helper.php' . "<br>";
if (file_exists(__DIR__ . '/../../logging_helper.php')) {
    echo "File exists!<br>";
} else {
    echo "File NOT found!<br>";
}
require_once __DIR__ . '/../../logging_helper.php';
echo "Include successful!<br>";
?>
