# Manual Client Installation – Fail2Ban-Report v0.5.0

## Preparation

- Check Fail2Ban → must be running  
- Check UFW → must be running

## Install required packages

```
apt update -qq && apt install jq gawk curl -y -qq
```

```
/opt/Fail2Ban-Report/
    Backend/
    Helper-Scripts/
    archive/
        fail2ban/
        blocklists/
```

## Backend scripts

> Note: Credentials:
> Username and Servername are basically the same thing.
> The Client will show up on the Server by it's Username
> So if you have your testserver (testing.yourodmain.tld) as Client and you are using the "UserName" "TestServer" it will show up in the UI as TestServer (Case-Sensitive)

In /opt/Fail2Ban-Report/Backend/ create the following scripts:

- fail2ban_log2json.sh ← insert client login credentials
- download-checker.sh ← insert client login credentials
- firewall-update.sh ← insert client login credentials
- syncback.sh ← insert client login credentials

Make all .sh files executable:

```
chmod +x /opt/Fail2Ban-Report/Backend/*.sh
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

The client UUID will be displayed – copy it.


Optional: Cron jobs

/path/to/fail2ban_log2json.sh
/path/to/download-checker.sh && /path/to/firewall-update.sh && /path/to/syncback.sh

---

# On the server

## Go to Helper-Scripts:

cd /opt/Fail2Ban-Report/Helper-Scripts/
./manage-clients.sh

Enter data for the new client.

## WebUI configuration

Adjust .htaccess as needed

Add your IP (use Require Any)

If .htaccess is also used in endpoint/, add IP there as well

## After first sync of Fail2Ban events from the new Client

cd /opt/Fail2Ban-Report/Helper-Scripts/
./folder-watchdog.sh
