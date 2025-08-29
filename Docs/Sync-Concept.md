# Sync Concept of Fail2Ban-Report

> Username of the Client and Displayed Servername in Web-UI are the same.
> Please also read the [Chain of Trust Document](chain-of-trust.md) to understand when Data-Ownership is transfered between Server and Client.
> There is a poibility that set blocks in blocklists will be set back in WebUI because of Client Syncronisation. (Last Truth from Client) Future Versions will prevent this

## Web UI
When a block or unblock action is triggered via the Web UI (example: block):

1. The IP is sent to the blocklist of the respective jail from the correct server, containing:
   - IP address  
   - Timestamp  
   - `active=true`  
   - `pending=true`  
2. An entry is created in `/archive/update.json` with server name, updated blocklist, and `true`.

---

## On the Client
When the client synchronizes its firewall, it processes the blocklist and applies it to the firewall.  
If a block was set:
- `active=true` remains  
- `pending` is set to `false`  

---

## After Sync on the Server
Once the blocklist is synced back to the server, the entry is no longer shown as `pending` but instead as `active`.

---

## Endpoints

### index.php
1. Client authenticates using server name, password, UUID, and IP (validated via `client-list.json`).  
2. `index.php` accepts `fail2ban-event.json` from the client and overwrites the server version.

### update.php
1. Client authenticates with server name, password, UUID, and IP (validated via `client-list.json`).  
2. Client queries `update.php` to check if an update is available (`update.json` is checked).  
3. Client receives a JSON response with a list of updated blocklists.  
4. `update.php` copies the corresponding blocklists into a protected download directory.  
5. `update.php` sets the entry for the copied blocklist in `update.json` to `false`.  

### download.php
1. Client authenticates (same as with `update.php`).  
2. Upon successful authentication, the client receives its blocklists (no direct downloads allowed).  
3. After delivery, blocklists are removed from the download directory.  

### syncback.php
1. Client authenticates (same as above).  
2. Client uploads blocklists to `syncback.php`.  
3. `syncback.php` saves the blocklists in a temp directory, locks the server-side blocklist, and overwrites it with the client’s latest valid version.  
   - **Note:** This can cause intermediate changes (between download and sync-back) to be lost. However, it guarantees that server and client are fully consistent afterward.  
4. After overwriting, `syncback.php` removes the corresponding blocklist from `update.json` and releases it again.  

---

## Resulting Behavior
- Data authority is with the **server** until the client downloads the blocklist.  
- Data authority shifts to the **client** until the blocklist is synced back.  
- Once synced back, data authority returns to the **server**.  

---

## Security
- The server only communicates with authenticated clients.  
- No direct access to `.json` files is possible.  
- No direct download of blocklists is allowed.  
- Although application-level authentication (server name, password, UUID) is sufficient, it is strongly recommended to also restrict client IP addresses for additional security.
- For web access, an additional .htaccess protection (or comparable server-level restriction) is highly recommended.  
- An additional **AllowList** in `.htaccess` is highly recommended.  
