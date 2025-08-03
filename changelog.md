# changelog

## Changes made for V 0.3.1

### ✨ New Features

- **Daily Log Processing**
  - The Bash script `fail2ban_log2json.sh` will now take only events from the current date creating a daily JSON file: (still overwriting)
    → `archive/fail2ban-events-YYYYMMDD.json`  => (same naming – fully compatible)
  - Benefit: Smaller, cleaner files and no cross-day mixing
  - Enables future statistical analysis
 
- **Statistics Header in the UI**
  - `includes/header.php` header updated with:
    - JS variable `statsFile` for today's JSON
    - New HTML block `#fail2ban-stats` inside header section
  - Stats are displayed neatly beside the page title (flex layout)

### 🔐 Security Improvement: Secure JSON Access for display of Data

- **Proxy Access via PHP Script**
  - Added `includes/get-json.php` as a secure proxy to serve JSON files
  - Only authorized PHP scripts deliver JSON content to frontend JS

- **Updated Frontend JSON Loading**
  - `assets/js/jsonreader.js` now fetches JSON data through `includes/get-json.php?file=...`
  - No more direct file URL requests to `/archive/`
 
- **New PHP Proxy Endpoint**
  - Added `includes/get-blocklist.php` as a secure server-side proxy to serve `blocklist.json` data
  - This prevents direct client-side access to the raw JSON file in `/archive/`

- **Updated JS Blocklist Overlay**
  - Modified `assets/js/blocklist-overlay.js` to fetch blocklist data exclusively via the new PHP endpoint (`includes/get-blocklist.php`)
  - Removed all direct `.json` file fetches from the JS code
  - UI behavior and filtering remain unchanged, ensuring seamless user experience

- **Security Improvement**
  - By decoupling the JS from direct `.json` access, the blocklist data is better protected from unauthorized or direct URL access



### 🛠 Modified or Added Files

- `fail2ban_log2json.sh`  
  → Now filters only for today's entries and structures JSON accordingly

- `includes/header.php`  
  → Injects `statsFile` JS variable and adds stats HTML section

- `includes/fail2ban-logstats.php`  
  → **NEW**: Reads daily JSON data for the frontent script `assets/js/fail2ban-logstats.js`

- `assets/js/fail2ban-logstats.js`  
  → **NEW**: Reads daily Stats from `includes/fail2ban-logstats.php` and injects them into the UI

- `assets/css/style.css`  
  → Added `.inline-headlines` flex layout and style adjustments for stats block

- `includes/get-json.php`  
  → New PHP proxy endpoint for serving JSON files securely

- `assets/js/jsonreader.js`  
  → Modified to fetch JSON data through the PHP proxy instead of direct file access

- `includes/get-blocklist.php`
→ New PHP proxy endpoint to securely serve the blocklist JSON data

- `assets/js/blocklist-overlay.js`
→ Modified to fetch blocklist data via the PHP proxy (get-blocklist.php) instead of accessing the JSON file directly

---


