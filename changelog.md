# changelog

## Changes made for V 0.3.1

- **Daily Log Processing**
  - The Bash script `fail2ban_log2json.sh` will now take only events from actual date to creates a separate JSON file per day (still overwriting)
    → `archive/fail2ban-events-YYYYMMDD.json`  => as it was allready - so fully compatible with this version
  - Benefit: Smaller, cleaner files and no cross-day mixing
 
- **Statistics Header in the UI**
  - `index.php` header updated with:
    - JS variable `statsFile` for today's JSON
    - New HTML block `#fail2ban-stats` inside header section
  - Stats are displayed neatly beside the page title (flex layout)

