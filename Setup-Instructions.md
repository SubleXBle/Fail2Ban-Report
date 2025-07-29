# ⚙️ Setup Instructions

### 1️⃣ Bash Script Setup (`fail2ban_log2json.sh`)

1. Save the script `fail2ban_log2json.sh` anywhere on your server (e.g. `/usr/local/bin/`).
2. Make it executable:
   ```bash
   chmod +x /path/to/fail2ban_log2json.sh
   ```
3. Open the script and adjust the following lines to fit your environment:
   `LOGFILE="/var/log/fail2ban.log"       # path to your Fail2Ban log`
   `OUTPUT_JSON_DIR="/var/www/Fail2Ban/archive"  # output directory for .json files (served by webserver)`
4. Run the script manually or via a daily cronjob:
   Run script via
   ```bash
   ./fail2ban_log2json.sh
   ```
   or run it via cronjob:
   ```
   crontab -e
   ```
   then
   ```
   @daily /path/to/fail2ban_log2json.sh
   ```
   or any other time that fits your needs (you can try the crontab time generator on [https://suble.net/cronhelper/](https://suble.net/cronhelper/) (⚠️german language)

### 2️⃣ Web Interface Setup (Webspace)

1. On your webserver, create a folder for the tool (e.g. Fail2Ban)
   ```
   /var/www/html/Fail2Ban/
   ```
2. Place the following files inside this folder:
   + <code>index.php</code>
   + <code>style.css</code>
   + <code>.htaccess</code>

3. Inside the same folder, create a subfolder named <code>archive</code>:
   ```
   /var/www/html/Fail2Ban/archive/
   ```
5. Make sure the webserver (e.g. www-data) has read access to this directory and write access if you want to store JSON files directly there.
   ```apache2
   chown -R www-data:www-data /var/www/html/Fail2Ban/*
   ```

## 🖥️ Usage
After the first log run is processed, open your browser and go to:
```
   https://yourdomain.tld/Fail2Ban/
```
You will see a dropdown to choose the date, filter by action, jail, and IP.
