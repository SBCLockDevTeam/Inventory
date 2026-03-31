# Deployment Guide

This guide covers a fresh installation of the SBC QR Inventory system on a Linux/Apache server.

---

## Prerequisites

| Requirement | Version | Notes |
|---|---|---|
| PHP | 7.4+ | With `pdo_mysql`, `session`, `openssl` extensions |
| MariaDB | 10.x+ | Database: `SBCInv` |
| Apache | 2.4+ | HTTPS required for OAuth2 |
| gcc | Any modern | Required to compile `bin/printer.c` |

---

## Step 1 — Clone the Repository

```bash
git clone git@github.com:SBCLockDevTeam/Inventory.git /var/www/html/sbcqr/qr/
```

The application must live at this exact path because `config/settings.php` defines `SERVER_ROOT` and `BASE_PATH` to match the URL `https://sbcqr.com/qr/`.

---

## Step 2 — Create `config/secrets.php`

The application requires a secrets file that is **never stored in Git**.

```bash
cp /var/www/html/sbcqr/qr/config/secrets.php.example \
   /var/www/html/sbcqr/qr/config/secrets.php
```

Edit `config/secrets.php` and fill in all values:

```php
<?php
// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'SBCInv');
define('DB_PASS', 'your_database_password');
define('DB_NAME', 'SBCInv');

// Email (for Ask for Changes feedback)
define('EMAIL_USER', 'your_email@example.com');
define('EMAIL_PASS', 'your_email_password');

// Microsoft Entra ID — see authentication.md for app registration steps
define('ENTRA_TENANT_ID',     'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
define('ENTRA_CLIENT_ID',     'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx');
define('ENTRA_CLIENT_SECRET', 'your-client-secret-value');
```

> ⚠️ Never commit `secrets.php` to Git. It is listed in `.gitignore`.

---

## Step 3 — Set Up the Database

### 3a. Create the database and user

```sql
CREATE DATABASE SBCInv CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'SBCInv'@'localhost' IDENTIFIED BY 'your_database_password';
GRANT ALL PRIVILEGES ON SBCInv.* TO 'SBCInv'@'localhost';
FLUSH PRIVILEGES;
```

### 3b. Apply the schema

For a **fresh installation**, apply only the canonical schema (it already incorporates all migrations):

```bash
mysql -u SBCInv -p SBCInv < /var/www/html/sbcqr/qr/db/schema.sql
```

### 3c. Seed initial data

```bash
mysql -u SBCInv -p SBCInv < /var/www/html/sbcqr/qr/db/seed.sql
```

The seed creates the default `clients` row and initial user(s) so the admin can log in for the first time.

### Updating an existing installation

Apply each new migration in numerical order:

```bash
mysql -u SBCInv -p SBCInv < /var/www/html/sbcqr/qr/db/migrations/00N_name.sql
```

Check `CHANGELOG.md` after each `git pull` to see if any migrations are required.

---

## Step 4 — Compile the Printer Binary

The label-printing feature uses a compiled C helper that sends ESC/P data over TCP. The binary is **not stored in Git** (it is platform-specific) and must be built on each server.

```bash
gcc -o /var/www/html/sbcqr/qr/bin/printer \
       /var/www/html/sbcqr/qr/bin/printer.c
```

No special libraries are required — only standard `gcc` and POSIX system libraries.

**Verify the build:**
```bash
/var/www/html/sbcqr/qr/bin/printer
# Expected output: Usage: printer <host> <port>
```

---

## Step 5 — Set File Permissions

```bash
# Make the printer binary executable
chmod +x /var/www/html/sbcqr/qr/bin/printer

# Ensure the web server user can write to uploads/
chown -R www-data:www-data /var/www/html/sbcqr/qr/uploads/
chmod -R 775 /var/www/html/sbcqr/qr/uploads/

# Protect secrets.php from other users
chmod 640 /var/www/html/sbcqr/qr/config/secrets.php
chown www-data:www-data /var/www/html/sbcqr/qr/config/secrets.php
```

---

## Step 6 — Configure Apache

The application sits at `https://sbcqr.com/qr/`. A minimal Apache virtual host:

```apache
<VirtualHost *:443>
    ServerName sbcqr.com
    DocumentRoot /var/www/html/sbcqr

    SSLEngine on
    SSLCertificateFile    /etc/ssl/certs/sbcqr.com.crt
    SSLCertificateKeyFile /etc/ssl/private/sbcqr.com.key

    <Directory /var/www/html/sbcqr/qr>
        AllowOverride None
        Require all granted
    </Directory>

    # Optional: Redirect HTTP to HTTPS
    # (put in a separate *:80 VirtualHost)
</VirtualHost>
```

> **HTTPS is required.** Microsoft Entra ID will reject non-HTTPS redirect URIs.

---

## Step 7 — Register the Entra App

See [authentication.md](authentication.md) for full instructions. Summary:

1. Register an app in the [Azure Portal](https://portal.azure.com).
2. Set redirect URI to `https://sbcqr.com/qr/auth/callback.php`.
3. Copy **Tenant ID**, **Client ID**, and **Client Secret** into `config/secrets.php`.

---

## Step 8 — Add the First Admin User

After the schema and seed are applied:

1. In the database, find the default `clients` row ID (should be `1`).
2. Insert the first admin user manually (use the Microsoft email address of the admin):

```sql
INSERT INTO users (client_id, name, email, is_admin)
VALUES (1, 'Admin Name', 'admin@yourdomain.com', 1);
```

Once this user logs in via Entra for the first time, their `entra_oid` is written automatically. Subsequent admin users can be added through the web UI under **Admin → Users**.

---

## Updating the Application

```bash
cd /var/www/html/sbcqr/qr
git pull

# Re-apply uploads ownership after every pull.
# git pull runs as root, which resets directory ownership on any
# touched paths. PHP (www-data) must own the uploads tree to create
# per-item subdirectories and write uploaded files.
chown -R www-data:www-data /var/www/html/sbcqr/qr/uploads/
chmod -R 775 /var/www/html/sbcqr/qr/uploads/

# If CHANGELOG.md lists new migrations, apply them:
mysql -u SBCInv -p SBCInv < db/migrations/00N_name.sql

# If bin/printer.c changed, recompile:
gcc -o bin/printer bin/printer.c
chmod +x bin/printer
```

Always check `CHANGELOG.md` after pulling for any server-side steps.

---

## Configuration Reference (`config/settings.php`)

`settings.php` is tracked in Git. Edit it only if the domain or server path changes.

| Constant | Value | Description |
|---|---|---|
| `BASE_PATH` | `/qr` | URL prefix (relative to domain root) |
| `BASE_URL` | `https://sbcqr.com/qr` | Full base URL |
| `SERVER_ROOT` | `/var/www/html/sbcqr/qr` | Absolute server path to app root |
| `ASSETS_PATH` | `BASE_PATH . '/assets/'` | Web path for static assets |
| `CSS_PATH` | `BASE_PATH . '/css/'` | Web path for CSS |
| `JS_PATH` | `BASE_PATH . '/js/'` | Web path for JS |
| `UPLOAD_PATH` | `BASE_PATH . '/uploads/'` | Web path for uploaded files |
| `CONFIG_PATH` | `SERVER_ROOT . '/config/'` | Server path for config files |
| `LIB_PATH` | `SERVER_ROOT . '/lib/'` | Server path for PHP helpers |
| `TEMPLATES_PATH` | `SERVER_ROOT . '/templates/'` | Server path for HTML templates |

---

## Files Not Tracked in Git

These files must be created or managed manually on each server:

| Path | Reason |
|---|---|
| `config/secrets.php` | Contains credentials |
| `bin/printer` | Platform-specific compiled binary |
| `uploads/` | User data (photos, documents, signatures) |
