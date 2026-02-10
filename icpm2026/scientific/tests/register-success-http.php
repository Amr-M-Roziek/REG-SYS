<?php
// Integration test: successful registration with abstract upload should 302 to welcome and create DB row
error_reporting(E_ALL);
$base = 'http://127.0.0.1:8000/icpm2026/poster26/index.php';
$file = dirname(__DIR__) . '/files/poster.pdf';
header('Content-Type: text/plain');
if (!is_file($file)) { echo "NO_FILE\n"; exit; }
$email = 'test+' . uniqid() . '@example.com';
$fields = [
  'signup' => 'Sign Up',
  'fname' => 'Flow Tester',
  'nationality' => 'Testland',
  'coauth1name' => '',
  'coauth1nationality' => '',
  'coauth1email' => '',
  'coauth2name' => '',
  'coauth2nationality' => '',
  'coauth2email' => '',
  'coauth3name' => '',
  'coauth3nationality' => '',
  'coauth3email' => '',
  'coauth4name' => '',
  'coauth4nationality' => '',
  'coauth4email' => '',
  'coauth5name' => '',
  'coauth5nationality' => '',
  'coauth5email' => '',
  'email' => $email,
  'profession' => 'Tester',
  'organization' => 'Local Org',
  'category' => 'Poster Competetion',
  'postertitle' => 'End-to-End Test Poster',
  'password' => 'pass1234',
  'contact' => '1234567890',
  'userip' => '127.0.0.1',
  'companyref' => '',
  'paypalref' => '',
  'abstract_file' => new CURLFile($file, 'application/pdf', 'poster.pdf'),
];
$jar = tempnam(sys_get_temp_dir(), 'cookiejar_');
$ch = curl_init($base);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
$resp = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$loc = curl_getinfo($ch, CURLINFO_REDIRECT_URL);
curl_close($ch);
echo "CODE=$code\n";
echo "LOCATION=" . ($loc ?: '') . "\n";
echo "EMAIL=$email\n";
if ($code == 302 && strpos($loc, 'welcome.php') !== false) {
  $ok = true;
} else {
  $ok = false;
}
require_once('../dbconnection.php');
$stmt = mysqli_prepare($con, "SELECT id,email,fname FROM users WHERE email=? LIMIT 1");
if ($stmt) {
  mysqli_stmt_bind_param($stmt,'s',$email);
  mysqli_stmt_execute($stmt);
  $ret = mysqli_stmt_get_result($stmt);
  $row = mysqli_fetch_assoc($ret);
} else {
  $row = false;
}
if ($ok && $row && isset($row['id'])) {
  echo "RESULT=PASS id=" . $row['id'] . "\n";
} else {
  echo "RESULT=FAIL\n";
}
