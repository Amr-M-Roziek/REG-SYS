<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing logging_helper.php...\n";
try {
    require_once('logging_helper.php');
    echo "logging_helper.php included successfully.\n";
    
    if (function_exists('log_error')) {
        echo "log_error function exists.\n";
        log_error('test@example.com', 'TEST', 'Test message');
        echo "log_error called successfully.\n";
    } else {
        echo "log_error function DOES NOT exist.\n";
    }
} catch (Throwable $e) {
    echo "Error including logging_helper.php: " . $e->getMessage() . "\n";
}

echo "\nTesting session_manager.php...\n";
try {
    require_once('session_manager.php');
    echo "session_manager.php included successfully.\n";
} catch (Throwable $e) {
    echo "Error including session_manager.php: " . $e->getMessage() . "\n";
}
?>