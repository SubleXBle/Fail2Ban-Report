<?php
// includes/actions/action_ban-ip.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405); // Method Not Allowed
  echo "Fehler: Nur POST erlaubt.";
  exit;
}

$ip = $_POST['ip'] ?? null;

if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
  http_response_code(400); // Bad Request
  echo "Ungültige oder fehlende IP.";
  exit;
}

// Dummy-Antwort
echo "[BAN] IP $ip wurde erfolgreich verarbeitet.";
