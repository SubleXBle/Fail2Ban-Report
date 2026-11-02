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
$SESSION_TIMEOUT        = 1800;
$SESSION_REGEN_INTERVAL = 900;
$SESSION_MAX_LIFETIME   = 7200;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $SESSION_TIMEOUT)) {
    session_unset(); session_destroy();
    die("Session expired. Please log in again.");
}
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['created_at'])) {
    $_SESSION['created_at'] = time();
} elseif (time() - $_SESSION['created_at'] > $SESSION_MAX_LIFETIME) {
    session_unset(); session_destroy();
    die("Session expired. Please log in again.");
}

if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > $SESSION_REGEN_INTERVAL) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Client binding (User-Agent + partial IP)
$client_ip = $_SERVER['REMOTE_ADDR'];
if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $client_ip = trim($ip_list[0]);
}
$lastDot = strrpos($client_ip, '.');
$ipPrefix = ($lastDot !== false) ? substr($client_ip, 0, $lastDot) : $client_ip;
$clientFingerprint = hash('sha256', $_SERVER['HTTP_USER_AGENT'] . $ipPrefix);

if (!isset($_SESSION['client_fingerprint'])) {
    $_SESSION['client_fingerprint'] = $clientFingerprint;
} elseif ($_SESSION['client_fingerprint'] !== $clientFingerprint) {
    session_unset(); session_destroy();
    die("Session validation failed. Please log in again.");
}

// Load User-File early
$USER_FILE = "/opt/Fail2Ban-Report/Settings/users.json";
$USERS = json_decode(file_get_contents($USER_FILE), true) ?: [];

// === 1. PROCESS LOGIN FORM POST (Runs first) ===
if (isset($_POST['login_user']) && isset($_POST['login_pass'])) {
    $user = $_POST['login_user'];
    $pass = $_POST['login_pass'];
    $loggedIn = false;

    foreach ($USERS as $u) {
        if ($u['username'] === $user && password_verify($pass, $u['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_role']          = $u['role'];
            $_SESSION['username']           = $u['username'];
            $_SESSION['created_at']         = time();
            $_SESSION['last_regeneration']  = time();
            $_SESSION['client_fingerprint'] = $clientFingerprint;
            $loggedIn = true;
            break;
        }
    }

    if (!$loggedIn) {
        error_log("Failed login for $user from " . $client_ip);
        die("Login failed");
    }
}

// === 2. CENTRAL AUTHENTICATION GATE ===
$current_script = basename($_SERVER['SCRIPT_NAME']);
$is_login_path = ($current_script === 'index.php');

if (!isset($_SESSION['username']) || !isset($_SESSION['user_role'])) {
    if (!$is_login_path) {
        http_response_code(403);
        session_unset();
        if (session_id()) { session_destroy(); }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'Authentication required.']);
        exit;
    }
    
    if (!isset($_SESSION['user_role'])) {
        $_SESSION['user_role'] = 'unauthenticated_guest';
    }
}

// Logout
if (isset($_POST['logout'])) {
    session_unset(); session_destroy();
    setcookie(session_name(), '', time() - 3600, '/');
    header("Location: index.php");
    exit;
}

function is_admin() {
    return (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin');
}
?>
