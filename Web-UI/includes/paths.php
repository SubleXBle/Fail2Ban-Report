<?php
session_start();

// Basepath
$ARCHIVE_ROOT = __DIR__ . "/../archive/";

// List of available Servers (has to be edited by hand for now)
$SERVERS = [
    "swsrv"  => "Webserver",
    "tests"  => "Testing"
];

// Standardserver
$DEFAULT_SERVER = "swsrv";

// if selected item in serverdropdown → dont forget it
if (isset($_POST['server']) && array_key_exists($_POST['server'], $SERVERS)) {
    $_SESSION['active_server'] = $_POST['server'];
}

// set active Server (Session → Default)
$activeServer = $_SESSION['active_server'] ?? $DEFAULT_SERVER;

/**
 * Path for active selected Server
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

// set Global PATHS-Variable
$PATHS = getPaths($activeServer);
