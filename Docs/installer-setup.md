# ✅ Fail2Ban-Report v0.5.0 – Installer

./installer.sh

---


# ✅ Fail2Ban-Report v0.5.0 – Post-Installation Checklist

## 🔐 1. Secure the Web-UI
**Goal:** Prevent unauthorized access  

### 🔒 Enforce HTTPS
```apache
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

🔑 Enable Basic Authentication

```
AuthType Basic
AuthName "Restricted Area"
AuthUserFile /etc/apache2/htpasswd/.htpasswd

<RequireAny>
   Require valid-user
</RequireAny>
```

Create password file:

```
sudo htpasswd -c -B /etc/apache2/htpasswd/.htpasswd admin
```

🛡️ Optional: IP Restriction

```
<RequireAny>
   Require ip YOUR_IP_ADDRESS
   Require ip SYNC_CLIENT_1
   Require ip SYNC_CLIENT_2
</RequireAny>
```

⚙️ 2. Set Up User Management

Goal: Role-based access to blocklists

```
cd /opt/Fail2Ban-Report/Helper-Scripts/
./manage-users.sh
```

Assign roles:

Admin – can modify blocklists

Viewer – read-only access


🧩 3. Review and Customize Configuration

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

🔄 4. Adjust Script Paths for Server Names

Goal: Proper log and blocklist mapping

```
# In fail2ban_log2json.sh
OUTPUT_JSON_DIR="/opt/Fail2Ban-Report/archive/<SERVERNAME>/fail2ban/"

# In firewall-update.sh
BLOCKLIST_PATH="/var/www/html/Fail2Ban-Report/archive/<SERVERNAME>/blocklists/"

```

🧪 5. Enable Logging for Cronjobs (Optional)

Goal: Easier debugging

```
*/5 * * * * /opt/Fail2Ban-Report/Backend/fail2ban_log2json.sh >> /var/log/f2b-report-json.log 2>&1
*/5 * * * * /opt/Fail2Ban-Report/Backend/firewall-update.sh >> /var/log/f2b-report-fw.log 2>&1
```

📡 6. Test the Web-UI

Goal: Verify functionality

Open in browser: http://<SERVER-IP>/Fail2Ban-Report/

Check log display

Verify access protection

Confirm user roles


