<?php

/**
 * Selects the recipient email based on the user data and the requested role.
 * 
 * @param array $user The user data array (must contain email, coauthXemail keys).
 * @param string $role The role to send to ('main', 'co1', 'co2', etc.).
 * @return array Result containing 'email' (string|null) and 'error' (string|null).
 */
function get_recipient_email($user, $role) {
    $email = null;
    $error = null;

    switch ($role) {
        case 'main':
            $email = isset($user['email']) ? $user['email'] : null;
            if (empty($email)) {
                $error = "Main author email is missing.";
            }
            break;
        case 'co1':
            $email = isset($user['coauth1email']) ? $user['coauth1email'] : null;
            if (empty($email)) {
                $error = "Co-Author 1 email is missing.";
            }
            break;
        case 'co2':
            $email = isset($user['coauth2email']) ? $user['coauth2email'] : null;
            if (empty($email)) {
                $error = "Co-Author 2 email is missing.";
            }
            break;
        case 'co3':
            $email = isset($user['coauth3email']) ? $user['coauth3email'] : null;
            if (empty($email)) {
                $error = "Co-Author 3 email is missing.";
            }
            break;
        case 'co4':
            $email = isset($user['coauth4email']) ? $user['coauth4email'] : null;
            if (empty($email)) {
                $error = "Co-Author 4 email is missing.";
            }
            break;
        case 'co5':
            $email = isset($user['coauth5email']) ? $user['coauth5email'] : null;
            if (empty($email)) {
                $error = "Co-Author 5 email is missing.";
            }
            break;
        default:
            $error = "Invalid role specified: $role";
            break;
    }

    // Only validate format if we found something
    if (!empty($email)) {
        // Trim whitespace just in case
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format: $email";
            $email = null;
        }
    } else {
        // Ensure email is null if empty (it might be empty string)
        $email = null;
    }

    return ['email' => $email, 'error' => $error];
}
?>
