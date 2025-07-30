# 🔧 Fail2Ban-Report Beta 2 – Manual Setup Instructions

These instructions explain how to manually install and configure **Fail2Ban-Report v2** on a Linux system.

---

## ✅ Requirements

- A Linux system (tested with debian only) with the following installed:
  - `fail2ban`
  - `jq`
  - `ufw` (only UFW is supported at this time)
- A PHP-enabled web server (e.g. Apache with PHP 7.4+)
- The web server user (e.g. `www-data`) must have write access to the `/archive/` directory

---

## 📁 Project Structure

Place the project in your desired web directory, for example:

    /var/www/html/Fail2Ban-Report/

The structure should look like this:

    Fail2Ban-Report/
    ├── assets/
    │   ├── css/style.css
    │   ├── images/*.png
    │   └── js/*.js
    ├── includes/
    │   ├── actions/*.php
    │   ├── block-ip.php
    │   ├── unblock-ip.php
    │   ├── list-files.php
    │   └── footer.php
    ├── archive/               ← must be writable by web server
    ├── index.php
    ├── .htaccess
    ├── README.md
    ├── Setup-Instructions.md
    └── (Shell scripts stored outside the web root)

---

## 🔐 Permissions

Make the `/archive/` directory writable for the web server:

    chown -R www-data:www-data /var/www/html/Fail2Ban-Report/
    find /var/www/html/Fail2Ban-Report/ -type d -exec chmod 755 {} \;
    find /var/www/html/Fail2Ban-Report/ -type f -exec chmod 644 {} \;

---

## ⚙️ Shell Scripts

The following two shell scripts **must not** be placed inside the web root.
Recommended path: `/opt/Fail2Ban-Report/`

- `fail2ban_log2json.sh`
- `firewall-update.sh`

Adjust paths in these scripts if necessary:
- `fail2ban_log2json.sh` reads the Fail2Ban log and writes JSON files to `/archive/` (archive/ is a folder placed in the Webspace of /Fail2Ban-Report/ 
- `firewall-update.sh` reads `blocklist.json` and syncs it with UFW (blocks/unblocks) so it also needs the path to `/archive/`

> Make sure both scripts are executable (`chmod +x`)

---

## 🕒 Cronjob Configuration

Set up two cronjobs:

1. Convert logs to JSON every 5–15 minutes:
    
    */5 * * * * root /opt/Fail2Ban-Report/fail2ban_log2json.sh

2. Sync firewall blocklist with UFW every 5–15 minutes:

    */5 * * * * root /opt/Fail2Ban-Report/firewall-update.sh

> Make sure both scripts are executable (`chmod +x`)

---

## 🌐 Web Interface Configuration

- No PHP configuration is required.
- All scripts in `includes/` and `includes/actions/` work without manual changes.
- The web interface displays log information and lets you:
  - View ban history
  - Block/unblock IPs manually
  - Manage the `blocklist.json` interactively

---

## 🔒 Security Notes

The `.htaccess` file includes:

- Protection against direct access to:
  - `.json`, `.sh`, `.ini`, `.log`, `.bak`, `.OLD`
- Rewrite rules for `archive/*.json` and `includes/*.php`
- Strong HTTPS headers
- (Optional) examples for basic authentication and IP restrictions (commented)

Make sure your Apache server honors `.htaccess`, and you enable `mod_rewrite`.

---

## ✅ Setup Complete

You can now access the tool at:

    http(s)://yourdomain.tld/Fail2Ban-Report/

Monitor your logs, manage bans, and secure your system visually and efficiently.

---

## 🌐 Optional: AbuseIPDB Integration

Fail2Ban-Report supports an optional IP reputation check via [AbuseIPDB](https://www.abuseipdb.com/), a public threat intelligence platform.

### 🔎 What It Does

- Displays the number of reports an IP address has received on AbuseIPDB
- Helps assess the trustworthiness of an IP before unblocking it
- Sends reports **only on manual user request** — no automation

### 🧰 Requirements

- A free account at [AbuseIPDB.com](https://www.abuseipdb.com/)
- A personal API key (available in your [AbuseIPDB dashboard](https://www.abuseipdb.com/account/api))

### 🛠 Configuration

1. Create the following file **outside the web root**, e.g. at:

    ```
    /opt/Fail2Ban-Report/fail2ban-report.config
    ```

2. Insert the following content, replacing the placeholder with your actual API key:

    ```ini
    [abuseipdb]
    api_key = YOUR_API_KEY_HERE
    ```

3. Make sure the file is **readable by the web server user** (e.g. `www-data`):

    ```bash
    chown www-data:www-data /opt/Fail2Ban-Report/fail2ban-report.config
    chmod 640 /opt/Fail2Ban-Report/fail2ban-report.config
    ```

4. Done — if the file exists and contains a valid API key, the reputation check will be available when viewing or reporting IPs in the web interface.

### ⚠️ Changing the Config Location or Name

If you choose to place the `.config` file elsewhere or use a different filename:

- Open the file:

    ```
    includes/actions/reports/abuseipdb.php
    ```

- Find the following line (early in the script):

    ```php
    $configPath = '/opt/Fail2Ban-Report/fail2ban-report.config';
    ```

- Modify it to match your custom location or filename.

> The file must be outside the web-accessible path for security reasons.

### 💡 Notes

- This feature is **optional** — Fail2Ban-Report works without it.
- API requests are made **server-side only**, your API key is never exposed to the browser.
- AbuseIPDB’s free tier allows **1,000 requests per day**.

