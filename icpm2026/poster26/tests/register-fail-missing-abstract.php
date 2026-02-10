<?php
// Integration test: registration without abstract should fail with validation message
error_reporting(E_ALL);
$base = 'http://127.0.0.1:8000/icpm2026/poster26/index.php';
header('Content-Type: text/plain');
$email = 'fail+' . uniqid() . '@example.com';
$fields = [
  'signup' => 'Sign Up',
  'fname' => 'No File Tester',
  'nationality' => 'Testland',
  'coauth1name' => '',
  'coauth1nationality' => '',
  'email' => $email,
  'profession' => 'Tester',
  'organization' => 'Local Org',
  'category' => 'Poster Competetion',
  'postertitle' => 'Missing File',
  'password' => 'pass1234',
  'contact' => '1234567890',
  'userip' => '127.0.0.1',
  'companyref' => '',
  'paypalref' => '',
];
$ch = curl_init($base);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
$resp = curl_exec($ch);
curl_close($ch);
if (strpos($resp, 'Please upload abstract file') !== false) {
  echo "RESULT=PASS\n";
} else {
  echo "RESULT=FAIL\n";
}
