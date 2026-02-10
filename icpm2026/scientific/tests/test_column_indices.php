<?php
// Tests/test_column_indices.php
// This script parses admin/manage-users.php to verify table column indices and CSS rules.

$file = __DIR__ . '/../admin/manage-users.php';
if (!file_exists($file)) {
    die("File not found: $file\n");
}

$content = file_get_contents($file);

// 1. Extract Table Headers to map indices
preg_match('/<thead>(.*?)<\/thead>/s', $content, $matches);
if (empty($matches[1])) {
    die("Could not find table headers.\n");
}

$headerBlock = $matches[1];
// Simple regex to find <th> tags
preg_match_all('/<th.*?>(.*?)<\/th>/s', $headerBlock, $thMatches);
$headers = $thMatches[1];

echo "Found " . count($headers) . " columns.\n";
$map = [];
foreach ($headers as $i => $h) {
    $index = $i + 1; // nth-child is 1-based
    $cleanH = trim(strip_tags($h));
    $map[$index] = $cleanH;
    echo "Index $index: $cleanH\n";
}

// 2. Extract CSS rules for visibility
echo "\nChecking CSS rules...\n";

// Helper to check if an index is hidden by a class
function checkCssRule($content, $class, $index) {
    // Look for pattern: #users-table.class ... nth-child(index) ... display: none !important
    // This is a simplified check, assuming the format used in the file
    $pattern = '/#users-table\.' . preg_quote($class, '/') . '.*?nth-child\(' . $index . '\).*?display:\s*none\s*!important/s';
    return preg_match($pattern, $content);
}

$checks = [
    'hide-coauth1' => [6, 7],
    'hide-coauth2' => [8, 9],
    'hide-coauth3' => [10, 11],
    'hide-coauth4' => [12, 13],
    'hide-coauth5' => [14, 15],
    'hide-emails'  => [16, 17, 18, 19, 20, 24, 25] // We expect these to be hidden
];

$errors = [];
foreach ($checks as $class => $indices) {
    foreach ($indices as $idx) {
        if (!checkCssRule($content, $class, $idx)) {
            $colName = isset($map[$idx]) ? $map[$idx] : "Unknown";
            $errors[] = "Class '$class' does NOT hide column $idx ($colName).";
        } else {
            // echo "OK: Class '$class' hides column $idx.\n";
        }
    }
}

if (!empty($errors)) {
    echo "\nErrors Found:\n";
    foreach ($errors as $e) {
        echo "- $e\n";
    }
    exit(1);
} else {
    echo "\nAll CSS rules match expected indices.\n";
    exit(0);
}
?>
