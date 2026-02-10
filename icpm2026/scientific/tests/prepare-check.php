<?php
require_once('../dbconnection.php');
header('Content-Type: text/plain');
$q = "insert into users(fname,nationality,coauth1name,coauth1nationality,coauth1email,coauth2name,coauth2nationality,coauth2email,coauth3name,coauth3nationality,coauth3email,coauth4name,coauth4nationality,coauth4email,coauth5name,coauth5nationality,coauth5email,email,profession,organization,category,password,contactno,userip,companyref,paypalref) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
$stmt = mysqli_prepare($con, $q);
if ($stmt) {
  echo "PREPARED\n";
} else {
  echo "FAIL: " . mysqli_errno($con) . " " . mysqli_error($con) . "\n";
}
