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
     ```
   @daily /path/to/fail2ban_log2json.sh
   ```
   or any other time that fits your needs (try the crontab time generator on suble.net)

### 2️⃣ Web Interface Setup (Webspace)

1. On your webserver, create a folder for the tool (e.g. Fail2Ban)
   ```
   /var/www/html/Fail2Ban/
   ```
2. Place the following files inside this folder:
   + <code>index.php</code>
   + <code>style.css</code>

3. Inside the same folder, create a subfolder named <code>archive</code>:
   ```
   /var/www/html/Fail2Ban/archive/
   ```
4. Make sure the webserver (e.g. www-data) has read access to this directory and write access if you want to store JSON files directly there.
   ```apache2
   chown -R www-data:www-data /var/www/html/Fail2Ban/*
   ```

## 🖥️ Usage
After the first log run is processed, open your browser and go to:
```
   https://yourdomain.tld/Fail2Ban/
```
You will see a dropdown to choose the date, filter by action, jail, and IP.

## 📝 Notes
+ Stylesheet have been moved to style.css for easy customization.
+ The JSON output is plain and lightweight. You can post-process or archive old data easily.
+ This tool requires no database and can run even on very minimal webspace setups. (e.g. RaspberryPi)

## 📄 License
This project is released under the MIT License. Feel free to modify and share.

## ✨ Contributions
Pull requests, ideas or translations welcome!

---

## 🗺️ Roadmap

**Fail2Ban-Report** is designed to be lightweight, modular, and open to future improvements. The following features are currently planned:

### 🔐 Security Features
- Integration of a proper `.htaccess` file for basic access control and secure defaults
- Optional password protection for the web interface

### ⚙️ Setup & Automation
- Setup script to automate initial installation, including directory structure and permissions
- Optionally auto-configure a daily cronjob

### 🔥 Active Defense Integration
- Allow manual IP blocking directly from the interface via `iptables` or `ufw`
- Optionally enable automatic blocking of suspicious IPs based on defined criteria

### 🧩 Open to Contributions
I'm happy to hear from users and contributors!  
Whether it's:
- feature requests,  
- improvement ideas,  
- or even pull requests —  
Feel free to reach out or contribute directly.

If you use this tool and think "Hey, wouldn't it be cool if it could also do XYZ?" — I'm all ears!
