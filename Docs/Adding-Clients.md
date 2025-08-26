# Manual Client Installation – Fail2Ban-Report v0.5.0

<span style="color:red">Username of the Client and Displayed Servername in Web-UI are the same.</span>

> Wanna know more about ![Chain of Trust](chain-of-trust.md) or ![Syncronisation Concept](Sync-Concept.md) ?

# On the new Sync-Client
## Preparation

- Check Fail2Ban → must be running  
- Check UFW → must be running

## Install required packages

```
apt update -qq && apt install jq gawk curl -y -qq
```

Structure of Fail2Ban-Report Sync-Client (!***Case-Sensitive***!)

```
/opt/Fail2Ban-Report/
    Backend/
    Helper-Scripts/
    Settings
    archive/
        fail2ban/
        blocklists/
```
## Helper scripts

in `/opt/Fail2Ban-Report/Helper-Scripts/` create:

- `create-client-uuid.sh`

Make it executable:
```
chmod +x /opt/Fail2Ban-Report/Helper-Scripts/create-client-uuid.sh
```

Run it once to generate client-uuid.json inside
/opt/Fail2Ban-Report/Settings/
```
./create-client-uuid.sh
```
The client UUID will be displayed

You can later see the created UUID with
```
cat /opt/Fail2Ban-Report/Settings/client-uuid.json
```

---

## Backend scripts

> Note: Credentials:
> Username and Servername are basically the same thing.
> The Client will show up on the Server by it's Username
> So if you have your testserver (testing.yourodmain.tld) as Client and you are using the "UserName" "TestServer" it will show up in the UI as TestServer (Case-Sensitive)

In /opt/Fail2Ban-Report/Backend/ create or copy the following scripts:

- config.env
- fail2ban_log2json.sh
- download-checker.sh
- firewall-update.sh
- syncback.sh
- Fail2Ban-Report-cronscript.sh

Make all .sh files executable:

```
chmod +x /opt/Fail2Ban-Report/Backend/*.sh
```

edit config.env
```
# === Shared Fail2Ban Report Client Config ===

# Authentication
CLIENT_USER="MyClientName"
CLIENT_PASS="MyPassword"
CLIENT_UUID="MyUUID"

# Server URLs
ENDPOINT_URL="https://my.server.tld/Fail2Ban-Report/endpoint/index.php"
UPDATE_URL="https://my.server.tld/Fail2Ban-Report/endpoint/update.php"
DOWNLOAD_URL="https://my.server.tld/Fail2Ban-Report/endpoint/download.php"
BACKSYNC_URL="https://my.server.tld/Fail2Ban-Report/endpoint/backsync.php"

# Local Paths
OUTPUT_JSON_DIR="/opt/Fail2Ban-Report/archive/fail2ban"
BLOCKLIST_DIR="/opt/Fail2Ban-Report/archive/blocklists"
CLIENT_LOG="/var/log/fail2ban-report-client.log"
```

You will have to edit:

Authentication Part
`CLIENT_USER` this is your Username on the Server and also the Name that is Displayed in Web-UI for this Server
`CLIENT_PASS` the Passwort for your Client
`CLIENT_UUID` the UUID that was created with `create-client-uuid.sh`

Server URLs

change `my.server.tld/` to fit your envoirement
if curious why so many urls read [Syncronisation-Concept](Sync-Concept.md)

---

## Cron jobs
you set the Fail2Ban-Report-cronscript.sh as the "Master Cronscript"
```
crontab -e
```
then
```
*/5 * * * * /opt/Fail2Ban-Report/Backend/Fail2Ban-Report-cronscript.sh >> /opt/Fail2Ban-Report/Backend/Fail2ban-Report-cronscript.sh
```
This will add a cronjob running the script every 5 minutes

change `*/5` to `*/10` or `*/15` to run every 10 or 15 minutes

The Script will call the other Backend Scripts:

- upload of new fail2ban-log json file
- check if new update of blocklists is available
  - if yes download blocklist
  - change firewall
  - upload changed blocklist
- script ends

---

# On the UI-Server

## Go to Helper-Scripts:

```cd /opt/Fail2Ban-Report/Helper-Scripts/```
```./manage-clients.sh```

Enter data for the new client.

## WebUI configuration

Adjust `.htaccess` in `/var/www/html/Fail2Ban-Report/` (or where your Installation exists) and `/endpoint/` to let the new IPs access it

Add your IP (use Require Any)

## After first sync of Fail2Ban events from the new Client

```cd /opt/Fail2Ban-Report/Helper-Scripts/```
```./folder-watchdog.sh```
