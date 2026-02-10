<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../dbconnection.php';

function expect($cond, $msg) {
    if (!$cond) {
        echo "FAIL: " . $msg . PHP_EOL;
        exit(1);
    } else {
        echo "PASS: " . $msg . PHP_EOL;
    }
}

$uniq = 'CaseXyZ_' . mt_rand(1000,9999);
$email = 'search_test_' . mt_rand(1000,9999) . '@example.com';

$insert = mysqli_prepare($con, "INSERT INTO users (fname, email, category, password, contactno, coauth1name) VALUES (?, ?, 'TestCat', 'Pass123', '000', 'CoA')");
mysqli_stmt_bind_param($insert, 'ss', $uniq, $email);
mysqli_stmt_execute($insert);
$id = mysqli_insert_id($con);
expect($id > 0, 'Inserted test user');

$_GET['ajax'] = 1;
$_GET['search'] = $uniq;
ob_start();
include __DIR__ . '/../manage-users.php';
$html = ob_get_clean();
expect(strpos($html, '<mark class="search-highlight">') !== false, 'Highlights matching text');
expect(strpos($html, $uniq) !== false, 'Contains searched value');

$_GET['ajax'] = 1;
$_GET['search'] = strtolower($uniq);
ob_start();
include __DIR__ . '/../manage-users.php';
$html2 = ob_get_clean();
expect(strpos($html2, 'No results found') !== false, 'Case-sensitive matching yields no results for different case');

$del = mysqli_prepare($con, "DELETE FROM users WHERE id = ?");
mysqli_stmt_bind_param($del, 'i', $id);
mysqli_stmt_execute($del);
expect(mysqli_stmt_affected_rows($del) === 1, 'Cleaned up test user');

echo "OK: Search tests completed" . PHP_EOL;
