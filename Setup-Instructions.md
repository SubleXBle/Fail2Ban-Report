# 🔧 Fail2Ban-Report v2 – Manual Setup Instructions

These instructions explain how to manually install and configure **Fail2Ban-Report v2** on a Linux system.

---

## ✅ Requirements

- A Linux system with the following installed:
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

    chown -R www-data:www-data /var/www/html/Fail2Ban-Report/archive/
    chmod -R 755 /var/www/html/Fail2Ban-Report/archive/

---

## ⚙️ Shell Scripts

The following two shell scripts **must not** be placed inside the web root.
Recommended path: `/opt/Fail2Ban-Report/`

- `fail2ban_log2json.sh`
- `firewall-update.sh`

Adjust paths in these scripts if necessary:
- `fail2ban_log2json.sh` reads the Fail2Ban log and writes JSON files to `/archive/`
- `firewall-update.sh` reads `blocklist.json` and syncs it with UFW (blocks/unblocks)

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

© 2025 – Fail2Ban-Report by Suble
