<?php
$file = __DIR__ . '/../main-dashboard.php';
if (!file_exists($file)) {
    echo "FAIL: main-dashboard.php not found\n";
    exit(1);
}
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
runTest('Workshop connection function defined', strpos($content, "if (\$module === 'workshop'") !== false);
runTest('Workshop module option in select', strpos($content, 'option value="workshop"') !== false);
runTest('Workshop latest table heading present', strpos($content, 'Workshop Registrations (Latest 20)') !== false);
runTest('Workshop search branch exists', strpos($content, "if (\$workshopCon && (\$filterModule === '' || \$filterModule === 'workshop'))") !== false);
foreach ($tests as $line) {
    echo $line . "\n";
}
if ($failed > 0) {
    echo "Tests failed: $failed\n";
    exit(1);
}
echo "All tests passed\n";

