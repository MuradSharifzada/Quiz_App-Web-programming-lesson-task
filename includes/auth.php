<?php
// includes/auth.php

// Start the session on every secure page
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if a user is logged in and redirects them to the login page if not.
 * NOTE: Assumes this file is included from a subdirectory (e.g., admin/, teacher/, student/).
 */
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        // Redirection path is relative to the calling script (e.g., ../login.php from admin/index.php)
        header("Location: ../login.php"); 
        exit;
    }
}

/**
 * Checks if the logged-in user has the required role.
 * * @param string $required_role The role name ('admin', 'teacher', 'student').
 */
function check_role($required_role) {
    // This requires $pdo to be defined in the global scope of the calling script.
    global $pdo; 
    
    check_login(); // Ensure user is logged in first

    $current_role_id = $_SESSION['role_id'] ?? null;
    
    // FIX: Corrected typo in function call from the last error (get_role_id_by_by_name)
    $target_role_id = get_role_id_by_name($pdo, $required_role); 

    // If the current user's role ID doesn't match the required role ID
    if ($current_role_id != $target_role_id) {
        // Fetch the current role name for accurate redirection
        $current_role_name = get_role_name_by_id($pdo, $current_role_id);
        
        // Redirect the user to their correct dashboard
        switch ($current_role_name) {
            case 'admin':
                header("Location: ../admin/index.php");
                break;
            case 'teacher':
                header("Location: ../teacher/index.php");
                break;
            case 'student':
                header("Location: ../student/index.php");
                break;
            default:
                // Fallback: log them out and send to login if role is unknown
                session_destroy();
                header("Location: ../login.php");
                break;
        }
        exit;
    }
}
?>