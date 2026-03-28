# QR Inventory System

This is a 2nd attempt at a QR Inventory system for SBC.

It uses PHP, CSS, JavaScript, MariaDB.

See `.agentic.yml` and `.agentic.workflow.yml` for project rules.

---

## Server Setup & Installation

### 1. Clone the repository

```bash
git clone git@github.com:SBCLockDevTeam/Inventory.git /var/www/html/sbcqr/qr/
```

### 2. Configure secrets

Copy the secrets template and fill in your database credentials:

```bash
cp /var/www/html/sbcqr/qr/config/secrets.php.example /var/www/html/sbcqr/qr/config/secrets.php
```

Then edit `/var/www/html/sbcqr/qr/config/secrets.php` and enter your database password and any other environment-specific credentials.

> ⚠️ `secrets.php` is excluded from Git and must be created manually on every server.

### 3. Compile the printer binary

The application includes a compiled C helper (`bin/printer`) for sending ESC/P label jobs to a network printer over TCP. This binary is **not stored in Git** (it is platform-specific) and **must be compiled on each server after cloning**.

**Why it exists:** PHP's `fsockopen()` had intermittent hostname resolution failures on some server configurations. This compiled helper bypasses that issue.

**To compile:**

```bash
gcc -o /var/www/html/sbcqr/qr/bin/printer /var/www/html/sbcqr/qr/bin/printer.c
```

No special libraries are required — only standard `gcc` and POSIX system libraries (available on any standard Linux server).

**To verify it compiled correctly:**

```bash
/var/www/html/sbcqr/qr/bin/printer
```

You should see: `Usage: printer <host> <port>`

**Usage:** The binary reads a raw ESC/P payload from stdin and sends it to the printer at `<host>:<port>` over TCP. It accepts both hostnames and IP addresses.

### 4. Set file permissions

```bash
chmod +x /var/www/html/sbcqr/qr/bin/printer
```

---

## Dependencies

| Dependency | Version      | Notes                        |
|------------|--------------|------------------------------|
| PHP        | 7.4+         |                              |
| MariaDB    | 10.x+        | Database: `SBCInv`           |
| Apache     | 2.4+         | Document root: `/var/www/html/sbcqr/` |
| gcc        | Any modern   | Required to compile `bin/printer.c` |

---

## Directory Structure

```
/qr/
├── /assets/       # Images and media
├── /bin/          # Compiled binaries (built on server, not tracked in Git)
│   ├── printer.c  # Source code for printer helper (tracked in Git)
│   └── printer    # Compiled binary (NOT tracked in Git — compile from printer.c)
├── /config/       # Configuration files
│   ├── secrets.php         # NOT tracked in Git — create from .example
│   └── secrets.php.example # Template — tracked in Git
├── /css/          # Stylesheets
├── /js/           # JavaScript
├── /lib/          # PHP utility/helper functions
├── /templates/    # HTML templates (header, footer, menu, etc.)
└── /uploads/      # User-uploaded files (not tracked in Git)
```

---

## Git Workflow

- AI writes changes to Git (`main` branch) first.
- User pulls changes to the server after.
- **Never commit** `config/secrets.php` or the compiled `bin/printer` binary.
- Work on `main` branch only — no pull requests.
