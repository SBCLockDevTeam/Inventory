# Changelog

## Workflow exceptions

(None yet)

## Changes

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