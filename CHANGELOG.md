# Changelog

## Workflow exceptions

(None yet)

## Changes

### 2026-03-29 — Item tree expand/collapse state now persists across tabs and browser restarts

The previous implementation stored the tree's open/collapsed state in `sessionStorage`,
which is scoped to a single browser tab. This meant the state was lost whenever the user
opened a new tab or restarted the browser, making the feature appear non-functional.

- **`js/pages/item_tree.js`**: Switched from `sessionStorage` to `localStorage` for
  storing the expand/collapse map (`itemTreeExpanded` key). State now survives across
  browser tabs and browser restarts, and is only reset when the user explicitly clicks
  "Collapse All" or clears their browser storage.

**Server setup instructions** (apply after pulling):
1. Pull the latest code:
   ```
   cd /var/www/html/sbcqr/qr && git pull
   ```

### 2026-03-28 — Printer selection persists per user across sessions

Printer preference is now stored server-side per authenticated user so it
survives across different browsers and devices.

- **`db/migrations/006_add_preferred_printer_to_users.sql`**: Adds
  `preferred_printer_id BIGINT UNSIGNED NULL` to `users` with a FK to
  `printers(id) ON DELETE SET NULL`. Run this migration on the server after
  pulling.
- **`db/schema.sql`**: Updated users table definition to include
  `preferred_printer_id` and the matching foreign key constraint.
- **`api/set_printer.php`**: New AJAX endpoint (POST `printer_id`). Requires
  authentication. Validates that the printer exists and is active, then
  persists the choice in `users.preferred_printer_id`.
- **`admin/items/view.php`**: Now loads the authenticated user's saved
  printer preference. Selection priority: (1) user's saved preference,
  (2) system default (`is_default = 1`), (3) first active printer. The
  correct option is pre-selected server-side in the dropdown HTML.
- **`js/pages/print_label.js`**: Removed `localStorage`-based persistence.
  The initial dropdown value is set by the server. On change, the new
  selection is saved to the server via `api/set_printer.php` (failure is
  logged silently as it is non-critical). Print-job logic is unchanged.

**Server setup instructions** (apply after pulling):
1. Pull the latest code:
   ```
   cd /var/www/html/sbcqr/qr && git pull
   ```
2. Run the migration to add the preference column:
   ```
   mysql -u SBCInv -p SBCInv < /var/www/html/sbcqr/qr/db/migrations/006_add_preferred_printer_to_users.sql
   ```

### 2026-03-28 — Printer: simplified connection error message

- **`api/print.php`**: When the printer binary exits non-zero, the verbose stderr (e.g. `Error: could not connect to pierround.com:9102`) is now written to the server error log via `error_log()` instead of being forwarded to the browser. The JSON response always returns the user-friendly message `"Print failed: Could not connect to printer."`.
- **`js/pages/print_label.js`**: Removed the `✗ ` prefix from error messages so the browser shows the exact string returned by the API (e.g. `Print failed: Could not connect to printer.`).

### 2026-03-28 — Printer: hostname support via binary helper

- **Root cause**: PHP's `fsockopen()` has had intermittent failures resolving hostnames (e.g. `pierround.com`) in some server configurations. The existing `bin/printer.c` binary used `inet_addr()` which only accepts raw IP addresses, not hostnames.
- **`bin/printer.c`**: Rewritten to use `getaddrinfo()` for DNS resolution, supporting both hostnames (`pierround.com`) and IP addresses. The binary now reads the raw ESC/P payload from stdin (piped by PHP) instead of sending a hardcoded test message, making it suitable for real label jobs.
- **`bin/printer`**: Recompiled from the updated source.
- **`api/print.php`**: Replaced `fsockopen()` TCP call with `proc_open()` invocation of `bin/printer`. PHP builds the ESC/P payload and pipes it to the binary via stdin; the binary handles DNS resolution and the TCP send. Error output from the binary is captured and forwarded in the JSON error response.
- **`admin/printers/add.php`**: "Host" label updated to "Hostname or URL" for clarity.
- **`admin/printers/edit.php`**: "Host" label updated to "Hostname or URL"; added the same `placeholder` hint already present in `add.php`.
- **`admin/printers/index.php`**: "Host" column heading updated to "Hostname / URL".

**Server setup instructions** (apply after pulling):
1. Pull the latest code:
   ```
   cd /var/www/html/sbcqr/qr && git pull
   ```
2. Ensure the binary is executable:
   ```
   chmod +x /var/www/html/sbcqr/qr/bin/printer
   ```
3. Verify the web server user (`www-data`) can execute it:
   ```
   ls -la /var/www/html/sbcqr/qr/bin/printer
   ```
4. Printer hostnames (e.g. `pierround.com`) can now be set or updated via **Admin → Printers → Edit**.

- **Migration 004 updated** (`db/migrations/004_add_printers.sql`): renamed `ip_address VARCHAR(45)` to `host VARCHAR(255)` so that domain names (e.g. `pierround.com`) and IP addresses are both supported. Seed data updated to use `pierround.com` with ports 9101/9102/9103.
- **`db/schema.sql`**: added `printers` table definition (was missing from the canonical schema).
- **`admin/printers/index.php`**: "IP Address" column heading and data cell updated to "Host".
- **`admin/printers/add.php`**: `ip_address` field renamed to `host`; placeholder updated to show both domain and IP examples.
- **`admin/printers/edit.php`**: `ip_address` field renamed to `host`.
- **`api/print.php`**: `$printer['ip_address']` reference updated to `$printer['host']`; SELECT column list updated accordingly.

**Server setup instructions** (apply after pulling):
1. Run the migration to create the printers table:
   ```
   mysql -u SBCInv -p SBCInv < /var/www/html/sbcqr/qr/db/migrations/004_add_printers.sql
   ```
2. This creates the `printers` table and inserts the three default printers (`pierround.com:9101`, `pierround.com:9102`, `pierround.com:9103`).
3. You can adjust printer host/port values at any time via Admin → Printers.

### 2026-03-27 — Microsoft Entra ID authentication

- **Entra ID integration**: Users now authenticate via Microsoft Entra ID (Azure AD) using OAuth 2.0 Authorization Code Flow with PKCE. No Composer dependency — implemented in plain PHP using `stream_context_create` for the token exchange.
- **New `lib/auth_helper.php`**: Core authentication library. Handles `initiateLogin()` (redirects to Microsoft), `handleCallback()` (exchanges code, resolves local user, writes session), `logout()` (clears session, returns Microsoft SSO logout URL), `requireAuth()` (page guard), and `getAuthUser()` (returns current auth state).
- **New `auth/login.php`**: Redirect-only page that starts the OAuth flow via `AuthHelper::initiateLogin()`.
- **New `auth/callback.php`**: OAuth callback handler. Validates the CSRF state token, calls `AuthHelper::handleCallback()`, and redirects on success or renders a plain error page on failure.
- **New `auth/logout.php`**: Clears the local session and redirects to Microsoft's single sign-out endpoint.
- **`config/secrets.php.example`**: Added `ENTRA_TENANT_ID`, `ENTRA_CLIENT_ID`, and `ENTRA_CLIENT_SECRET` placeholders with setup instructions.
- **`lib/client_helper.php`**: Refactored to derive active user and client from the Entra auth session instead of the old manual user-selector session. `getActiveUser()` now returns the authenticated user. `setActiveClient()` is restricted to admin users only. `setActiveUser()` is a no-op when authenticated (prevents impersonation).
- **`templates/common/header.php`**: Added `AuthHelper::requireAuth()` guard — all pages including this template are now protected. Replaced the old client/user dropdown pair with: a client dropdown (admins only, for switching context) and a static authenticated-user display (name + email).
- **`templates/common/menu.php`**: Added a Logout link. Clients and Users menu items are now hidden from regular (non-admin) users.
- **DB schema — `users` table**: Added `email VARCHAR(255) NULL UNIQUE` and `entra_oid VARCHAR(64) NULL UNIQUE` columns. Admin must enter a user's Microsoft email in the user record before they can log in.
- **`db/migrations/002_add_entra_auth_to_users.sql`**: Migration for existing installations.
- **`admin/users/add.php` / `edit.php`**: Added email input field (optional, must be a valid email format). Admin enters the user's Microsoft email so they can authenticate via Entra ID.
- **`admin/users/index.php`**: Added Email column to the users table.
- **CSS `css/style.css`**: Added `.auth-user-name` and `.auth-user-email` styles for the header user display.

**Server setup instructions** (for the user to apply after pulling):
1. Register an app in Microsoft Entra ID (Azure portal → Microsoft Entra ID → App registrations → New registration).
2. Set the redirect URI to `https://sbcqr.com/qr/auth/callback.php` (type: Web).
3. Create a client secret under Certificates & secrets.
4. Copy `/config/secrets.php.example` to `/config/secrets.php` and fill in `ENTRA_TENANT_ID`, `ENTRA_CLIENT_ID`, `ENTRA_CLIENT_SECRET`.
5. Run the DB migration: `mysql -u SBCInv -p SBCInv < /var/www/html/sbcqr/qr/db/migrations/002_add_entra_auth_to_users.sql`
6. Edit each user via Admin → Users → Edit and enter their Microsoft email address.

### 2026-03-27 — Phase 2: Core feature completion

- **Dynamic field values**: Created `admin/items/values.php` — users can now enter, view, and update values for all custom field types (text, textarea, number, date, checkbox, photo, document, signature) on any item.
- **Field values on view page**: Updated `admin/items/view.php` to display a read-only summary of all field values beneath the item details. "Fill Values" and "Clone Item" action buttons added.
- **Photo upload**: Created `api/upload_photo.php` and `api/delete_photo.php` AJAX endpoints. Created `js/lib/photo_capture.js` reusable widget — single button opens a dialog offering "Take Photo with Camera" or "Browse for Image File" as per UX spec.
- **Document upload**: Created `api/upload_document.php` and `api/delete_document.php` AJAX endpoints. Files stored in `/uploads/documents/`.
- **Signature capture**: Created `api/save_signature.php` and `api/delete_signature.php` AJAX endpoints. Created `js/lib/signature_capture.js` — canvas-based signature dialog with touch/mouse drawing, optional printed name, and instructions text.
- **Item cloning**: Created `admin/items/clone.php` — supports structure-only clone (field definitions, blank values) and structure+data clone (field definitions + scalar values copied by value, not by reference). Photos/documents/signatures are not cloned.
- **Field delete**: Fixed placeholder Delete buttons in `admin/items/fields.php` — wired to new `admin/items/field_delete.php` AJAX endpoint. Cascading DB deletes automatically remove all stored values for the deleted field.
- **Inline JS removed**: Extracted `<script>` block from `fields.php` into `js/pages/fields.js`. No more inline JavaScript.
- **Log views**: Created `admin/logs/index.php` (general log, admin-only, paginated, filterable by item and action type) and `admin/logs/exceptions.php` (exceptions log, customer-facing, plain language). Menu updated with Logs section.
- **Ask for Changes form**: Replaced JavaScript `alert()` stub with a proper accessible modal. Created `templates/common/ask_changes_modal.php`, `api/ask_changes.php` AJAX endpoint (emails `info@securitybuildingcontrols.com`), and updated `js/script.js` and `templates/common/footer.php`.
- **Hamburger menu**: Mobile navigation now uses a hamburger toggle button. Menu collapses on small screens and expands on click. Responsive CSS added to `css/style.css`.
- **FieldHelper library**: Created `lib/field_helper.php` with reusable helpers for field definitions, scalar values, photos, documents, signatures, and logging (`logGeneral`, `logException`).
- **Database helper**: Added `getLastInsertId()` method to `lib/database.php`.
- **Upload security**: Created `/uploads/.htaccess` blocking PHP execution in the uploads directory. Subdirectory structure committed with `.gitkeep`. Uploaded files excluded from Git via `.gitignore`.
- **CSS**: Created `css/components/photo_upload.css` for photo thumbnails, document list, signature widget, and field value display styles. Modal and utility styles appended to `css/style.css`.
- **Menu**: Updated `templates/common/menu.php` — Logs menu item added (shows General Log for admins only); hamburger button added for mobile.

- Fix: Add `is_admin` column to `users` table in `db/schema.sql`; regular users vs admin users are now distinguishable.
- Fix: Created `db/migrations/001_add_is_admin_to_users.sql` for existing installations to apply the schema change.
- Fix: Updated `db/seed.sql` to set `is_admin = 1` for Alice, Charlie, and Diana (default users); Bob is a non-admin regular user.
- Fix: Updated `lib/client_helper.php` — `getActiveUser()` and `getAllUsersForClient()` now include `is_admin` field; added `isActiveUserAdmin()` helper returning bool.
- Fix: Updated `admin/items/add.php` — regular (non-admin) users no longer see the "No parent (Root item)" option; admin users see it but are also blocked if the client already has a root item (one root per client rule enforced).
- Fix: Updated `admin/items/edit.php` — regular users cannot promote an item to a root item; admin users are blocked if the client already has a different root item.
- Fix: Updated `admin/users/add.php` — added `is_admin` checkbox to the create user form.
- Fix: Updated `admin/users/edit.php` — added `is_admin` checkbox to the edit user form; `is_admin` is persisted on save.
- Fix: Updated `admin/users/index.php` — added Admin column to the users table so admin status is visible at a glance.

- Replaced brand with Client/User system: "brand" was a theming stub; it is now replaced with proper Clients and Users.
- Added `clients` and `users` tables to `db/schema.sql`; added `client_id` column to `items` table.
- Updated `db/seed.sql` with sample clients, users per client, and items linked to clients.
- Created `lib/client_helper.php`: session-based client and user management (getActiveClient, setActiveClient, getActiveUser, setActiveUser, getAllClients, getAllUsersForClient).
- Created `set_user.php` AJAX endpoint: sets active client or user in session; user change returns a redirect signal to home page.
- Created `admin/clients/` pages (index, add, edit, delete) for managing clients.
- Created `admin/users/` pages (index, add, edit, delete) for managing users (users belong to clients).
- Updated `templates/common/header.php`: shows "Client — User" label with client and user dropdown selectors.
- Updated `templates/common/menu.php`: added Clients and Users navigation items.
- Updated `js/script.js`: client change reloads the page; user change redirects to home page.
- Updated `index.php` (home page): shows the active client's root items in a table.
- Updated `admin/items/index.php`: filters items by the active client.
- Updated `admin/items/add.php`: saves client_id on new items.
- Updated `css/style.css`: added user-selector header styles, form-group/form-check/form-actions styles.

- Fix AI hallucination: removed `brand_id` column, index, and FK from `items` table in `db/schema.sql`. Brand has no relation to inventory items.
- Fix AI hallucination: removed `brand_id` from items INSERT statements in `db/seed.sql`; updated item descriptions to not reference brands.
- Fix AI hallucination: removed brand JOINs and `brand_name` from `LocationHelper::getAllContainers()` and `getDirectChildren()` in `lib/location_helper.php`.
- Fix AI hallucination: removed brand filter dropdown, brand JOIN, and Brand column from `admin/items/index.php`.
- Fix AI hallucination: removed brand dropdown and `brand_id` from form, validation, and INSERT in `admin/items/add.php`.
- Fix AI hallucination: removed brand dropdown and `brand_id` from form, validation, and UPDATE in `admin/items/edit.php`.
- Fix AI hallucination: removed brand JOIN and Brand field display from `admin/items/view.php`.
- Fix AI hallucination: removed brand JOIN and brand name from deletion confirmation in `admin/items/delete.php`.
- Implemented proper brand selector: created `lib/brand_helper.php` with session-based brand management (get brands, get/set active brand).
- Implemented proper brand selector: created `set_brand.php` AJAX endpoint to persist brand selection in session.
- Implemented proper brand selector: updated `templates/common/header.php` to load brands from DB and show DB-driven dropdown in top right; brand selection only affects page theme.
- Updated `js/script.js`: brand change now POSTs to `set_brand.php` via fetch and reloads the page to apply the brand theme.
- Fixed page structure: removed duplicate `</body></html>` closing tags from page files (`index.php`, `view.php`, `delete.php`, `fields.php`); `templates/common/footer.php` already closes the document.

- Initial project setup with refined configuration files.
- INV Location Tracking: added `lib/location_helper.php` with breadcrumb, container listing, descendant traversal, and children helpers.
- INV Location Tracking: created `admin/items/view.php` – item detail page with location breadcrumb and children list.
- INV Location Tracking: created `admin/items/edit.php` – edit item fields and move item to a new parent location (circular-reference protection included).
- INV Location Tracking: created `admin/items/delete.php` – delete confirmation with automatic child reparenting.
- INV Location Tracking: updated `admin/items/add.php` to support parent/location selection; fixed hardcoded CSS paths to use `CSS_PATH`/`BASE_PATH` constants.
- Path fixes: `templates/common/menu.php` converted to PHP; all links now use `BASE_PATH` constant.
- Path fixes: `templates/common/footer.php` compliance link now uses `BASE_PATH` constant.
- Added `css/components/location.css` for breadcrumb and location-tree styles.