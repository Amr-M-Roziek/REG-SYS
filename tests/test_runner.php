<?php
// test_runner.php

$files = [
    'c:/xampp/htdocs/reg-sys.com/icpm2026/admin/ajax_handler.php',
    'c:/xampp/htdocs/reg-sys.com/icpm2026/participant/admin/ajax_handler.php',
    'c:/xampp/htdocs/reg-sys.com/icpm2026/scientific/admin/ajax_handler.php',
    'c:/xampp/htdocs/reg-sys.com/icpm2026/poster26/admin/ajax_handler.php'
];

$workerScript = __DIR__ . '/test_worker_safe.php';
$report = [];

echo "Starting Regression Tests for Email Content...\n";
echo "================================================\n";

foreach ($files as $file) {
    // Show relative path for clarity
    $relativePath = str_replace('c:/xampp/htdocs/reg-sys.com/icpm2026/', '', $file);
    echo "Testing: " . $relativePath . " ... ";
    
    $phpPath = 'c:/xampp/php/php.exe';
    $cmd = "\"$phpPath\" \"$workerScript\" \"$file\"";
    $output = shell_exec($cmd);
    $data = json_decode($output, true);
    
    if (!$data) {
        echo "FAILED (JSON Decode Error)\n";
        echo "Raw Output: $output\n";
        $report[] = ['file' => $file, 'status' => 'FAILED', 'reason' => 'JSON Decode Error'];
        continue;
    }
    
    if ($data['status'] === 'error') {
        echo "FAILED ({$data['message']})\n";
        $report[] = ['file' => $file, 'status' => 'FAILED', 'reason' => $data['message']];
        continue;
    }
    
    // Analyze Content
    $body = $data['body'];
    $errors = [];
    
    // 1. Check for leading quotes (>)
    $lines = explode("\n", $body);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        // Look for lines starting with > but ignore if it's inside HTML tag definition (rare but possible)
        // Usually quoted text appears as > Some text
        // We need to be careful not to match HTML tags like <div>
        if (preg_match('/^>[^<]/', $trimmed)) {
            $errors[] = "Found quoted line: $trimmed";
            break;
        }
    }
    
    // 2. Check for blockquote tags
    if (stripos($body, '<blockquote') !== false) {
        $errors[] = "Found <blockquote> tag";
    }
    
    // 3. Check for CID images
    if (strpos($body, 'cid:icpm_logo') === false) {
        $errors[] = "Missing cid:icpm_logo";
    }
    if (strpos($body, 'cid:appstore') === false) {
        $errors[] = "Missing cid:appstore";
    }
    
    // 4. Check MIME structure
    if (strpos($body, 'Content-Type: multipart/related') === false) {
        $errors[] = "Missing multipart/related Content-Type";
    }
    if (strpos($body, 'Content-Type: text/html') === false) {
        $errors[] = "Missing text/html Content-Type";
    }
    
    if (empty($errors)) {
        echo "PASSED\n";
        $report[] = ['file' => $file, 'status' => 'PASSED'];
    } else {
        echo "FAILED\n";
        foreach ($errors as $err) {
            echo "  - $err\n";
        }
        $report[] = ['file' => $file, 'status' => 'FAILED', 'reason' => implode(", ", $errors)];
    }
}

echo "\nSummary Report\n";
echo "================================================\n";
$allPassed = true;
foreach ($report as $item) {
    echo basename(dirname($item['file'])) . ": " . $item['status'] . ($item['status'] == 'FAILED' ? " (" . $item['reason'] . ")" : "") . "\n";
    if ($item['status'] === 'FAILED') $allPassed = false;
}

if ($allPassed) {
    echo "\nVERDICT: All tests passed. No quoted text detected. MIME structure correct.\n";
    exit(0);
} else {
    echo "\nVERDICT: Some tests failed.\n";
    exit(1);
}
?>
