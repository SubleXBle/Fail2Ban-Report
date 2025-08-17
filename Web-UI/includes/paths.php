<?php
//session_start();

// Use existing Session or start a new one
if (session_status() === PHP_SESSION_NONE) {
//    die("Session not started. Include auth.php first.");
//    session_start();
    require_once __DIR__ . '/auth.php';
}

// Config Pfad
$CONFIG_ROOT = "/opt/Fail2Ban-Report/Settings/";

// Basispfad
$ARCHIVE_ROOT = __DIR__ . "/../archive/";

// Liste verfügbarer Server
$SERVERS = [
    "swsrv"  => "Webserver",
    "sasrv"  => "Appserver",
    "tests"  => "Testing"
];

// Default Server => Will have to be set in config to work for others
$DEFAULT_SERVER = "swsrv";

// If choosen item -> dont forget
if (isset($_POST['server']) && array_key_exists($_POST['server'], $SERVERS)) {
    $_SESSION['active_server'] = $_POST['server'];
}

// active server (Session → Default)
$activeServer = $_SESSION['active_server'] ?? $DEFAULT_SERVER;

/**
 * Pfade für den aktuell aktiven Server zurückgeben
 */
function getPaths($server) {
    global $ARCHIVE_ROOT;
    $base = $ARCHIVE_ROOT . $server . "/";
    return [
        "fail2ban"   => $base . "fail2ban/",
        "blocklists" => $base . "blocklists/",
        "ufw"        => $base . "ufw/",
    ];
}

// Globale PATHS-Variable setzen
$PATHS = getPaths($activeServer);
$PATHS['config'] = $CONFIG_ROOT;
