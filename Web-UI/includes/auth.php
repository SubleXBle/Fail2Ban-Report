<?php
// Start session (with secure cookie flags)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,          // Session ends when the browser is closed
        'path' => '/',
        'httponly' => true,       // No access via JavaScript
        'secure' => true,         // HTTPS only
        'samesite' => 'Strict'    // No cross-site requests allowed
    ]);
    session_start();
}

// Timeout check immediately after session start
$SESSION_TIMEOUT = 1800; // 30 Minutes
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    die("Session expired. Please log in again.");
}
$_SESSION['last_activity'] = time();

// set Standard Role
if (!isset($_SESSION['user_role'])) {
    $_SESSION['user_role'] = 'viewer';
}

// Load User-File
$USER_FILE= "/opt/Fail2Ban-Report/Settings/users.json";
$USERS = json_decode(file_get_contents($USER_FILE), true) ?: [];

// Logout
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']); // Zurück zur Login-Seite
    exit;
}

// sent Loginform?
if (isset($_POST['login_user']) && isset($_POST['login_pass'])) {
    $user = $_POST['login_user'];
    $pass = $_POST['login_pass'];
    $loggedIn = false;

    foreach ($USERS as $u) {
        if ($u['username'] === $user && password_verify($pass, $u['password'])) {
            // Login success -> Hold Session
            session_regenerate_id(true);
            $_SESSION['user_role'] = $u['role'];
            $_SESSION['username']  = $u['username'];
            $loggedIn = true;
            break;
        }
    }

    if (!$loggedIn) {
        // trigger error on failed login (could take this to fail2ban)
        error_log("Failed login for $user from " . $_SERVER['REMOTE_ADDR']);
        die("Login failed");
    }
}

// Admin-Check
function is_admin() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}

// Session Debug
/*
function debug_session() {
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}
*/
?>
