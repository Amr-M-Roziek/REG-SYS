<?php
// Centralized session configuration to prevent conflicts and ensure persistence
if (session_status() === PHP_SESSION_NONE) {
    // Set a unique session name for this application to avoid conflicts with other local apps
    session_name('SCIENTIFIC_ADMIN_SESSION');
    
    // Ensure cookies are accessible across the domain path
    // We use default save path to avoid permission issues
    // ini_set('session.save_path', sys_get_temp_dir()); // Use default if possible
    
    // Cookie settings
    session_set_cookie_params([
        'lifetime' => 0, // Session cookie
        'path' => '/',
        'domain' => '', // Current domain
        'secure' => false, // Set to true if HTTPS is enforced, false for local/http compatibility
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    session_start();
}

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    if (function_exists('random_bytes')) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
    }
}
