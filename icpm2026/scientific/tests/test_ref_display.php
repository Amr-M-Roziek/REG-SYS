<?php
// Tests for Table Display Enhancements

$file = __DIR__ . '/../admin/manage-users.php';
$content = file_get_contents($file);

$tests = [];
$failed = 0;

function runTest($name, $condition) {
    global $tests, $failed;
    if ($condition) {
        $tests[] = "[PASS] $name";
    } else {
        $tests[] = "[FAIL] $name";
        $failed++;
    }
}

// 1. Check Ref Number CSS
runTest("Ref Number CSS exists", strpos($content, '#users-table th:nth-child(2), #users-table td:nth-child(2)') !== false);
runTest("Ref Number white-space nowrap", strpos($content, 'white-space: nowrap !important') !== false);
runTest("Ref Number overflow visible", strpos($content, 'overflow: visible !important') !== false);
runTest("Ref Number no truncation", strpos($content, 'text-overflow: clip !important') !== false);

// 2. Check Loading Indicator HTML
runTest("Loading indicator HTML exists", strpos($content, '<div id="loading-indicator">') !== false);

// 3. Check Loading Indicator CSS
runTest("Loading indicator CSS exists", strpos($content, '#loading-indicator {') !== false);

// 4. Check JS Logic for Loading
runTest("JS shows loading indicator", strpos($content, "$('#loading-indicator').fadeIn(100);") !== false);
runTest("JS uses setTimeout", strpos($content, "setTimeout(function() {") !== false);
runTest("JS applies visibility", strpos($content, "applyColumnVisibility();") !== false);
runTest("JS hides loading indicator", strpos($content, "$('#loading-indicator').fadeOut(200);") !== false);

// 5. Check Default Visibility CSS (Hidden Columns)
runTest("Default hidden columns CSS exists", strpos($content, '#users-table th:nth-child(6)') !== false && strpos($content, 'display: none;') !== false);

// Output Results
echo "Running " . count($tests) . " tests...\n";
foreach ($tests as $test) {
    echo "$test\n";
}

if ($failed > 0) {
    echo "\n$failed tests failed.\n";
    exit(1);
} else {
    echo "\nAll tests passed.\n";
    exit(0);
}
