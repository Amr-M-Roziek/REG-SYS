<?php
// test_worker.php
// Usage: php test_worker.php <path_to_ajax_handler.php>

if ($argc < 2) {
    echo json_encode(['status' => 'error', 'message' => 'No file provided']);
    exit(1);
}

$targetFile = $argv[1];

if (!file_exists($targetFile)) {
    echo json_encode(['status' => 'error', 'message' => 'File not found: ' . $targetFile]);
    exit(1);
}

// Mock Session
session_start();
$_SESSION['id'] = 123;
$_SESSION['role'] = 'admin';

// Mock POST to prevent execution of actions
$_POST['action'] = 'TEST_MODE_NO_ACTION';

// Change directory so relative includes work
$dir = dirname($targetFile);
chdir($dir);

// Capture output buffer to discard any whitespace/garbage output by the included file
ob_start();
try {
    include basename($targetFile);
} catch (Exception $e) {
    // Ignore exceptions during include (e.g. DB connection)
}
ob_end_clean();

// Check if function exists
if (!function_exists('generateMimeMessage')) {
    echo json_encode(['status' => 'error', 'message' => 'generateMimeMessage function not found in ' . $targetFile]);
    exit(1);
}

// Prepare dummy user data
$user = [
    'id' => 1,
    'fname' => 'Test',
    'lname' => 'User',
    'email' => 'test@example.com',
    'category' => 'Speaker'
];

// Test parameters
$attachmentPath = __FILE__; // Use this file as a dummy attachment
$attachmentName = 'test_attachment.txt';
$extraAttachments = [];

// Generate MIME
try {
    $result = generateMimeMessage($user, $attachmentPath, $attachmentName, $extraAttachments);
    
    echo json_encode([
        'status' => 'success',
        'file' => $targetFile,
        'headers' => $result['headers'],
        'body' => $result['body']
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>