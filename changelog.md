# changelog

> Improved security by ensuring all JSON data is accessed only via PHP proxies, preventing direct client-side access.

> Shell scripts (.sh) currently still read and write the .json files directly since these files are still inside the web directory — but this will change soon.
For version 0.3.1, I focus on a clear frontend/backend separation by proxying all JSON access through PHP.
Moving the archive/ directory out of the web root to increase security is planned for the next major step in version 0.4.1.

## Changes made for V 0.3.1

### ✨ New Features

- **Daily Log Processing**
  - The Bash script `fail2ban_log2json.sh` will now collect only events from the current date creating a daily JSON file: (still overwriting)
    → `archive/fail2ban-events-YYYYMMDD.json`  => (same naming – fully compatible)
  - Benefit: Smaller, cleaner files and no cross-day mixing
  - Enables future statistical analysis
 
- **Ministats in Header**
  - `includes/header.php` header updated with:
    - JS variable `statsFile` for today's JSON
    - New HTML block `#fail2ban-stats` inside header section
  - Stats are displayed neatly beside the page title (flex layout)
  - Stats only show the current dates stats
 
- **Favicon**
  - `assets/css/favicon-32x32.png` added to make browsers happy

- **Mobile Friendly**
  - site is now more mobile friendy by adding `<meta name="viewport" content="width=device-width, initial-scale=0.8">`

---

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

---

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

- `.htaccess`
→ Added "deny access" to archive/ by default

---


