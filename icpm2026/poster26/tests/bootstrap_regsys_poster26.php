<?php
$host = '127.0.0.1';
$user = 'regsys_poster';
$pass = 'regsys@2025';
$link = @mysqli_connect($host, $user, $pass);
header('Content-Type: text/plain');
if (!$link) { echo "CONNECT_FAIL: " . mysqli_connect_error() . "\n"; exit; }
$ok = @mysqli_query($link, "CREATE DATABASE IF NOT EXISTS regsys_poster26 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
if (!$ok) { echo "CREATE_DB_FAIL: " . mysqli_error($link) . "\n"; exit; }
$ok = @mysqli_query($link, "CREATE TABLE IF NOT EXISTS regsys_poster26.users LIKE regsys_poster.users");
if (!$ok) { echo "CREATE_TABLE_FAIL: " . mysqli_error($link) . "\n"; exit; }
$chk = @mysqli_query($link, "SELECT COUNT(*) FROM regsys_poster26.users");
if ($chk) { $row = mysqli_fetch_row($chk); echo "OK users_count=" . intval($row[0]) . "\n"; } else { echo "CHECK_FAIL: " . mysqli_error($link) . "\n"; }
