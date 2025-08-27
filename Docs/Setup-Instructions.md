# 🔧 Fail2Ban-Report V 0.5.0 – Manual Setup Instructions


This guide explains how to install **Fail2Ban-Report v0.5.0** from scratch.
It covers the Web-UI setup, security hardening, backend configuration, and user management.

> 🚨 This guide assumes that you have downloaded the project to /home/USER/ and that the project folder is named Fail2Ban-Report-latest. Also that you want to install the Web-UI in /var/www/html/
Therefore, any copy commands or paths in this guide are based on this assumption.

> Folder Names are Case Sensitive - please use them as descibed to avoid conflicts

Wanna know more about ![Chain of Trust](chain-of-trust.md) or ![Syncronisation Concept](Sync-Concept.md) ?


---

## ✅ Requirements

- A Linux system (tested with debian only) with the following installed:
  - `fail2ban`
  - `jq`
  - `awk` (gawk)
  - `curl`
  - `ufw` (only UFW is supported at this time)
- A PHP-enabled web server (e.g. Apache with PHP 7.4+)
- The web server user (e.g. `www-data`) must have write access to the `/archive/` directory

---

## Step 1 – Install Web-UI on the Server

1. Create the target directory on your web server, e.g.:

   ```bash
   mkdir -p /var/www/html/Fail2Ban-Report
   ```

2. Copy the content of the `Web-UI` folder into it:

   ```bash
   cp -r /home/USER/Fail2Ban-Report-latest/Web-UI/* /var/www/html/Fail2Ban-Report/
   ```

3. Adjust file ownership so your web server user can access them:

   ```bash
   chown -R -P www-data:www-data /var/www/html/Fail2Ban-Report
   ```

---

## Step 2 – Secure the Web-UI

Edit the included **`.htaccess`** file and apply the following settings:

### 1. Enforce HTTPS

Go to the section **“Basic HTTPS Headers”** and make sure the Web-UI is only available via **HTTPS**.
All unencrypted HTTP requests should be redirected to HTTPS.

### 2. Authentication

Fail2Ban-Report does not include a native base login.
Use the `.htaccess` file to secure access with either **Basic Authentication** or **IP-based restrictions**.

---

### Option A: Password Protection

Enable Basic Auth in `.htaccess`:

```apache
AuthType Basic
AuthName "Restricted Area"
AuthUserFile /etc/apache2/htpasswd/.htpasswd

<RequireAny>
   Require valid-user
</RequireAny>
```

* Create the `.htpasswd` file outside of the web root, e.g.:

  ```bash
  htpasswd -c -B /etc/apache2/htpasswd/.htpasswd admin
  ```

* Store only **bcrypt-hashed passwords**, never plain text.
  You can use the built-in Apache `htpasswd` tool (strongly recommended) or an external generator (e.g. my tool: [bcrypt generator](https://suble.net/htpasswd.php)).

> Note, that you need your Sync Clients to allow access via IP also to get over password restrictions

---

### Option B: IP Restriction

```apache
<RequireAny>
   Require ip <YOUR_IP_ADDRESS>
</RequireAny>
```

If you want to allow your Sync Clients as well, list them explicitly:
> Note that this will be needed, if you have sync clients and use password protection to allow sync-clients to get over password restriction by using ip address

```apache
<RequireAny>
   Require ip <YOUR_IP_ADDRESS>
   Require ip <Sync-Client-1>
   Require ip <Sync-Client-2>
</RequireAny>
```

⚠️ **Important:** Never allow unauthenticated access to your Web-UI! Use either password protection, IP restriction, or both.

---

## Step 3 – Backend Setup

Create the following directory structure (case-sensitive!):

```
/opt/Fail2Ban-Report
├── archive
│   ├── fail2ban
│   └── blocklists
├── Helper-Scripts
├── Settings
└── Backend
```

---

### Helper-Scripts

Copy helper scripts:

```bash
cp -r /home/USER/Fail2Ban-Report-latest/Helper-Scripts/* /opt/Fail2Ban-Report/Helper-Scripts/
```

Adjust paths in `folder-watchdog.sh`:

* Set `BASE_PATH` to the directory of your Web-UI `archive` folder.

---

### Config File

Copy the config file:

```bash
cp -r /home/USER/Fail2Ban-Report/Conf/fail2ban-report.config /opt/Fail2Ban-Report/Settings/
```

Example configuration:

```ini
[reports]
report=false
report_types=abuseipdb,ipinfo

[Fail2Ban-Daily-List-Settings]
max_display_days=7

[Warnings]
enabled=true
threshold=5:20

[Default Server]
defaultserver=
```

* **Reports**: If enabled (`report=true`), you need API keys for [AbuseIPDB](https://www.abuseipdb.com/) and [IPInfo](https://ipinfo.io/).
* **report_types**: can be configured with `report_types=` `abuseipdb`,`ipinfo` (you can set none, one or both - seperate them with `,`)
* **Warnings**: Threshold `5:20` means “Warning” at 5 bans/minute per jail, “Critical” at 20.
* **Default Server**: Define the default server name (folder name) shown in the Web-UI.

---

### Backend Scripts

Copy backend scripts:

```bash
cp -r /home/USER/Fail2Ban-Report/Backend/fail2ban_log2json.sh /opt/Fail2Ban-Report/Backend/
cp -r /home/USER/Fail2Ban-Report/Backend/firewall-update.sh /opt/Fail2Ban-Report/Backend/
```

Adjust script paths:

* `fail2ban_log2json.sh` → set `OUTPUT_JSON_DIR` to `/opt/Fail2Ban-Report/archive/<SERVERNAME>/fail2ban/`
* `firewall-update.sh` → set blocklist path to `/var/www/html/Fail2Ban-Report/archive/<SERVERNAME>/blocklists`

Make scripts executable:

```bash
chmod +x /opt/Fail2Ban-Report/Backend/*.sh
```

---

### Cronjobs

Edit your crontab:

```bash
crontab -e
```

Example (run every 5 minutes):

```bash
*/5 * * * * /opt/Fail2Ban-Report/Backend/fail2ban_log2json.sh
*/5 * * * * /opt/Fail2Ban-Report/Backend/firewall-update.sh
```

* `*/5` = every 5 minutes
* `*/15` = every 15 minutes
* `*/30` = every 30 minutes

Logging is recommended for easier debugging.

---

## Step 4 – Create Admin User

To ensure only authorized users can manipulate blocklists, **authentication is required** since v0.5.0.

Go to the helper scripts directory:

```bash
cd /opt/Fail2Ban-Report/Helper-Scripts/
./manage-users.sh
```

* Enter username and password (stored as bcrypt hash only).
* Assign a role:

  * `Admin` → can manipulate blocklists
  * `Viewer` → read-only access

---

## Done! 🎉🎊🥳

Fail2Ban-Report is now running on your server.
To add more clients, follow the ![**“Adding-Clients”** guide](Adding-Clients.md).
> Username of the Client and Displayed Servername in Web-UI are the same.
