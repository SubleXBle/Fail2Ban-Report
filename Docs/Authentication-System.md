## 🔑 Authentication in Fail2Ban-Report

This version introduces a **user-based authentication system** to secure access to the Fail2Ban-Report web interface.

### ✨ Key Features

1. **Session-based Login**
   - Users authenticate with username and password.
   - Passwords are securely stored using **bcrypt hashing** (`password_hash` / `password_verify`).
   - On successful login, a secure PHP session is established.

2. **Secure Session Management**
   - Session cookies are set with `HttpOnly`, `Secure`, and `SameSite=Strict`.
   - Inactivity timeout: **30 minutes**.
   - Absolute session lifetime: **2 hours**.
   - Session ID is automatically regenerated every **15 minutes**.
   - Session is bound to the client using a **fingerprint** (browser user-agent + IP subnet).

3. **Role-based Access Control**
   - Default role: `viewer`.
   - Extendable with roles like `admin` (configured in `users.json`).
   - Access checks are handled via the `is_admin()` function.

4. **Login / Logout Mechanism**
   - Logout reliably destroys the session and clears the session cookie.
   - Failed login attempts are logged via `error_log` → compatible with **Fail2Ban** monitoring.

5. **User Data**
   - User accounts are stored in a local JSON file (`users.json`).
   - Structure: `username`, `password` (hash), `role`.
   - File should be protected with strict filesystem permissions.

---

### Protection-Level
- Protects the Fail2Ban-Report Blocklists from unauthorized access.  
- Hardened against session hijacking and fixation attacks.  
- Provides a foundation for future improvements (e.g., CSRF protection [_Login Page_], additional roles, additional admin features, Fail2Ban Support by logging failed login attempts).

---
