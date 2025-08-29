# Chain of Trust – Authentication and Data Ownership

> ### Please read carefully
> This is the first Version of the **Fail2Ban-Report** Endpoint - upcoming releases will make changes to ensure a smoother workflow.


The **backend shell scripts** run with `sudo` or root privileges to read Fail2Ban logs and modify firewall rules.
This ensures that processing only occurs at a trusted system level, as long as the server itself is properly secured.

---

## 1) Creation of Daily JSON Files (Fail2Ban Events)

During data transfer from client to server, the following information is verified:

* Username
* Password
* UUID
* IP address (optional but recommended)

Uploads are performed via a PHP script that additionally validates file names and structure.
This ensures that the JSON data comes from a **trusted source**.

### ➡️ The ownership of Fail2Ban event data is transferred from the client to the server.

---

## 2) Manipulation of Blocklists (Web Interface)

* Fail2Ban events are displayed in the Web UI.
* Changes (Block/Unblock) are performed **exclusively through the web interface**.
* The Web UI ensures that browsers and JavaScript cannot directly access the `.json` files.
* Only users with the **Admin** role in Fail2Ban-Report (Application Accounts) can make changes.
* Authentication is handled via a **session-based system**, and passwords are stored only in encrypted form (bcrypt) on the backend.

### ➡️ Data ownership resides with the server, which manages the blocklists and provides them via the UI.
### ➡️ The chain of trust remains intact because only verified accounts can make modifications.

---

## 3) Data Synchronization (Client ↔ Server)

* **The server never actively pushes data.**
* Clients actively check the server for updates.
* Clients re-authenticate with username, password, UUID, and optionally IP address.
* Updates are only provided after successful authentication.

Flow:

1. Client requests update → Server verifies authentication.
2. If an update exists, the server places the blocklist in an endpoint directory.
   
### ➡️ Data ownership transfers to the client.

3. Client downloads the blocklist; the file is then deleted from the server.
4. Client processes the data locally (root shell script, stores it in `archive`).
5. Client uploads the updated blocklist back to the server (re-authentication required).
6. Server overwrites its blocklist with the client's version.

### ➡️ Data ownership returns to the server.

---

## Conclusion: Chain of Trust

* **Root/sudo shell scripts** perform local processing (trusted environment).
* **Authenticated communication** ensures that only authorized clients and admins can manipulate or transfer blocklists.
* **Data ownership** switches transparently between server and client, but always remains within a verified trust chain.

### ➡️ Result: A complete **Chain of Trust** that guarantees data integrity and security in blocklist management and synchronization.
