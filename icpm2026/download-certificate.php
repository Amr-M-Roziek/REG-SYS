<?php
$base = '/icpm2026/participant/download-certificate.php';
$query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
$location = $base . ($query !== '' ? '?' . $query : '');
header('Location: ' . $location, true, 302);
exit;

