# Changelog

## Workflow exceptions

(None yet)

## Changes

- Initial project setup with refined configuration files.
- INV Location Tracking: added `lib/location_helper.php` with breadcrumb, container listing, descendant traversal, and children helpers.
- INV Location Tracking: created `admin/items/view.php` – item detail page with location breadcrumb and children list.
- INV Location Tracking: created `admin/items/edit.php` – edit item fields and move item to a new parent location (circular-reference protection included).
- INV Location Tracking: created `admin/items/delete.php` – delete confirmation with automatic child reparenting.
- INV Location Tracking: updated `admin/items/add.php` to support parent/location selection; fixed hardcoded CSS paths to use `CSS_PATH`/`BASE_PATH` constants.
- Path fixes: `templates/common/menu.php` converted to PHP; all links now use `BASE_PATH` constant.
- Path fixes: `templates/common/footer.php` compliance link now uses `BASE_PATH` constant.
- Added `css/components/location.css` for breadcrumb and location-tree styles.