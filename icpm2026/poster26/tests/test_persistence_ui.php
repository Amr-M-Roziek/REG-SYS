<?php
// Tests for Persistence and UI Enhancements

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

// 1. Check Buttons
runTest("Refresh Button HTML exists", strpos($content, 'id="refresh-table"') !== false);
runTest("Refresh Button class", strpos($content, 'btn-info') !== false);
runTest("Reset Button HTML exists", strpos($content, 'id="reset-settings"') !== false);
runTest("Reset Button class", strpos($content, 'btn-warning') !== false);

// 2. Check Cookie Helpers
runTest("setCookie function", strpos($content, 'function setCookie(name, value, days)') !== false);
runTest("getCookie function", strpos($content, 'function getCookie(name)') !== false);
runTest("eraseCookie function", strpos($content, 'function eraseCookie(name)') !== false);

// 3. Check Cookie Logic
runTest("Cookie usage in init", strpos($content, "getCookie('col_vis_' + target)") !== false);
runTest("Cookie usage in change", strpos($content, "setCookie('col_vis_' + target") !== false);
runTest("Secure cookie flags", strpos($content, "SameSite=Lax") !== false);

// 4. Check Refresh Logic
runTest("window.fetchRows exposed", strpos($content, "window.fetchRows = function") !== false);
runTest("Refresh button click handler", strpos($content, "$('#refresh-table').click") !== false);
runTest("Refresh button calls fetchRows", strpos($content, "window.fetchRows(q, function()") !== false);

// 5. Check Reset Logic
runTest("Reset button click handler", strpos($content, "$('#reset-settings').click") !== false);
runTest("Reset calls eraseCookie", strpos($content, "eraseCookie('col_vis_' + target)") !== false);

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
