# changelog

## Changes made for V 0.3.1

- **Daily Log Processing**
  - The Bash script `fail2ban_log2json.sh` will now take only events from actual date to creates a separate JSON file per day (still overwriting)
    → `archive/fail2ban-events-YYYYMMDD.json`  => as it was allready - so fully compatible with this version
  - Benefit: Smaller, cleaner files and no cross-day mixing
 
- **Statistics Header in the UI**
  - `includes/header.php` header updated with:
    - JS variable `statsFile` for today's JSON
    - New HTML block `#fail2ban-stats` inside header section
  - Stats are displayed neatly beside the page title (flex layout)

### 🛠 Modified or Added Files

- `fail2ban_log2json.sh`  
  → Now filters only for today's entries and structures JSON accordingly

- `includes/header.php`  
  → Injects `statsFile` JS variable and adds stats HTML section

- `includes/fail2ban-logstats.php`  
  → **NEW**: Reads daily JSON so the `assets/js/fail2ban-logstats.js` can read it

- `assets/js/fail2ban-logstats.js`  
  → **NEW**: Reads daily Stats from `includes/fail2ban-logstats.php` and injects stats into the page

- `assets/css/style.css`  
  → Added `.inline-headlines` flex layout and style adjustments for stats block

---
