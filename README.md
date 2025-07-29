# Fail2Ban-Report

A simple and clean web-based reporting tool for Fail2Ban events.  
Turn your daily Fail2Ban logs into searchable and filterable JSON reports – right on your webspace.

## 🛡️ This tool does not replace proper intrusion detection and access control. It is a visualization layer and should be deployed accordingly.

![Fail2Ban.png](Fail2Ban.png)

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

## 📝 Notes
+ Stylesheet have been moved to style.css for easy customization.
+ The JSON output is plain and lightweight. You can post-process or archive old data easily.
+ This tool requires no database and can run even on very minimal webspace setups. (e.g. RaspberryPi)

## Protecting Your Fail2Ban Report with .htaccess

To enhance the security of your Fail2Ban report, a `.htaccess` file is provided that:

- Disables directory listings
- Blocks direct access to sensitive files such as `.json` and `.css`
- Sets basic HTTP security headers for safer browsing

### How to Use the `.htaccess` File

1. Save the provided `.htaccess` file in the root directory of your Fail2Ban report (where `index.php` resides).
2. Ensure your web server allows `.htaccess` overrides (typically via `AllowOverride` in Apache).
3. The `.htaccess` will automatically protect files in the main directory and subfolders like `/archive/`.

### Important Security Notice

While this `.htaccess` provides a basic level of protection, **it is highly recommended to implement additional security measures**, such as:

- HTTP authentication (Basic Auth) to restrict access to authorized users only
- IP-based access restrictions to allow only trusted networks or addresses

Fail2Ban reports often contain sensitive security-related data. Adding these layers of protection will help prevent unauthorized access and keep your data safe.

For example, you can set up Basic Auth with:

```apache
AuthType Basic
AuthName "Restricted Area"
AuthUserFile /path/to/.htpasswd
Require valid-user
```

You can use the htpasswd helper for your htpasswd files (choose bcrypt as algorythm as it is better) on [https://suble.net/htpasswd/](https://suble.net/htpasswd/) (⚠️ german language)

or restrict by IP:

```
Require ip 192.168.1.0/24
Require ip 203.0.113.5
```

## 📄 License
This project is released under the GPLv3 License. Feel free to modify and share.

---

## 🗺️ Roadmap

**Fail2Ban-Report** is designed to be lightweight, modular, and open to future improvements. The following features are currently planned:

### ⚙️ Setup & Automation
- Setup script to automate initial installation, including directory structure and permissions
- Optionally auto-configure a daily cronjob

### 🔐 Security Features
- Integration of a stronger `.htaccess` file for basic access control and secure defaults

### 🔥 Active Defense Integration
- Allow manual IP blocking directly from the interface via `iptables` or `ufw`
- Optionally enable automatic blocking of suspicious IPs based on defined criteria

## 🧩 Open to Contributions
I'm happy to hear from users and contributors!  
Whether it's:
- feature requests,  
- improvement ideas,  
- or even pull requests —  
Feel free to reach out or contribute directly.

If you use this tool and think "Hey, wouldn't it be cool if it could also do XYZ?" — I'm all ears!
