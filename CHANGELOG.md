# Changelog

## Workflow exceptions

(None yet)

## Changes

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