<?php
require_once('../dbconnection.php');
header('Content-Type: text/plain');
if (!$con) {
  echo "CONNECTION=false\n";
  exit;
}
$r = mysqli_query($con, "SELECT 1");
if ($r) {
  $row = mysqli_fetch_row($r);
  echo "OK " . $row[0] . "\n";
} else {
  echo "QUERY_FAIL: " . mysqli_error($con) . "\n";
}
