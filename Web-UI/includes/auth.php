<?php
// Start session (with secure cookie flags)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'secure' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

// Timeout / Lifetime / Regeneration Settings
$SESSION_TIMEOUT        = 1800; // 30 Minutes inactivity
$SESSION_REGEN_INTERVAL = 900;  // 15 Minutes for ID regeneration
$SESSION_MAX_LIFETIME   = 7200; // 2 Hours absolute lifetime

// Check inactivity timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    die("Session expired due to inactivity. Please log in again.");
}
$_SESSION['last_activity'] = time();

// Check absolute lifetime
if (!isset($_SESSION['created_at'])) {
    $_SESSION['created_at'] = time();
} elseif (time() - $_SESSION['created_at'] > $SESSION_MAX_LIFETIME) {
    session_unset();
    session_destroy();
    die("Session expired due to maximum lifetime. Please log in again.");
}

// Session ID regeneration
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > $SESSION_REGEN_INTERVAL) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Client binding (User-Agent + partial IP)
$lastDot = strrpos($_SERVER['REMOTE_ADDR'], '.');
$ipPrefix = ($lastDot !== false) ? substr($_SERVER['REMOTE_ADDR'], 0, $lastDot) : $_SERVER['REMOTE_ADDR'];
$clientFingerprint = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $ipPrefix);

if (!isset($_SESSION['client_fingerprint'])) {
    $_SESSION['client_fingerprint'] = $clientFingerprint;
} elseif ($_SESSION['client_fingerprint'] !== $clientFingerprint) {
    session_unset();
    session_destroy();
    die("Session validation failed. Please log in again.");
}

// Check current script path to allow accessing the main login landing page
$current_script = basename($_SERVER['SCRIPT_NAME']);
$is_login_path = ($current_script === 'index.php');

// Central Authentication Gate (Fix against critical Authentication Bypass / Anonymous Read Surface)
if (!isset($_SESSION['username']) || !isset($_SESSION['user_role'])) {
    // If a request hits a background read/write endpoint without an active session, drop it immediately
    if (!$is_login_path) {
        http_response_code(403);
        session_unset();
        if (session_id()) {
            session_destroy();
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required. Access denied.',
            'type'    => 'error'
        ]);
        exit;
    }
    
    // Assign a highly restricted temporary guest role for the initial unauthenticated landing interface
    if (!isset($_SESSION['user_role'])) {
        $_SESSION['user_role'] = 'unauthenticated_guest';
    }
}

// Load User-File
$USER_FILE= "/opt/Fail2Ban-Report/Settings/users.json";
$USERS = json_decode(file_get_contents($USER_FILE), true) ?: [];

// Logout
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
    header("Location: " . $_SERVER['PHP_SELF']);
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
            $_SESSION['user_role']         = $u['role'];
            $_SESSION['username']          = $u['username'];
            $_SESSION['created_at']        = time();
            $_SESSION['last_regeneration'] = time();
            
            $lastDotInner = strrpos($_SERVER['REMOTE_ADDR'], '.');
            $ipPrefixInner = ($lastDotInner !== false) ? substr($_SERVER['REMOTE_ADDR'], 0, $lastDotInner) : $_SERVER['REMOTE_ADDR'];
            $_SESSION['client_fingerprint'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $ipPrefixInner);
            
            $loggedIn = true;
            break;
        }
    }

    if (!$loggedIn) {
        error_log("Failed login for $user from " . $_SERVER['REMOTE_ADDR']);
        die("Login failed");
    }
}

// Admin-Check
function is_admin() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}
?>
