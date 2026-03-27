# Changelog

## Workflow exceptions

(None yet)

## Changes

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