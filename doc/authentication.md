# Authentication

The system uses **Microsoft Entra ID (Azure AD)** as its sole identity provider via the **OAuth 2.0 Authorization Code flow with PKCE**. There are no passwords stored in the application.

---

## How it Works

### 1. User visits a protected page

Every page includes `templates/common/header.php`. The first thing `header.php` does is:

```php
AuthHelper::requireAuth();
```

If the visitor has no valid session, they are immediately redirected to `/auth/login.php`.

### 2. Login initiation (`auth/login.php`)

`login.php` calls `AuthHelper::initiateLogin()`, which:

1. Generates a cryptographically random **PKCE code verifier** and derives the **code challenge** (`SHA-256` base64url).
2. Generates a random **state token** (CSRF protection).
3. Stores both in the PHP session (`$_SESSION`).
4. Redirects the browser to:

```
https://login.microsoftonline.com/{ENTRA_TENANT_ID}/oauth2/v2.0/authorize
  ?client_id={ENTRA_CLIENT_ID}
  &response_type=code
  &redirect_uri=https://sbcqr.com/qr/auth/callback.php
  &scope=openid profile email
  &code_challenge={base64url_sha256_of_verifier}
  &code_challenge_method=S256
  &state={random_state}
```

### 3. Microsoft sign-in

The user authenticates with their organisational Microsoft 365 account. Microsoft redirects the browser back to:

```
https://sbcqr.com/qr/auth/callback.php?code=<auth_code>&state=<state>
```

### 4. Callback (`auth/callback.php`)

`callback.php` calls `AuthHelper::handleCallback($code, $state)`:

1. **CSRF check** — confirms `$state` matches the value stored in the session. Rejects mismatches.
2. **Token exchange** — POSTs to `https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token` with the `code`, `client_secret`, and PKCE verifier. Microsoft returns an ID token (JWT).
3. **JWT parsing** — Base64-decodes the ID token payload (no signature verification needed; the token comes directly from Microsoft's TLS-secured endpoint). Extracts:
   - `oid` — Microsoft Object ID (stable permanent identifier)
   - `email` / `preferred_username` — user's email address
   - `name` — display name
4. **Local user resolution** (`resolveLocalUser()`):
   - Queries `users` table by `entra_oid` first (fast path for returning users).
   - Falls back to `email` match if `entra_oid` is not yet set (first login). On match, writes the `oid` back to `users.entra_oid` for future fast-path lookups.
   - If no matching user is found, authentication fails with a friendly error.
5. **Session write** — Stores auth state in `$_SESSION['SessionAuth']`:
   ```php
   [
     'entra_oid'    => '...',
     'email'        => '...',
     'display_name' => '...',
     'user_id'      => (int),
     'client_id'    => (int),
     'name'         => '...',
     'is_admin'     => (bool),
   ]
   ```
6. **Redirect** — Sends the user to the originally requested page (stored in session before the redirect to login), or to `/home.php` as a fallback.

### 5. Subsequent requests

`AuthHelper::isAuthenticated()` checks `$_SESSION['SessionAuth']`. If present, the user is treated as authenticated. The session persists for the duration of the browser session (no explicit expiry — relies on PHP's `session.gc_maxlifetime`).

### 6. Logout (`auth/logout.php`)

1. Unsets `$_SESSION['SessionAuth']`.
2. Returns the Microsoft single sign-out URL:
   ```
   https://login.microsoftonline.com/{tenant}/oauth2/v2.0/logout
     ?post_logout_redirect_uri=https://sbcqr.com/qr/auth/login.php
   ```
3. Redirects the browser there, which clears Microsoft's SSO cookies.

---

## Authorization Levels

| Level | Check | Where applied |
|---|---|---|
| Authenticated | `AuthHelper::isAuthenticated()` | All pages via `header.php`; all API endpoints |
| Admin | `ClientHelper::isActiveUserAdmin()` | All `admin/` pages; field management; root item creation |
| Public (no auth) | — | `index.php` (QR lookup) only |

Admins have access to all features. Regular users can create/edit items and fill field values, but cannot manage users, printers, or field definitions.

---

## Session Data Reference

Retrieved via `AuthHelper::getAuthUser()` or `ClientHelper::getActiveUser()`:

| Key | Type | Description |
|---|---|---|
| `entra_oid` | `string` | Microsoft Object ID |
| `email` | `string` | User's Microsoft email |
| `display_name` | `string` | User's display name from Microsoft |
| `user_id` | `int` | Row ID in `users` table |
| `client_id` | `int` | Row ID in `clients` table |
| `name` | `string` | User's name as stored in `users` table |
| `is_admin` | `bool` | Whether user has admin privileges |

---

## App Registration (Azure Portal Setup)

To enable authentication, an app must be registered in Microsoft Entra ID:

1. Go to [https://portal.azure.com](https://portal.azure.com) → **Microsoft Entra ID** → **App registrations** → **New registration**.
2. Set a name (e.g. "SBC QR Inventory").
3. Set **Supported account types** to "Accounts in this organizational directory only" (single-tenant).
4. Add a **Redirect URI** (Web):
   ```
   https://sbcqr.com/qr/auth/callback.php
   ```
5. After registering, note the **Directory (tenant) ID** and **Application (client) ID**.
6. Under **Certificates & secrets**, create a new **Client secret** and note its value.
7. Under **API permissions**, ensure `openid`, `profile`, and `email` delegated permissions are present (they are by default for new registrations).

Enter the three values in `config/secrets.php`:
```php
define('ENTRA_TENANT_ID',     'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
define('ENTRA_CLIENT_ID',     'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
define('ENTRA_CLIENT_SECRET', 'your-client-secret-value');
```

---

## Pre-provisioning Users

Users **must exist** in the `users` table before they can log in. The application does not auto-create user records.

1. Admin goes to **Admin → Users → Add User**.
2. Enters the user's **Microsoft email address** (must match exactly what Microsoft sends in the ID token).
3. Optionally sets **Is Admin** if the user needs admin privileges.

On the user's first login, `resolveLocalUser()` matches by email and writes their `entra_oid` to the record for all future logins.

If a user's email changes in Microsoft, an admin must update the email in **Admin → Users** or the user will be unable to log in until the `entra_oid` is also reset.

---

## Security Notes

- **No secrets in Git** — `config/secrets.php` is in `.gitignore`. The client secret is never stored in version control.
- **PKCE** prevents authorization code interception attacks; the code verifier is never sent to Microsoft until the token exchange.
- **State token** is validated on every callback to prevent CSRF attacks.
- **No password storage** — credentials are entirely managed by Microsoft.
- **HTTPS required** — OAuth2 redirect URIs must be HTTPS. Ensure Apache or a proxy terminates TLS before PHP.
