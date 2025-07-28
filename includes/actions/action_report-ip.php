<?php
// includes/actions/action_report-ip.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo "Fehler: Nur POST erlaubt.";
  exit;
}

$ip = $_POST['ip'] ?? null;

if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) {
  http_response_code(400);
  echo "Ungültige oder fehlende IP.";
  exit;
}

// Dummy-Antwort
echo "[REPORT] IP $ip wurde erfolgreich verarbeitet.";
