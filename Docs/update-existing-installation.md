# Updating existing Installation of Fail2Ban-Report

> Allready collected Daily Fail2Ban -Event Data (up from V 0.1.0) is fully compatible to V 0.5.0

> Blocklists (up from 0.3.3 in *.blocklist.json format) are fully compatible with V 0.5.0

## Get archive/ to the new Structure

right now in your archive/ folder you can find all sorts of .json files

you will have to create a Folder inside of archive/ for your local Installation. This Folder is the "Name" of your Local Server. So if you create a Folder named "Webserver" your local Server will show up in UI as "Webserver"

in your new folder `archive/<SERVERNAME>/` you will have to create 2 additional Folders:

- fail2ban
- blocklists

Change permissions of the new Created Folders and hand it over to your Webserver-User. (chown)

Moove all Daily Event-Lists to `archive/<SERVERNAME>/fail2ban/`
Moove all Blocklists to `archive/<SERVERNAME>/blocklists/`

Your archive/ is set and ready for V 0.5.0

## get /opt/Fail2Ban-Report/ to it's new Structure

Go to /opt/Fail2Ban-Report/
If this Folder does not exist, create it, as the new Version will need this Folder and Its config.

create following Folders: (! ***Case-Sensitive*** !)

opt/Fail2Ban-Report/Settings/
opt/Fail2Ban-Report/Backend/
opt/Fail2Ban-Report/Helper-Scripts/

copy new Versions of `fail2ban_log2json.sh` and `firewall-update.sh` to /opt/Fail2Ban-Report/Backend

make them executeable 

```
chmod +x /opt/Fail2Ban-Report/Backend/*.sh
```

if you are using Cronjobs to run your Backend, please update the Cronjob to the new Path of the Backend-Files

copy the new Config to opt/Fail2Ban-Report/Settings/

Contents of the new `fail2ban-report.config`

```
[reports]
report=false
report_types=abuseipdb,ipinfo

[AbuseIPDB API Key]
abuseipdb_key=YOUR-API-KEY

[IP-Info API Key]
ipinfo_key=YOUR-API-KEY

[Fail2Ban-Daily-List-Settings]
max_display_days=7

[Warnings]
enabled=true
threshold=5:20

[Default Server]
defaultserver=
```


copy following Helper-Scripts from Sources to /opt/Fail2Ban-Report/Helper-Scripts/

- folder-watchdog.sh
- manage-clients.sh
- manage-users.sh

make them executeable
chmod +x /opt/Fail2Ban-Report/Helper-Scripts/*.sh

run the manage-users script to create your admin user

```
./manage-users.sh
```

> - choose 1 to create a user
> - Enter your Username
> - Enter Password
> - Enter "admin" to get admin role (needed to manipulate blocklists)

### Your Backend is now ready for V 0.5.0

---

## Updating Web-UI

> save your `.htaccess` eventually to not have to rework everything or edit new version

Overwrite all your Files in your Web-UI Directory (eg.: `/var/www/html/Fail2Ban-Report/`) with the Files in Web-UI Folder in this Project

you are done - you have upgraded Fail2Ban-Report to it's new version
> if you want to use a checklist, you can use the document for new installation.


