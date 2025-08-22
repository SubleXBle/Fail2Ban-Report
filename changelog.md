# changelog

## Timeline
```

0.3.1 ──► 0.3.2 ──► 0.3.3 ──► 0.3.4 ──► 0.4.0 ──► 0.5.0
🔐🌐     ⚙️🌐     ⚙️🌐     ⚙️🌐     🌐🐳    ⚙️🔐🌐
  │         │         │         │         │         │
  │         │         │         │         │         └─ Multi-server + endpoint
  │         │         │         │         └─ Marker system, copy clipboard & Docker Version
  │         │         │         └─ Warnings & pending status
  │         │         └─ Jail-specific blocklists & filters
  │         └─ Aggregated Fail2Ban stats
  └─ Secure JSON proxy, daily logs, mobile, favicon


##########  Legend #########

🔐 Security
🌐 UI
⚙️ Backend
🐳 Docker

```

# detailed Changes

## Changes made for 0.5.0

### Restructuring `archive/` directory
The archive structure has been reorganized for multi-server support:

```
archive/
└── %server%/ <= Hostname or choosen Name for Host
├── fail2ban/ <= daily json files from fail2ban
├── blocklists/ <= blocklists
└── ufw/ <= future feature
```

Standardserver has to be set in config

### Updated PHP files
- `block-ip.php` → path updated to `archive/%server%/blocklists/`
- `blocklist-stats.php` → path updated to `archive/%server%/blocklists/`
- `fail2ban-logstats.php` → path updated to `archive/%server%/fail2ban/`
- `get-blocklist.php` → path updated to `archive/%server%/blocklists/`
- `get-json.php` → path updated to `archive/%server%/fail2ban/` (2x)
- `list-files.php` → path updated to `archive/%server%/fail2ban/`
- `ufw-report.php` → paths updated to `archive/%server%/blocklists/` and `archive/%server%/ufw/`
- `unblock-ip.php` → path updated to `archive/%server%/blocklists/`
- `warnings.php` → path updated to `archive/%server%/fail2ban/`

### Updated JS files
- `action-collector.js`

### Centralized Path Configuration

**Dynamic Path Management**
- New `paths.php` introduced for centralized path management
   - `$PATHS['config']` → `/opt/Fail2Ban-Report/Settings/`  
   - `$PATHS['blocklists']` → server-specific blocklist paths  
- Usage:
```php
require_once __DIR__ . "/paths.php";
$NEEDED_PATH = $PATHS["blocklists"];
```

### UI

- new dropdown for server-list to switch between servers

## Authentication for Admin-Actions

1. **Session-based Authentication**
   - Secure sessions with `session_set_cookie_params`:  
     - `HttpOnly = true`  
     - `Secure = true` (HTTPS only)  
     - `SameSite = Strict`  
   - Session timeout set to 30 minutes  
   - Session roles: `viewer` (default) / `admin`  

2. **Login / Logout Functionality**
   - Login form with username & password  
   - Password verification using `password_verify()` (bcrypt)  
   - Session regeneration after successful login (`session_regenerate_id(true)`)  
   - Logout destroys the session and redirects back to login page  

3. **User Management via JSON File**
   - File: `users.json` located outside the web root (`/opt/Fail2Ban-Report/Settings/users.json`)  
   - No default users – users must be created via shell script  
   - File permissions: `chown root:www-data` + `chmod 0660`  
   - All admin and viewer information loaded from this file  

4. **Admin-only Actions**
   - `block-ip.php` and `unblock-ip.php` check `is_admin()`  
   - Viewers cannot execute these actions and receive clear error messages  

5. **UI Adjustments**
   - Role display, e.g., `Viewer` or `Admin`  
   - Server selection preserved correctly across sessions
   - Login Form in Header
   - Logout button resets role to `Viewer`  

#### Roles
  - `Block` and `Unblock` Features need now Admin Role to work
  - Users with Roles `Viewer` or `Admin` can be added with provided .sh Script `manage-users.sh`



### fail2ban_log2json.sh

**Changes:**
- **Support for "Increase Ban" events:**  
  - Added parsing logic for `Increase Ban` events in addition to `Ban` and `Unban`.
  - Extracts the IP from `Increase Ban` lines separately.
  - Ensures only lines with valid IPs are included in the JSON output.
- **Updated grep pattern:**  
  - From `grep -E "(^|[^A-Za-z])(Ban|Unban) "` to `grep -E "(Ban|Unban)"` to capture `Increase Ban`.
- **Output path adjustment:**  
  - `OUTPUT_JSON_DIR` updated from `/opt/Fail2Ban-Report/archive/YOUR-HOSTNAME/fail2ban` to `/var/www/Fail2Ban-Report/archive`.
- **Minor cleanup:**  
  - Last comma removal logic kept to ensure valid JSON.

---

### jsonreader.js

**Changes:**
- **Increase Ban handling in table:**
  - `Increase Ban` events are no longer rendered as separate rows to avoid flooding the table.
  - Counts `Increase Ban` events per IP and marks the corresponding `Ban`/`Unban` row with a yellow marker (`🟡`).
  - Appends the count of `Increase Ban` events in parentheses next to the yellow marker.
- **Marker logic updated:**
  - Red marker (`🔴`) now indicates multiple `Ban`/`Unban` events per IP.
  - Yellow marker (`🟡`) indicates that `Increase Ban` events exist for that IP, even if no repeated `Ban`/`Unban`.
  - Combination of red and yellow markers can appear if both conditions apply.
- **Filtering remains consistent:**
  - Marker filter logic updated to respect new marker assignments.
- **Step restructuring for clarity:**
  - Added steps for counting `Increase Ban`, filtering, marker assignment, marker filtering, jail dropdown rebuild, sorting, and rendering.

**Behavioral impact:**
- Reduces duplicate rows caused by multiple `Increase Ban` events.
- Highlights IPs with `Increase Ban` activity on the same day.
- Table rendering and filtering continue to work as before with updated marker system.

### index.php
changed the dropdown-list to match the new Marker assignment


### Fail2Ban-Report 0.5.0 – Backend / Endpoint Updates

### 1. Endpoint (`/endpoint/index.php`)
- New HTTPS endpoint for clients to send JSON data (`fail2ban-events-*.json` and `*.blocklist.json`).  
- Authentication using:
  - Username  
  - Password (bcrypt)  
  - UUID  
  - IP address (optional whitelist via `.htaccess`)  
- File type handling:
  - **fail2ban-events-*.json:** overwrites existing file in `archive/<username>/fail2ban/`  
  - **\*.blocklist.json:** locked via `flock`; existing entries are updated (`pending=false`) or deleted depending on transmitted status  
- Automatic creation of client folders in `archive/` on first upload  
- Correct permissions set for web server (`root:www-data`)  

---

### 2. Client Script for JSON Creation & Upload (`fail2ban_log2json_push.sh`)
- Generates daily JSON from Fail2Ban logs (`fail2ban-events-YYYYMMDD.json`).  
- Uploads the JSON directly to the endpoint using `curl` with authentication (Username + Password + UUID).  
- Logs upload results locally.  
- All key settings (log file, output directory, endpoint URL, auth credentials) configurable at the top of the script.  

---

### 3. Client List Management Helper (`manage-clients.sh`)
- CLI tool to add, edit, or delete client entries.  
- Each client has:
  - Username  
  - Password (bcrypt, server-side hash)  
  - UUID  
  - IP address  
- Stored in `client-list.json` (`/opt/Fail2Ban-Report/Settings/`).  
- Password hash generated via PHP `password_hash()`.  

---

### 4. Client UUID Generation (`create-client-uuid.sh`)
- Script to generate a client UUID for installation or rotation.  
- Stores UUID in `/opt/Fail2Ban-Report/Settings/client-uuid`.  
- Lightweight, intended for initialization or rotation only.  

---

### 5. `.htaccess` for Endpoint
- Separate `.htaccess` in the `endpoint/` folder to override global security rules.  
- By default, only `index.php` is accessible; all other files blocked.  
- Optional IP whitelist (`Require ip <IP>`) can be enabled.  
- Optional Basic Auth can be added.  
- Prevents directory listing and access to sensitive files.  

---

### Summary
- Fully new endpoint infrastructure for client JSON push/pull.  
- Client scripts for upload, UUID, and client list management created.  
- Security enhanced through combined authentication and dedicated `.htaccess` for endpoint.  
- Flexible file handling for Fail2Ban events and blocklists implemented.




---

## Changes made for V 0.4.0

### Optimized `firewall-update.sh` for faster processing, improving performance with large JSON files.

#### Changed
- **Batch blocking of IPs**
  - Original: Loops through each active IP and runs `ufw deny from "$ip"` immediately if not already blocked.
  - New: Collects all new active IPs per jail and executes `ufw deny from "$ip"` in one batch section before performing a single `ufw reload`.

- **UFW reload behavior**
  - Original: No explicit reload after blocking; relied on UFW to apply rules instantly.
  - New: Explicit `ufw reload` after all block actions are done to ensure all deny rules are active before proceeding.

- **Unblocking procedure**
  - Original: For each inactive IP:
    - Lists all matching UFW rules.
    - Deletes them immediately without reload between rules.
  - New: For each inactive IP:
    - Performs `ufw status numbered` before deletion to ensure correct rule numbering.
    - Deletes rules one-by-one **with reload after each deletion** to avoid numbering mismatches.

- **JSON update timing**
  - Original: Updates JSON and cleans inactive entries after processing each IP.
  - New: Updates and cleans JSON **once per jail** after all block/unblock actions are completed.

#### Unchanged
- Locking mechanism using `/tmp/{jail}.blocklist.lock` remains identical.
- Validation of prerequisites (`jq`, `ufw`).
- Ownership and permission setting (`chown www-data:www-data`, `chmod 644`).
- Logging format and verbosity remain compatible.

### Small Changes for Statistics in Header

small changes where made in the following files:
- `header.php` **History** visual Changes
- `fail2ban-logstats.php` **History** visual Changes
- `fail2ban-logstats.js` **History** visual Changes
- `index.php` **Sort** per IP
- `style.css` several small changes

### 🟡🔴 Warnings Feature - Changelog

#### New Features
- **IP Event Marker System**:  
  - Highlights IPs appearing multiple times with the same event (`Ban`/`Unban`) in the current view.
  - Highlights IPs present in multiple jails in the current view.
  - Visual markers:
    - 🟡 Yellow → Multiple same events
    - 🔴 Red → Appears in multiple jails
    - ⚪ Grey → No marker
- **Sortable & Filterable 'Mark' Column**:  
  - Added a new column `Mark` in the main result table.
  - Column is fully sortable like other columns.
  - Marker filter dropdown added to filter by marker type:
    - All
    - Yellow
    - Red
    - Yellow + Red
    - None
- **Dynamic Filtering**:
  - Markers update in real-time when filters (`Action`, `Jail`, `IP`, `Date`) are changed.
  - Table automatically updates to reflect filtered marker status.

#### UI Enhancements
- Marker column added between `Action` and `IP` for better visibility.
- Marker filter dropdown integrated into existing filters, maintaining logical order.
- Supports responsive layout using flexbox, keeping filters and buttons aligned.

#### Implementation Notes
- Marker calculation is based on the currently displayed dataset, not the full JSON.
- No changes to backend JSON structure are required; marker is computed client-side.
- Fully compatible with existing sort and filter system.


### ✨ New Feature: Copy Filtered Data to Clipboard

- **Added** a new "Copy to Clipboard" button to export the currently **filtered table data**.
- **Implemented** a dedicated JavaScript file `assets/js/table-export.js` for the copy functionality.
- **Integration** with existing DataTables filtering logic to ensure only visible/filtered rows are copied.
- **Output Format**: Tab-separated values (TSV) with all HTML tags removed for clean text export.
- **User Feedback**: 
  - Shows a warning if there’s no data to copy.
  - Shows a success or error alert based on the clipboard operation result.


### List of changed files

- firewall-update.sh
- index.php
- includes/unblock-ip.php
- includes/blocklist-stats.php
- includes/fail2ban-logstats.php
- includes/header.php
- includes/unblock-ip.php
- includes/warnings.php
- assets/css/style.css
- assets/js/fail2ban-logstats.js
- assets/js/jsonreader.js
- assets/js/table-export.js



---

## Changes made for V 0.3.4 (Fix)

Json Files should not loose Data anymore when several write processes trying to write the json file at the same time.

- `firewall-update.sh`: Added FLOCK to lock json when writing
- `block-ip.php`: Added FLOCK to lock json when writing
- `unblock-ip.php`: Added FLOCK to lock json when writing
- `blocklist-stats.php`: Shows now more correct States of active and pending when blocking and unblocking



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


