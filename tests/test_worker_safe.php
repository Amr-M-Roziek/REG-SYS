<?php
// test_worker_safe.php
// Usage: php test_worker_safe.php <path_to_ajax_handler.php>

// Suppress all error output to stdout to keep JSON clean
error_reporting(0);
ini_set('display_errors', '0');

if ($argc < 2) {
    echo json_encode(['status' => 'error', 'message' => 'No file provided']);
    exit(1);
}

$targetFile = $argv[1];
if (!file_exists($targetFile)) {
    echo json_encode(['status' => 'error', 'message' => 'File not found: ' . $targetFile]);
    exit(1);
}

$code = file_get_contents($targetFile);
$tokens = token_get_all($code);

$functions = [];
$currentFunction = null;
$braceLevel = 0;
$inFunction = false;
$buffer = '';
$foundBraceStart = false;

for ($i = 0; $i < count($tokens); $i++) {
    $token = $tokens[$i];
    
    if (is_array($token)) {
        $text = $token[1];
        $id = $token[0];
    } else {
        $text = $token;
        $id = null;
    }
    
    // Detect function start
    if (!$inFunction && $id === T_FUNCTION) {
        // Look ahead for function name
        $j = $i + 1;
        while (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
            $j++;
        }
        if (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
            $funcName = $tokens[$j][1];
            if ($funcName === 'getHtmlEmail' || $funcName === 'generateMimeMessage') {
                $currentFunction = $funcName;
                $inFunction = true;
                $braceLevel = 0;
                $foundBraceStart = false;
                $buffer = 'function ' . $funcName;
                $i = $j; // Advance loop to function name
                continue;
            }
        }
    }
    
    if ($inFunction) {
        $buffer .= $text;
        
        // Count braces
        // Handle structural braces and variable interpolation braces
        if ($text === '{' || $id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
            $foundBraceStart = true;
            $braceLevel++;
        } elseif ($text === '}') {
            $braceLevel--;
            if ($foundBraceStart && $braceLevel === 0) {
                // End of function
                $functions[$currentFunction] = $buffer;
                $inFunction = false;
                $currentFunction = null;
                $buffer = '';
            }
        }
    }
}

if (!isset($functions['getHtmlEmail']) || !isset($functions['generateMimeMessage'])) {
    echo json_encode(['status' => 'error', 'message' => 'Functions not found in ' . basename($targetFile)]);
    exit(1);
}

// Check if function already exists (unlikely in CLI worker, but good practice)
if (!function_exists('getHtmlEmail')) {
    try {
        eval($functions['getHtmlEmail']);
    } catch (ParseError $e) {
        echo json_encode(['status' => 'error', 'message' => 'Parse Error in getHtmlEmail: ' . $e->getMessage()]);
        exit(1);
    }
}
if (!function_exists('generateMimeMessage')) {
    try {
        eval($functions['generateMimeMessage']);
    } catch (ParseError $e) {
        echo json_encode(['status' => 'error', 'message' => 'Parse Error in generateMimeMessage: ' . $e->getMessage()]);
        exit(1);
    }
}

// Prepare dummy user data
$user = [
    'id' => 1,
    'fname' => 'Test',
    'lname' => 'User',
    'email' => 'test@example.com',
    'category' => 'Speaker'
];

$attachmentPath = __FILE__; // Use this file as a dummy attachment
$attachmentName = 'test_attachment.txt';
$extraAttachments = [];

// Generate MIME
try {
    // We need to suppress warnings from file_get_contents inside the function if files don't exist
    // But since we can't easily modify the eval'd code, we rely on global error suppression
    
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
