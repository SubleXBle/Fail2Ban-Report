# ✅ Fail2Ban-Report v0.5.0 – Installer

This guide explains how to use the automated installer script for **Fail2Ban-Report v0.5.0**.  
The installer sets up the **Web-UI**, the **Backend**, and (optionally) configures cronjobs.  
It is designed for **Debian/Ubuntu-like systems**.

---

## 📋 Prerequisites

Before running the script, make sure:

- You are running it as **root** (or with `sudo`).
- Dependencies are installed:
```bash
  apt update
  apt install -y git jq curl rsync apache2-utils
```

## ▶️ Running the Installer

Download and run the script:

```
wget https://raw.githubusercontent.com/SubleXBle/Fail2Ban-Report/latest/install.sh -O install.sh
chmod +x install.sh
sudo ./install.sh

```

## ⚙️ What the Installer Does

### ✅ Checks requirements
Ensures you are root and verifies required commands (`awk`, `jq`, `curl`, `git`).

### 📝 Collects user input
- Where to download/clone the source (default: `~/Fail2Ban-Report-latest`)  
- Where to install the Web-UI (default: `/var/www/html/Fail2Ban-Report/`)

### ⬇️ Downloads the latest source
Uses `git` to clone or update the repository from GitHub.

### 🌐 Installs the Web-UI
- Copies files to the chosen web directory.  
- Sets ownership to `www-data:www-data`.

### 🛠 Installs the Backend
- Creates required directories in `/opt/Fail2Ban-Report/`.  
- Copies backend scripts and configuration files.  
- Adjusts paths in scripts for JSON logs and blocklists.  
- Makes scripts executable.

### ⏰ Sets up cronjobs (optional)
- Asks if you want cronjobs for backend automation.  
- Lets you choose the interval (5, 10, 15, or 30 minutes).  
- Installs cronjobs for:
  - `fail2ban_log2json.sh`  
  - `firewall-update.sh`

### 📊 Final Summary
At the end, you’ll see:
- ✔ Source directory  
- ✔ Web-UI path  
- ✔ Backend path  
- ✔ Cronjob status  


## ✅ Example Installer Output

After a successful run, you will see:

```
========================================
   Fail2Ban-Report v0.5.0 – Setup Done
========================================

The following steps were completed:
  ✔ Sources downloaded from GitHub (/home/user/Fail2Ban-Report-latest)
  ✔ Web-UI installed to: /var/www/html/Fail2Ban-Report
  ✔ Backend installed to: /opt/Fail2Ban-Report
  ✔ Cronjobs installed (Interval: */5 * * * *)

Next steps:
  1. Open the Web-UI: http://<SERVER-IP>/Fail2Ban-Report/
  2. Edit the configuration file if needed:
     /opt/Fail2Ban-Report/Settings/fail2ban-report.config
  3. Protect the Web-UI from public access!

Installation completed. Enjoy your Fail2Ban-Report! 🚀
```


---


## ✅ Fail2Ban-Report v0.5.0 – Post-Installation Checklist

### 🔐 1. Secure the Web-UI
**Goal:** Prevent unauthorized access  

#### 🔒 Enforce HTTPS
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

#### 🔑 Enable Basic Authentication

```
AuthType Basic
AuthName "Restricted Area"
AuthUserFile /etc/apache2/htpasswd/.htpasswd

<RequireAny>
   Require valid-user
</RequireAny>
```

#### Create password file:

```
sudo htpasswd -c -B /etc/apache2/htpasswd/.htpasswd admin
```

#### 🛡️ Optional: IP Restriction

```
<RequireAny>
   Require ip YOUR_IP_ADDRESS
   Require ip SYNC_CLIENT_1
   Require ip SYNC_CLIENT_2
</RequireAny>
```

### ⚙️ 2. Set Up User Management

Goal: Role-based access to blocklists

```
cd /opt/Fail2Ban-Report/Helper-Scripts/
./manage-users.sh
```

#### Assign roles:

- Admin – can modify blocklists
- Viewer – read-only access


### 🧩 3. Review and Customize Configuration

Goal: Optimize reporting and thresholds

```
nano /opt/Fail2Ban-Report/Settings/fail2ban-report.config
```

Example settings:

```
[reports]
report=true
report_types=abuseipdb,ipinfo

[Warnings]
enabled=true
threshold=5:20

```

### 🔄 4. Adjust Script Paths for Server Names

Goal: Proper log and blocklist mapping

```
# In fail2ban_log2json.sh
OUTPUT_JSON_DIR="/opt/Fail2Ban-Report/archive/<SERVERNAME>/fail2ban/"

# In firewall-update.sh
BLOCKLIST_PATH="/var/www/html/Fail2Ban-Report/archive/<SERVERNAME>/blocklists/"

```

### 🧪 5. Enable Logging for Cronjobs (Optional)

Goal: Easier debugging

```
*/5 * * * * /opt/Fail2Ban-Report/Backend/fail2ban_log2json.sh >> /var/log/f2b-report-json.log 2>&1
*/5 * * * * /opt/Fail2Ban-Report/Backend/firewall-update.sh >> /var/log/f2b-report-fw.log 2>&1
```

### 📡 6. Test the Web-UI

Goal: Verify functionality

Open in browser: http://<SERVER-IP>/Fail2Ban-Report/

Check log display

Verify access protection

Confirm user roles




