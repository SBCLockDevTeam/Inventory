# System Architecture

## Overview

The SBC QR Inventory system is a server-side web application that tracks physical items via printed QR code labels. Items are stored in a hierarchical container tree. Each item carries a unique 10-character hexadecimal code that is embedded in its QR label. Scanning the label opens a public read-only detail page; all write operations require Microsoft Entra ID authentication.

---

## Technology Stack

| Layer | Technology |
|---|---|
| Backend language | PHP 7.4+ |
| Database | MariaDB 10.x (database: `SBCInv`) |
| Frontend | Vanilla JavaScript, HTML5, CSS3 |
| Authentication | Microsoft Entra ID (Azure AD) — OAuth 2.0 PKCE |
| Label printing | C binary (`bin/printer`) — ESC/P over TCP |
| Web server | Apache 2.4+ |

No PHP framework, no Composer dependencies, no JavaScript build step. Everything runs on plain PHP + a stock LAMP stack.

---

## URL & File Layout

| Concern | Value |
|---|---|
| Public URL base | `https://sbcqr.com/qr/` |
| Server filesystem root | `/var/www/html/sbcqr/qr/` |
| Apache document root | `/var/www/html/sbcqr/` |
| Configuration constant | `BASE_PATH = '/qr'` |

---

## Directory Structure

```
/qr/
├── admin/                  # Admin-only pages (users, printers, logs, items)
│   ├── items/              # Admin view of item management
│   ├── logs/               # General log + exceptions log viewer
│   ├── printers/           # Network label printer CRUD
│   └── users/              # User account management
├── api/                    # AJAX JSON endpoints (all require auth)
├── auth/                   # OAuth2 flow pages (login, callback, logout)
├── bin/                    # C printer helper binary (compiled on server)
│   ├── printer.c           # Source — tracked in Git
│   └── printer             # Compiled binary — NOT tracked in Git
├── compliance/             # Compliance-related pages
├── config/
│   ├── secrets.php         # DB + Entra credentials — NOT in Git
│   ├── secrets.php.example # Template — in Git
│   └── settings.php        # URL/path constants — in Git
├── css/
│   ├── style.css           # Main stylesheet
│   └── components/         # Per-component CSS files
├── db/
│   ├── schema.sql          # Canonical full schema
│   ├── seed.sql            # Initial seed data
│   └── migrations/         # Numbered migration scripts
├── items/                  # User-facing item management pages
├── js/
│   ├── script.js           # Global JS (avatar dropdown, modals)
│   ├── lib/                # Reusable JS widgets
│   └── pages/              # Page-specific JS files
├── lib/                    # PHP helper classes
├── templates/
│   └── common/             # Shared HTML partials (header, footer, menu)
├── uploads/                # User-uploaded files — NOT in Git
│   ├── photos/
│   ├── documents/
│   └── signatures/
├── home.php                # Redirects authenticated user to item tree
└── index.php               # Public QR lookup (no auth)
```

---

## Core Concepts

### Items and Containers

Every physical object tracked by the system is an **item**. Each item has:

- A unique **`public_code`** — a 10-character lowercase hex string generated at creation time (e.g. `a3f9c12e40`). This is embedded in the printed QR label.
- A **`location_item_id`** pointing to the item's parent container. Root items are their own parent (`location_item_id = public_code`).
- An **`is_container`** flag. Only containers can hold children. Only admins can create root-level items.

Items form an acyclic tree. The application validates that a move cannot create a circular reference (an item cannot be placed inside one of its own descendants).

### Dynamic Fields

Admins define custom fields per item — the system does not require code changes to add a new data point. See [dynamic-fields.md](dynamic-fields.md) for full detail.

### QR Lookup (Public)

`index.php` is the only page that does not require authentication. A QR label encodes the URL `https://sbcqr.com/qr/?Q=<public_code>`. Scanning opens a read-only view of the item's name, description, and all field values. The scan also updates `items.last_seen_at` for audit purposes.

### Authentication Scope

All other pages include `templates/common/header.php`, which calls `AuthHelper::requireAuth()`. This redirects unauthenticated visitors to the Microsoft Entra login flow before they can see anything. See [authentication.md](authentication.md).

---

## Request Lifecycle

### Authenticated page request

```
Browser → Apache → header.php (requireAuth)
    ↓ not authenticated → /auth/login.php → Microsoft → /auth/callback.php
    ↓ authenticated
Page logic (PHP) → DatabaseHelper (PDO) → MariaDB
Page logic → HTML output → Browser
```

### AJAX API request

```
Browser JS → /api/<endpoint>.php (checks isAuthenticated → 401 if not)
    ↓ authenticated
Endpoint logic → DatabaseHelper → MariaDB
Endpoint → JSON response → Browser JS
```

### QR scan

```
QR Scanner → Browser → index.php?Q=<code> (no auth check)
index.php → DatabaseHelper → MariaDB (UPDATE last_seen_at + SELECT details)
index.php → HTML output → Browser
```

### Label print

```
Browser JS (print_label.js)
  → POST /api/print.php { printer_id, item_name, description }
  → PHP builds ESC/P payload
  → proc_open("bin/printer <host> <port>") + writes payload to stdin
  → binary opens TCP socket → sends ESC/P to printer
  → JSON { success: true } → Browser
```

---

## PHP Helper Architecture

All reusable logic lives in `lib/`. Pages and API endpoints include helpers and call their static methods.

| File | Class | Responsibility |
|---|---|---|
| `lib/database.php` | `DatabaseHelper` | PDO wrapper, prepared statements, code generation |
| `lib/auth_helper.php` | `AuthHelper` | Entra OAuth2 PKCE flow, session management |
| `lib/client_helper.php` | `ClientHelper` | Current-user and admin-status helpers |
| `lib/form_helpers.php` | `FormHelper` | Input retrieval, sanitisation, validation |
| `lib/field_helper.php` | `FieldHelper` | Dynamic field CRUD (fields, values, photos, docs, signatures) |
| `lib/location_helper.php` | `LocationHelper` | Item hierarchy traversal, breadcrumb, circular-ref check |
| `lib/tree_helper.php` | `TreeHelper` | Build and render the full item tree |
| `lib/brand_helper.php` | `BrandHelper` | Legacy (single-tenant migration shim — retained for BC) |

---

## Security Summary

| Concern | Mechanism |
|---|---|
| SQL injection | All queries use PDO prepared statements |
| XSS | `FormHelper::sanitize()` (`htmlspecialchars` ENT_QUOTES UTF-8) on all output |
| CSRF (OAuth) | State token generated at login, validated on callback |
| Secrets | `config/secrets.php` excluded from Git (`.gitignore`) |
| Admin access | `ClientHelper::isActiveUserAdmin()` check on every admin page/endpoint |
| Unauthenticated access | `AuthHelper::requireAuth()` in `header.php` (all protected pages) |
| File uploads | Extension allow-list; files stored outside webroot path exposure |

---

## Key External Dependencies

| System | Purpose |
|---|---|
| Microsoft Entra ID | OAuth2 identity provider for user authentication |
| MariaDB | Primary database |
| Apache | Web server (HTTPS required for OAuth2 redirect_uri) |
| Network label printer | ESC/P TCP target for label printing |

No third-party PHP packages (no Composer). No JavaScript frameworks. No CDN dependencies.
