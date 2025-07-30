<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Fail2Ban Report</title>
  <link rel="stylesheet" href="assets/css/style.css" />
  <script>
    const availableFiles = <?php echo $filesJson; ?>;
  </script>
  <script src="assets/js/jsonreader.js" defer></script>
  <script src="assets/js/action-collector.js" defer></script>
  <script src="assets/js/action.js" defer></script>
  <script src="assets/js/blocklist-overlay.js" defer></script>
</head>
<body>
  
 <div class="inline-headlines"> 
  <h1>Fail2Ban-Report</h1>
  <h2>Let's catch the bad guys!</h2>
 </div>
