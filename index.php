<?php include(__DIR__ . '/includes/list-files.php'); ?>
<?php // index.php Version 0.2.1 ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Fail2Ban Report</title>
  <link rel="stylesheet" href="/assets/css/style.css" />
  <script>
    const availableFiles = <?php echo $filesJson; ?>;
  </script>
  <script src="/assets/js/jsonreader.js" defer></script>
</head>
<body>
  <h1>Fail2Ban Report</h1>
  <h2>Let's catch the bad guys!</h2>

  <label for="dateSelect">Select Date:</label>
  <select id="dateSelect"></select>

  <label for="actionFilter">Action:</label>
  <select id="actionFilter">
    <option value="">All</option>
    <option value="Ban">Ban</option>
    <option value="Unban">Unban</option>
  </select>

  <label for="jailFilter">Jail:</label>
  <select id="jailFilter"></select>

  <label for="ipFilter">IP contains:</label>
  <input type="text" id="ipFilter" placeholder="e.g. 192.168" />

  <table id="resultTable">
    <thead>
      <tr>
        <th>Time</th>
        <th>Action</th>
        <th>IP</th>
        <th>Jail</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>

</body>
</html>
