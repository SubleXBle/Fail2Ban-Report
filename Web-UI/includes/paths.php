<?php
// Use existing Session or start a new one
if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/auth.php';
}

// Config Pfad
$CONFIG_ROOT = "/opt/Fail2Ban-Report/Settings/";

// Basispfad
$ARCHIVE_ROOT = dirname(__DIR__) . "/archive/";

// Serverliste automatisch aus archive/ generieren
$SERVERS = [];
if (is_dir($ARCHIVE_ROOT)) {
    foreach (scandir($ARCHIVE_ROOT) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        if (is_dir($ARCHIVE_ROOT . $entry)) {
            // z. B. Key = Ordnername, Value = "Schönschreibweise"
            $SERVERS[$entry] = ucfirst($entry);
        }
    }
}

// Config einlesen
$configFile = $CONFIG_ROOT . 'fail2ban-report.config';
$config = parse_ini_file($configFile, true);

// Standardserver aus Config lesen
$configDefault = $config['Default Server']['defaultserver'] ?? null;

// Validierung Default: aus Config, sonst erster gefundener Server
if ($configDefault && array_key_exists($configDefault, $SERVERS)) {
    $DEFAULT_SERVER = $configDefault;
} else {
    $DEFAULT_SERVER = array_key_first($SERVERS);
}

// If choosen item -> dont forget
if (isset($_POST['server']) && array_key_exists($_POST['server'], $SERVERS)) {
    $_SESSION['active_server'] = $_POST['server'];
}

// active server (Session → Default)
$activeServer = (isset($_SESSION['active_server']) && array_key_exists($_SESSION['active_server'], $SERVERS))
    ? $_SESSION['active_server']
    : $DEFAULT_SERVER;

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
