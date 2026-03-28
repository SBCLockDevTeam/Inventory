# Developer Guide

This guide covers conventions, helper APIs, and processes for developers working on the codebase.

---

## Code Organisation Principles

The project follows strict separation of concerns with no framework:

| Layer | Location | Rule |
|---|---|---|
| Business logic | `lib/` | All reusable PHP in helper classes |
| Page controllers | `items/`, `admin/`, `auth/` | Thin — call helpers, render HTML |
| API endpoints | `api/` | Return JSON, check auth, call helpers |
| Templates | `templates/` | HTML only — no business logic |
| Styles | `css/` | CSS only — no inline styles in PHP/HTML |
| Scripts | `js/` | JS only — no inline scripts in PHP/HTML |
| Config | `config/` | Constants only — no secrets in Git |

**DRY rule:** If the same logic appears in two places, it belongs in `lib/`.

---

## PHP Helper Reference

### `DatabaseHelper` (`lib/database.php`)

PDO wrapper. All queries go through this class — never use raw PDO or string-concatenated SQL.

```php
// Fetch all rows
$rows = DatabaseHelper::queryAll(
    "SELECT * FROM items WHERE client_id = ?",
    [$client_id]
);

// Fetch one row (returns null if not found)
$item = DatabaseHelper::queryOne(
    "SELECT * FROM items WHERE public_code = ?",
    [$code]
);

// Fetch a count
$count = DatabaseHelper::queryCount(
    "SELECT COUNT(*) FROM items WHERE location_item_id = ?",
    [$parent_code]
);

// INSERT / UPDATE / DELETE
DatabaseHelper::execute(
    "UPDATE items SET name = ?, updated_at = NOW() WHERE public_code = ?",
    [$name, $code]
);

// Generate a unique 10-hex item code
$code = DatabaseHelper::generateUniqueCode();

// Transactions
DatabaseHelper::beginTransaction();
try {
    DatabaseHelper::execute(...);
    DatabaseHelper::execute(...);
    DatabaseHelper::commit();
} catch (Exception $e) {
    DatabaseHelper::rollback();
    throw $e;
}
```

`init()` is called lazily on first use — the PDO connection is created once per request.

---

### `AuthHelper` (`lib/auth_helper.php`)

OAuth2 PKCE authentication. Used by pages and API endpoints.

```php
// Guard a page — redirects to login if not authenticated
AuthHelper::requireAuth();           // called in templates/common/header.php

// Check without redirecting (for API endpoints)
if (!AuthHelper::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get the authenticated user's session data
$auth = AuthHelper::getAuthUser();
// $auth['user_id'], $auth['is_admin'], $auth['email'], $auth['display_name'], etc.
```

See [authentication.md](authentication.md) for full flow documentation.

---

### `ClientHelper` (`lib/client_helper.php`)

User state helpers.

```php
$user = ClientHelper::getActiveUser();      // Returns session user record or null
$isAdmin = ClientHelper::isActiveUserAdmin(); // Returns bool
```

---

### `FormHelper` (`lib/form_helpers.php`)

Input sanitisation and validation. Always use this for any user input.

```php
// Retrieve and sanitise POST/GET values
$name     = FormHelper::getPost('name', '');       // default if missing
$code     = FormHelper::getGet('Q', '');

// Manual sanitisation
$safe = FormHelper::sanitize($raw_input);          // htmlspecialchars ENT_QUOTES UTF-8

// Validation helpers
FormHelper::isRequired($value);                    // non-empty check
FormHelper::isValidHex10($value);                  // valid item public_code
FormHelper::isValidEmail($value);                  // valid email format
FormHelper::isValidInt($value);                    // integer check
FormHelper::isValidNumeric($value);                // numeric check (incl. decimal)
```

---

### `FieldHelper` (`lib/field_helper.php`)

Dynamic field management. Used by edit/view pages and all upload API endpoints.

```php
// Get all field definitions for an item (ordered by sort_order)
$fields = FieldHelper::getFields($item_public_code);

// Get scalar values (text/number/date/checkbox) keyed by field_id
$scalars = FieldHelper::getScalarValues($item_public_code);

// Upsert a scalar value
FieldHelper::saveScalarValue($item_code, $field_id, $field_type, $raw_value);

// Photos
$photos = FieldHelper::getAllPhotos($item_public_code);           // all photos, keyed by field_id
$fieldPhotos = FieldHelper::getPhotos($item_public_code, $field_id); // photos for one field
FieldHelper::savePhoto($item_code, $field_id, $file_path, $caption);
FieldHelper::deletePhoto($image_id, $item_public_code);

// Documents — same pattern
$docs = FieldHelper::getAllDocuments($item_public_code);
FieldHelper::saveDocument($item_code, $field_id, $file_path, $original_filename, $mime_type);
FieldHelper::deleteDocument($doc_id, $item_public_code);

// Signatures — same pattern
$sigs = FieldHelper::getAllSignatures($item_public_code);
FieldHelper::saveSignature($item_code, $field_id, $image_path, $printed_name);
FieldHelper::deleteSignature($signature_id, $item_public_code);

// Logging
FieldHelper::logGeneral($user_identifier, $action_type, $item_code, $field_id, $before, $after, $notes);
FieldHelper::logException($item_code, $event_summary);
```

---

### `LocationHelper` (`lib/location_helper.php`)

Item hierarchy traversal.

```php
// Walk up from item to root; returns ordered array of ancestor items (root first)
$breadcrumb = LocationHelper::getLocationBreadcrumb($item_public_code);

// All container items (optionally exclude codes)
$containers = LocationHelper::getAllContainers([$exclude_code1, $exclude_code2]);

// Direct children of a container
$children = LocationHelper::getDirectChildren($parent_public_code);

// Check if moving an item to a new parent would create a circular reference
$wouldBeCircular = LocationHelper::checkCircularReference($item_code, $new_parent_code);
```

---

### `TreeHelper` (`lib/tree_helper.php`)

Build and render the full item tree.

```php
// Build a nested tree structure from all items
$tree = TreeHelper::buildTree();

// Render the tree as HTML <ul> with JS expand/collapse hooks
TreeHelper::renderTree($tree, '/qr/items/view.php', 0);
```

---

## Adding a New Page

1. Create the PHP file in the appropriate directory (`items/`, `admin/`, etc.).
2. Start every protected page with the header template (which includes the auth guard):
   ```php
   <?php require_once __DIR__ . '/../templates/common/header.php'; ?>
   ```
3. For admin-only pages, add an admin check after the header:
   ```php
   if (!ClientHelper::isActiveUserAdmin()) {
       header('Location: ' . BASE_PATH . '/home.php');
       exit;
   }
   ```
4. End every page with the footer template:
   ```php
   <?php require_once __DIR__ . '/../templates/common/footer.php'; ?>
   ```

---

## Adding a New API Endpoint

1. Create `api/my_endpoint.php`.
2. Start with the standard auth check:
   ```php
   <?php
   require_once __DIR__ . '/../config/settings.php';
   require_once LIB_PATH . 'auth_helper.php';

   header('Content-Type: application/json');

   if (!AuthHelper::isAuthenticated()) {
       http_response_code(401);
       echo json_encode(['success' => false, 'error' => 'Unauthorized']);
       exit;
   }

   if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
       http_response_code(405);
       echo json_encode(['success' => false, 'error' => 'Method not allowed']);
       exit;
   }
   ```
3. Use `FormHelper::getPost()` for all input.
4. Use `DatabaseHelper` for all queries.
5. Return `echo json_encode(['success' => true, ...])` or `echo json_encode(['success' => false, 'error' => '...'])`.

---

## Adding a Database Migration

1. Create `db/migrations/00N_description.sql` where `N` is the next number in sequence.
2. Write the migration as idempotent SQL where possible (e.g. `ALTER TABLE … ADD COLUMN IF NOT EXISTS …`).
3. Update `db/schema.sql` to reflect the final schema state after the migration.
4. Add a `CHANGELOG.md` entry describing the change and the apply command.
5. Document the apply command in the pull request / changelog so the server operator knows to run it.

---

## JavaScript Conventions

- **No frameworks** — vanilla ES5/ES6 only.
- Page-specific scripts live in `js/pages/`. They are included only on the page that needs them.
- Reusable UI widgets live in `js/lib/` (currently: `photo_capture.js`, `signature_capture.js`).
- Global utilities (dropdowns, modals, menus) live in `js/script.js`.
- AJAX calls use `fetch()` or `XMLHttpRequest`. Always check `response.success` in the JSON — do not rely solely on HTTP status codes.

---

## CSS Conventions

- **No frameworks** — vanilla CSS only.
- Global styles in `css/style.css`.
- Component-specific overrides in `css/components/` (e.g. `tree.css`, `table.css`).
- No inline `style` attributes in PHP/HTML.

---

## Git Workflow

- **Branch:** `main` only — no feature branches or pull requests for day-to-day work.
- **AI writes first:** AI agents commit to `main`; the server operator then pulls to the server.
- **Never commit:**
  - `config/secrets.php`
  - `bin/printer` (compiled binary)
  - `uploads/` (user data)
- **After pulling to server:** always check `CHANGELOG.md` for migration or recompile steps.

---

## Security Checklist

When adding or modifying any feature:

- [ ] All database queries use `DatabaseHelper` with prepared statements — no string interpolation in SQL.
- [ ] All user input displayed in HTML is passed through `FormHelper::sanitize()`.
- [ ] All new API endpoints check `AuthHelper::isAuthenticated()` at the top.
- [ ] Admin-only features check `ClientHelper::isActiveUserAdmin()`.
- [ ] No credentials or secrets appear in any tracked file.
- [ ] File uploads are validated (type checks, size limits) before saving.
