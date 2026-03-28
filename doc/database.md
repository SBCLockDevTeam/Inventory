# Database Reference

Database engine: **MariaDB 10.x**  
Database name: **`SBCInv`**  
Character set: **`utf8mb4`**  
Canonical schema: **`db/schema.sql`**

---

## Table Overview

| Table | Purpose |
|---|---|
| `items` | Inventory items (the core entity) |
| `item_fields` | Dynamic field definitions per item |
| `item_field_values` | Scalar field values (text, number, date, checkbox) |
| `item_images` | Photo uploads per field |
| `item_documents` | Document uploads per field |
| `item_signatures` | Canvas signature captures per field |
| `users` | Authenticated user accounts |
| `clients` | Tenant record (single-tenant — one row in production) |
| `brands` | Legacy (retained for backward compatibility) |
| `printers` | Network label printer definitions |
| `general_log` | Admin-facing audit log |
| `exceptions_log` | Simplified user-facing error log |

---

## Table Definitions

### `items`

The central entity. Each row represents one physical (or logical) item.

| Column | Type | Notes |
|---|---|---|
| `public_code` | `CHAR(10)` PK | 10-char lowercase hex; embedded in QR label URL |
| `name` | `VARCHAR(255)` NOT NULL | Human-readable item name |
| `description` | `TEXT` | Optional free-text description |
| `is_container` | `TINYINT(1)` | `1` = can hold child items; `0` = leaf item |
| `location_item_id` | `CHAR(10)` NOT NULL | FK → `items.public_code` (parent container). Root items self-reference. |
| `client_id` | `BIGINT UNSIGNED` | FK → `clients.id` ON DELETE SET NULL |
| `primary_image` | `VARCHAR(512)` | Relative file path to primary display photo |
| `last_seen_at` | `TIMESTAMP` | Updated each time the public QR URL is scanned |
| `created_at` | `TIMESTAMP` | Row creation timestamp |
| `updated_at` | `TIMESTAMP` | Auto-updated on any row change |

**Indexes:** `idx_items_location (location_item_id)`, `idx_items_client (client_id)`

**Hierarchy rules:**
- Root item: `location_item_id = public_code` (self-reference). Only admins can create root items.
- All other items: `location_item_id` points to a container item.
- Circular references are blocked by application logic in `lib/location_helper.php`.

---

### `item_fields`

Admin-defined field definitions attached to a specific item. Determines which input fields appear on the item's edit/view pages.

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | |
| `item_public_code` | `CHAR(10)` NOT NULL | FK → `items.public_code` ON DELETE CASCADE |
| `field_key` | `VARCHAR(64)` NOT NULL | URL-safe slug derived from label; unique per item |
| `label` | `VARCHAR(128)` NOT NULL | Display label shown to users |
| `field_type` | ENUM | `text`, `textarea`, `number`, `date`, `checkbox`, `photo`, `document`, `signature` |
| `required` | `TINYINT(1)` | If `1`, value must be provided when editing |
| `sort_order` | `INT` | Display order (drag-to-reorder via admin UI) |
| `allow_multiple` | `TINYINT(1)` | For `photo`/`document` fields: allow more than one upload |
| `instructions` | `TEXT` | Optional help text shown below the field |
| `require_printed_name` | `TINYINT(1)` | For `signature` fields: capture signer's printed name |
| `created_at` | `TIMESTAMP` | |
| `updated_at` | `TIMESTAMP` | |

**Unique key:** `uq_item_field (item_public_code, field_key)`

See [dynamic-fields.md](dynamic-fields.md) for behaviour details.

---

### `item_field_values`

Stores scalar values for `text`, `textarea`, `number`, `date`, and `checkbox` fields. Exactly one row per `(item_public_code, field_id)` pair — INSERT or UPDATE (upsert pattern).

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | |
| `item_public_code` | `CHAR(10)` NOT NULL | FK → `items.public_code` ON DELETE CASCADE |
| `field_id` | `BIGINT UNSIGNED` NOT NULL | FK → `item_fields.id` ON DELETE CASCADE |
| `value_text` | `TEXT` | Used for `text`, `textarea` |
| `value_number` | `DECIMAL(18,4)` | Used for `number` |
| `value_date` | `DATE` | Used for `date` |
| `value_bool` | `TINYINT(1)` | Used for `checkbox` |
| `created_at` | `TIMESTAMP` | |
| `updated_at` | `TIMESTAMP` | |

**Unique key:** `uq_item_field_value (item_public_code, field_id)`

---

### `item_images`

Stores uploaded photos for `photo` fields. Multiple rows allowed per field (when `item_fields.allow_multiple = 1`).

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | |
| `item_public_code` | `CHAR(10)` NOT NULL | FK → `items.public_code` ON DELETE CASCADE |
| `field_id` | `BIGINT UNSIGNED` NOT NULL | FK → `item_fields.id` ON DELETE CASCADE |
| `file_path` | `VARCHAR(512)` NOT NULL | Path relative to `SERVER_ROOT/uploads/photos/` |
| `caption` | `VARCHAR(255)` | Optional photo caption |
| `sort_order` | `INT` | Display order |
| `created_at` | `TIMESTAMP` | |

**Index:** `idx_item_images (item_public_code, field_id, sort_order)`

---

### `item_documents`

Stores uploaded documents for `document` fields. Multiple rows allowed per field.

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | |
| `item_public_code` | `CHAR(10)` NOT NULL | FK → `items.public_code` ON DELETE CASCADE |
| `field_id` | `BIGINT UNSIGNED` NOT NULL | FK → `item_fields.id` ON DELETE CASCADE |
| `file_path` | `VARCHAR(512)` NOT NULL | Server-side storage path |
| `original_filename` | `VARCHAR(255)` NOT NULL | Original filename as uploaded |
| `mime_type` | `VARCHAR(128)` | Detected MIME type |
| `created_at` | `TIMESTAMP` | |

---

### `item_signatures`

Stores canvas-based signature captures for `signature` fields. One row per field (re-capture overwrites).

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | |
| `item_public_code` | `CHAR(10)` NOT NULL | FK → `items.public_code` ON DELETE CASCADE |
| `field_id` | `BIGINT UNSIGNED` NOT NULL | FK → `item_fields.id` ON DELETE CASCADE |
| `signature_image_path` | `VARCHAR(512)` NOT NULL | Path to PNG file on server |
| `printed_name` | `VARCHAR(255)` | Signer's name (when `require_printed_name = 1`) |
| `created_at` | `TIMESTAMP` | |

---

### `users`

User accounts. Users authenticate via Microsoft Entra ID; the `entra_oid` column is the primary match key.

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | |
| `client_id` | `BIGINT UNSIGNED` NOT NULL | FK → `clients.id` ON DELETE CASCADE |
| `name` | `VARCHAR(128)` NOT NULL | Display name (must be unique per client) |
| `email` | `VARCHAR(255)` UNIQUE | Microsoft email address — required for Entra login |
| `entra_oid` | `VARCHAR(64)` UNIQUE | Microsoft Object ID — set on first successful Entra login |
| `is_default` | `TINYINT(1)` | Legacy — retained for BC |
| `is_admin` | `TINYINT(1)` | `1` = full admin privileges |
| `preferred_printer_id` | `BIGINT UNSIGNED` | FK → `printers.id` ON DELETE SET NULL; user's last-chosen printer |
| `created_at` | `TIMESTAMP` | |
| `updated_at` | `TIMESTAMP` | |

**Unique keys:** `uq_users_client_name (client_id, name)`, `uq_users_email (email)`, `uq_users_entra_oid (entra_oid)`

---

### `clients`

Tenant record. The application is single-tenant in production (one row).

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | |
| `name` | `VARCHAR(128)` NOT NULL UNIQUE | |
| `description` | `TEXT` | |
| `is_default` | `TINYINT(1)` | `1` for the active tenant row |
| `created_at` | `TIMESTAMP` | |
| `updated_at` | `TIMESTAMP` | |

---

### `brands`

Legacy table retained for backward compatibility. Not used in new functionality.

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | |
| `name` | `VARCHAR(128)` NOT NULL UNIQUE | |
| `description` | `TEXT` | |
| `is_default` | `TINYINT(1)` | |
| `created_at` | `TIMESTAMP` | |
| `updated_at` | `TIMESTAMP` | |

---

### `printers`

Network label printer definitions. Managed via Admin → Printers.

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | |
| `name` | `VARCHAR(128)` NOT NULL UNIQUE | Human-readable label (e.g. "Front Desk Printer") |
| `host` | `VARCHAR(255)` NOT NULL | Hostname or IP address (e.g. `pierround.com` or `192.168.1.10`) |
| `port` | `SMALLINT UNSIGNED` | TCP port (default `9100`; ESC/P standard) |
| `is_active` | `TINYINT(1)` | `0` = hidden from print UI |
| `is_default` | `TINYINT(1)` | `1` = pre-selected when user has no saved preference |
| `sort_order` | `INT` | Display order in admin list |
| `created_at` | `TIMESTAMP` | |
| `updated_at` | `TIMESTAMP` | |

**Indexes:** `idx_printers_active (is_active)`, `idx_printers_sort (sort_order)`

---

### `general_log`

Full audit trail. Written for every create/update/delete operation and field-value change.

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | |
| `user_identifier` | `VARCHAR(255)` | Username or email of acting user |
| `action_type` | `VARCHAR(64)` | Action keyword (e.g. `item_created`, `field_value_updated`) |
| `item_public_code` | `CHAR(10)` | Related item code (NULL for system events) |
| `field_id` | `BIGINT UNSIGNED` | Related field ID (NULL for non-field events) |
| `value_before` | `TEXT` | Previous value (NULL for creates) |
| `value_after` | `TEXT` | New value (NULL for deletes) |
| `notes` | `TEXT` | Free-text supplemental context |
| `created_at` | `TIMESTAMP` | |

**Indexes:** `idx_general_log_item (item_public_code)`, `idx_general_log_action (action_type)`

---

### `exceptions_log`

Simplified error log surfaced to non-technical admin users.

| Column | Type | Notes |
|---|---|---|
| `id` | `BIGINT UNSIGNED` PK | |
| `item_public_code` | `CHAR(10)` | Related item, if applicable |
| `event_summary` | `VARCHAR(255)` NOT NULL | Short description of what went wrong |
| `created_at` | `TIMESTAMP` | |

---

## Entity-Relationship Summary

```
clients ─── users (many)
         └─ items (many, via client_id)

items ─── item_fields (many)        [cascade delete]
       └─ items (children, via location_item_id)

item_fields ─── item_field_values   [cascade delete]
            ├── item_images         [cascade delete]
            ├── item_documents      [cascade delete]
            └── item_signatures     [cascade delete]

printers ─── users (preferred_printer_id, SET NULL on delete)
```

---

## Migrations

Migrations live in `db/migrations/` and are numbered sequentially. Apply them in order on existing installations when pulling new code that includes a migration.

| File | Change |
|---|---|
| `001_add_is_admin_to_users.sql` | Adds `is_admin` column to `users` |
| `002_add_entra_auth_to_users.sql` | Adds `email` and `entra_oid` columns + unique indexes to `users` |
| `003_single_tenant.sql` | Removes multi-tenant artefacts; consolidates to single `clients` row |
| `004_add_printers.sql` | Creates `printers` table and inserts default printer rows |
| `005_add_last_seen_to_items.sql` | Adds `last_seen_at` timestamp to `items` |
| `006_add_preferred_printer_to_users.sql` | Adds `preferred_printer_id` FK to `users` |

**Apply a migration:**
```bash
mysql -u SBCInv -p SBCInv < /var/www/html/sbcqr/qr/db/migrations/00N_name.sql
```

Fresh installs should use `db/schema.sql` only (it already incorporates all migrations).
