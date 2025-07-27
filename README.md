# Fail2Ban-Report

A simple and clean web-based reporting tool for Fail2Ban events.  
Turn your daily Fail2Ban logs into searchable and filterable JSON reports – right on your webspace.

## 📦 Features

- Parses `fail2ban.log` into daily JSON logs
- Filter by date, action (`Ban` / `Unban`), jail and IP fragment
- Responsive dark-themed UI
- Easy to deploy, no database, no frameworks

---

## ⚙️ Setup Instructions

### 1️⃣ Bash Script Setup (`fail2ban_log2json.sh`)

1. Save the script `fail2ban_log2json.sh` anywhere on your server (e.g. `/usr/local/bin/`).
2. Make it executable:
   ```bash
   chmod +x /path/to/fail2ban_log2json.sh
   ```
3. Open the script and adjust the following lines to fit your environment:
   ```bash
   LOGFILE="/var/log/fail2ban.log"       # path to your Fail2Ban log
   OUTPUT_JSON_DIR="/var/www/Fail2Ban/archive"  # output directory for .json files (served by webserver)
   ```
4. Run the script manually or via a daily cronjob:
   Run script via
   ```bash
   ./fail2ban_log2json.sh
   ```
   or run it via cronjob:
   ```bash
   crontab -e
   ```
   then
     ```bash
   @daily /path/to/fail2ban_log2json.sh
   ```
   or any other time that fits your needs

### 2️⃣ Web Interface Setup (Webspace)

1. On your webserver, create a folder for the tool (e.g. Fail2Ban)
   ```bash
   /var/www/html/Fail2Ban/
   ```
2. Place the following files inside this folder:
   + <code>index.php</code>
   + style.css
