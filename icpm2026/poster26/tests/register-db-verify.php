<?php
// Unit-style test: verify DB insert and fields after registration
error_reporting(E_ALL);
header('Content-Type: text/plain');
require_once('../dbconnection.php');
$email = isset($_GET['email']) ? $_GET['email'] : '';
if ($email === '') { echo "NO_EMAIL\n"; exit; }
$stmt = mysqli_prepare($con, "SELECT id,email,fname,category,organization,postertitle FROM users WHERE email=? LIMIT 1");
if (!$stmt) { echo "PREP_FAIL ".mysqli_error($con)."\n"; exit; }
mysqli_stmt_bind_param($stmt,'s',$email);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$row = mysqli_fetch_assoc($res);
if (!$row) { echo "NOT_FOUND\n"; exit; }
$required = ['id','email','fname','category','organization'];
$ok = true;
foreach ($required as $k) { if (!isset($row[$k]) || $row[$k]==='') { $ok=false; break; } }
echo $ok ? "RESULT=PASS id=".$row['id']."\n" : "RESULT=FAIL\n";
