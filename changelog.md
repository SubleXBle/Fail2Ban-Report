# changelog

## Changes made for V 0.3.3 (QoL Update)

- **Warning System and Pending Status Indicators**
  - `config.ini`: Added new `[Warnings]` section to configure thresholds for warning and critical levels (Events per minute per jail).
  - `includes/warnings.php`: New backend script that scans the latest event log for jails exceeding defined thresholds.
  - `assets/js/warnings.js`: New JavaScript file to fetch warning data and render status indicators in the header.
  - `includes/header.php`: Updated to include warning and critical indicators (colored dots and summary).
  - `assets/css/style.css`: Added "Warning Dots" section to style the new header indicators.
  - `includes/block-ip.php`: Now writes a `pending: true` flag when an IP is manually blocked.
  - `includes/unblock-ip.php`: Sets `pending: true` when an IP is manually unblocked.
  - `firewall-update.sh`: Automatically sets `pending: false` for processed entries during block/unblock operations.
  - `assets/js/blocklist-stats.js`: Updated to display pending entries for both block and unblock actions.


### Changelog: Multi-Selection UI and Backend Handling for Ban and Report Actions

### User Interface & Frontend Logic
- **`assets/js/jsonreader.js`**  
  - Changed from action buttons per row to checkboxes for multi-selection of IPs.  
  - Updated rendering logic to support checkbox inputs and bulk selection filtering.

- **`assets/js/action.js`**  
  - Listens on new "Ban" and "Report" buttons instead of old "Action!" buttons.  
  - Collects selected checkbox data and passes arrays of IPs and jails to action collector.

- **`index.php`**  
  - Replaced old checkboxes/buttons with dedicated "Ban" and "Report" buttons for bulk actions.

- **`assets/js/action-collector.js`**  
  - Refactored to accept arrays of IPs and corresponding jails for ban or report actions.  
  - Sends POST requests with multiple IPs to backend PHP endpoints.  
  - Displays notifications for each action with success/info/error styling.

- **`assets/js/notifications.js`** *(new)*  
  - Dedicated notification handling for displaying colored messages (success/info/error).  
  - Extracted from previous action.js notification code.

- **`includes/header.php`**  
  - Included `notifications.js` for notification display support.

### Backend Logic and API
- **`includes/actions/action_ban-ip.php`**  
  - Modified to accept arrays of IPs in POST data.  
  - Calls `blockIp()` for each IP separately, aggregates results.  
  - Returns combined messages with per-IP success, info, or error details.

- **`includes/block-ip.php`**  
  - Updated `blockIp()` function to accept either single IP or array of IPs.  
  - Reads and writes jail-specific blocklist JSON files (e.g. `ssh.blocklist.json`).  
  - Returns detailed results including a new `type` field (`success`, `info`, `error`) for better UI feedback.  
  - Differentiates already blocked IPs (info) vs newly blocked IPs (success).


- **`includes/actions/action_report-ip.php`**  
  - Refactored to support multi-IP reports while sending individual requests internally for each IP and each report service.  
  - Added API-friendly delay (`usleep(500000)`) between requests to avoid rate limits.  
  - Improved aggregation of results and messages with detailed per-IP, per-service data and overall success determination.  
  - Outputs combined JSON response with structured `results` array for frontend consumption.

- **`includes/actions/reports/abuseipdb.php`**  
  - No structural changes, but behavior benefits from backend delay to reduce API call frequency.  
  - Provides detailed report counts and dynamic message types (success, info, error) based on abuse report counts.

- **`includes/actions/reports/ipinfo.php`**  
  - New report integration for IPInfo API, fetching geolocation and network metadata for queried IPs.  
  - Returns informative messages including hostname, location (city, region, country), organization, and other IP details.  
  - Handles API errors and missing keys gracefully, providing clear error messages in JSON format.  
  - Used alongside AbuseIPDB for richer combined IP intelligence reports.  


- Improved error handling and message formatting for multi-IP operations.

---


## Changes made for V 0.3.2 (improvements)
> This update brings several improvements — blocklists are now separated by jail, marking another step towards better Fail2Ban integration.  
> Additionally, many new helpful statistics have been added to the header, along with a new jail filter in the blocklist overview.  
> As expected from a reporting tool, stats are essential, so i added some basic stats.


- **Blocklist per Jail Implementation**
  - The blocklist JSON files are now created and managed individually per Fail2Ban jail instead of using a single global `blocklist.json`.
    → New files like `sshd.blocklist.json`, `dovecot.blocklist.json`, `unknown.blocklist.json` are created automatically on demand.
  - This separation prevents blocklist files from growing too large and enables finer control and filtering by jail.

- **Blocklist Filtering UI Enhancement**
  - The blocklist overlay UI now supports filtering entries by one or multiple jails via a multi-select dropdown.
  - The search input matches IP and jail names simultaneously, combined with date filtering.
  - If an IP appears in multiple blocklists, all occurrences are shown to improve visibility.

- **Backend and Frontend Adjustments**
  - `includes/block-ip.php`: Enhanced to write to and manage jail-specific blocklist files.
  - `includes/get-blocklist.php`: Aggregates entries from all `.blocklist.json` files and returns a combined JSON to the frontend.
  - `includes/actions/action_ban-ip.php`: Passes the jail name along with the IP for correct blocklist assignment.
  - `assets/js/blocklist-overlay.js` (renamed from blocklist.js): Updated to handle multi-jail filtering and render aggregated blocklist data with filtering controls.
  - HTML updated with a multi-select `<select>` element for jail filtering in the overlay.

- **Benefits**
  - Improved scalability and organization of blocklist data.
  - Better user experience through intuitive filtering and visibility.
  - Clear separation of blocklists per Fail2Ban jail without complicating the user workflow.

- **Jail-Aware Unban System**
  - The unban process has been fully adapted to support per-jail blocklists (e.g. `sshd.blocklist.json`, `dovecot.blocklist.json`) instead of the previous global `blocklist.json`.
  - The function `unblockIp()` in `includes/unblock-ip.php` now accepts a second parameter `$jail` and determines the correct blocklist file based on the jail name.
    → The jail name is normalized to lowercase and used to locate the appropriate `*.blocklist.json` file.
  - Improved validation and error handling:
    - Returns a specific message if the jail-specific blocklist file is missing.
    - Detects if an IP was already inactive.
    - Ensures only the correct jail's blocklist is modified.
  - Updated `includes/actions/action_unban-ip.php` to:
    - Accept both `ip` and `jail` via POST.
    - Pass the jail parameter to `unblockIp()`.
    - Return meaningful JSON responses for success or failure.
  - Ensures compatibility with multi-jail environments and prepares for full UI integration of jail-specific unban operations.

- **firewall-update.sh Multi-Jail Support and Cleanup**
  - The firewall update script now processes all `*.blocklist.json` files inside the archive directory (e.g. `/opt/Fail2Ban-Report/archive/`), supporting per-jail blocklists.
  - IPs marked as `"active": false` are automatically removed from UFW firewall rules and then deleted from their respective JSON files to keep blocklists clean and manageable.
  - New IPs with `"active": true` are added to UFW if not already blocked.
  - The script ensures proper file ownership (`www-data`) and permissions (`644`) after each update.
  - Optional logging feature with timestamps can be enabled or disabled (default off).
  - Robust error handling for missing dependencies (`jq` or `ufw`) with clean exits and log messages.
  - Fully backward compatible with existing `.blocklist.json` files, requiring only configuration of the archive directory path.

- **Blocklist Stats Overview**
  - A new stats section was added to display a real-time overview of active and pending entries across all jail-specific blocklists (e.g. `sshd.blocklist.json`, `apache-auth.blocklist.json`).
  - Introduced backend script `includes/blocklist-stats.php`:
    - Scans all `*.blocklist.json` files in the `archive/` directory.
    - Extracts the jail name from the filename and counts `active = true` and `active = false` entries.
    - Returns a JSON structure like:
      ```json
      {
        "sshd": { "active": 12, "pending": 3 },
        "apache-auth": { "active": 5, "pending": 2 }
      }
      ```
  - Frontend integration:
    - Added a new stats block in the header next to existing Fail2Ban stats, showing per-jail active and pending counts.
    - Implemented JavaScript (`assets/js/blocklist-stats.js`) to fetch and render stats dynamically.
    - Blocklist Stats refresh automatically every 10 seconds.


- Extended Fail2Ban Statistics:
  - Backend (`includes/fail2ban-logstats.php`):
    - Enhanced JSON output to include aggregated event counts for yesterday, last 7 days, and last 30 days.
    - Aggregation performed by merging daily JSON event files based on file order (newest first), without relying on date functions.
    - Added structured JSON response with keys: `aggregated.yesterday`, `aggregated.last_7_days`, and `aggregated.last_30_days`.
  - Frontend (`assets/js/fail2ban-logstats.js`):
    - Updated JavaScript to fetch and display aggregated stats alongside today's stats.
    - Added dynamic population of new DOM elements showing event counts, bans, unbans, and unique IPs for the aggregated timeframes.
    - Gracefully handles errors and missing data for aggregated stats.


---

### [0.3.1] – 2025-08-05
- Fixed: Relative path error in `action_unban-ip.php` when accessing `blocklist.json`.  
  → Hotfixed @ 13:10 CEST directly in main.

> Improved security by ensuring all JSON data is accessed only via PHP proxies, preventing direct client-side access.

> Shell scripts (.sh) currently still read and write the .json files directly since these files are still inside the web directory — but this will change soon.
For version 0.3.1, I focus on a clear frontend/backend separation by proxying all JSON access through PHP.
Moving the archive/ directory out of the web root to increase security is planned for the next major step in version 0.4.1.

---

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


