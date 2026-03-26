# Changelog

All notable changes to the SBC Inventory project are documented here.

---

## [Unreleased]

### Added
- `.agentic.yml` — project rules and constraints file
- `.gitignore` — excludes `config/secrets.php`, logs, uploads, system files
- `config/settings.php` — non-sensitive application settings
- `config/secrets.php.example` — template for server-side secrets file
- `db/schema.sql` — full MariaDB database schema
- `db/seed.sql` — seed data (default brands, admin user, etc.)
- `lib/bootstrap.php` — application bootstrap: settings loader, DB accessor, error helpers, exception logger
- `lib/database.php` — Database class with prepared-statement helpers (queryOne, queryAll, queryCount, execute)
- `lib/logger.php` — Logger class for writing structured log entries
- `templates/common/header.php` — site-wide HTML header template
- `templates/common/footer.php` — site-wide HTML footer template
- `templates/common/menu.php` — primary navigation menu template
- `templates/common/brand_selector.php` — brand dropdown component (session persistence)
- `templates/common/error_division.php` — error/warning/notice banner template
- `css/style.css` — global styles, design tokens, buttons, forms, cards, tables, utility classes
- `css/layout.css` — structural layout: header, nav menu, footer, responsive breakpoints
- `js/lib/ajax_helpers.js` — reusable AJAX/fetch utility functions
- `js/lib/form_validation.js` — client-side form validation helpers
- `js/pages/ask_for_changes.js` — JS for the Ask for Changes feedback form
- `index.php` — application entry point / dashboard page

---

## Workflow exceptions

*(No exceptions recorded yet.)*