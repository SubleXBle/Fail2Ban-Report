<?php
// includes/unblock-ip.php

function unblockIp($ip) {
  if (!filter_var($ip, FILTER_VALIDATE_IP)) {
    return ['success' => false, 'message' => "Invalid IP format: $ip"];
  }

  // Remove iptables rule
  $cmd = escapeshellcmd("iptables -D INPUT -s $ip -j DROP");
  exec($cmd, $output, $exitCode);

  if ($exitCode !== 0) {
    return ['success' => false, 'message' => "Failed to remove iptables rule for IP $ip"];
  }

  // Remove from blocklist.json
  $jsonFile = __DIR__ . '/archive/blocklist.json';
  if (!file_exists($jsonFile)) {
    return ['success' => true, 'message' => "IP $ip was not in blocklist."];
  }

  $data = json_decode(file_get_contents($jsonFile), true);
  if (!is_array($data)) {
    $data = [];
  }

  $newData = array_filter($data, fn($item) => $item['ip'] !== $ip);

  file_put_contents($jsonFile, json_encode(array_values($newData), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

  return ['success' => true, 'message' => "IP $ip successfully unblocked and removed from blocklist."];
}
