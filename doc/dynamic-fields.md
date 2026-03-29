# Dynamic Fields

Dynamic fields let admins attach custom data capture to any item without modifying code. A field definition is stored in `item_fields`; its values are stored in one of several value tables depending on the field type.

---

## Field Types

| Type | Description | Storage table | Multiple values? |
|---|---|---|---|
| `text` | Single-line text input | `item_field_values.value_text` | No |
| `textarea` | Multi-line text area | `item_field_values.value_text` | No |
| `number` | Numeric input (decimal allowed) | `item_field_values.value_number` | No |
| `date` | Date picker | `item_field_values.value_date` | No |
| `checkbox` | Boolean checkbox | `item_field_values.value_bool` | No |
| `photo` | Image upload widget | `item_images` | Optional (`allow_multiple`) |
| `document` | File upload widget | `item_documents` | Optional (`allow_multiple`) |
| `signature` | Canvas signature capture | `item_signatures` | No (re-capture replaces) |

---

## Field Properties

Defined in `item_fields` per field:

| Property | Column | Description |
|---|---|---|
| Label | `label` | Display name shown above the input |
| Field key | `field_key` | URL-safe slug; auto-generated from label (e.g. `serial_number`). Unique per item. |
| Type | `field_type` | See table above |
| Required | `required` | If `1`, the edit form enforces a non-empty value |
| Display order | `sort_order` | Controls the order fields appear; drag-to-reorder in admin UI |
| Allow multiple | `allow_multiple` | For `photo`/`document`: allow more than one file per field |
| Instructions | `instructions` | Optional help text displayed below the field label |
| Require printed name | `require_printed_name` | For `signature` fields: capture a text "printed name" alongside the signature |
| Publicly Viewable | `publicly_viewable` | If `1` (default), the field value is shown on the public QR scan page. Admins can toggle this per field on the edit page. |

---

## Managing Fields (Admin)

### Adding a field

Navigate to an item → click **Manage Fields**. Fill in:

- **Label** — Human-readable field name (e.g. "Serial Number")
- **Field type** — Select from the type list
- **Required** — Check if the field must be filled before saving
- **Allow multiple** — For photo/document fields; enables multiple uploads
- **Instructions** — Optional helper text
- **Require printed name** — For signature fields

Submitting calls `api/add_field.php`, which inserts a row into `item_fields` and returns the new `field_id`.

### Reordering fields

On the **Manage Fields** page (`items/fields.php`), drag rows up or down. Each drag calls `api/reorder_field.php`, which swaps `sort_order` values between the moved field and its neighbour.

### Deleting a field

Click the delete icon next to a field on the **Manage Fields** page (`items/field_delete.php`). This removes the `item_fields` row; all dependent value rows are deleted by cascading FK constraints in the database.

---

## Scalar Field Behaviour (text / textarea / number / date / checkbox)

Scalar values are edited on the item's **Edit** page (`items/edit.php`). The form submits all scalar fields together with the core item details.

On save:
1. `FieldHelper::saveScalarValue($item_code, $field_id, $field_type, $raw_value)` is called for each scalar field.
2. The helper performs an `INSERT … ON DUPLICATE KEY UPDATE` (upsert) on `item_field_values`.
3. If a field is `required` and the value is empty, the form renders an error and does not save.

---

## Photo Field Behaviour

Photos are uploaded asynchronously via AJAX — the page does not reload.

**Upload flow:**
1. User clicks the camera or file-picker button in the photo widget.
2. JavaScript (`js/lib/photo_capture.js`) POSTs the file to `api/upload_photo.php` with `field_id` and `item_code`.
3. The API validates the file, saves it to `uploads/photos/`, inserts a row in `item_images`, and returns `{ success: true, image_id, url }`.
4. JavaScript appends a thumbnail with a delete button to the widget.

**Delete flow:**
- User clicks the delete (×) button on a thumbnail.
- JavaScript POSTs to `api/delete_photo.php` with `image_id` and `item_code`.
- The API deletes the file from disk and the row from `item_images`.

**allow_multiple behaviour:**
- If `allow_multiple = 0` and a photo already exists for the field, the upload button is hidden after the first upload.
- If `allow_multiple = 1`, the upload button remains visible regardless of how many photos have been uploaded.

---

## Document Field Behaviour

Documents follow the same asynchronous AJAX pattern as photos.

- Upload endpoint: `api/upload_document.php` → saves to `uploads/documents/`
- Delete endpoint: `api/delete_document.php`
- Original filename and MIME type are stored in `item_documents` for display and download.

---

## Signature Field Behaviour

Signatures use an HTML5 Canvas widget (`js/lib/signature_capture.js`).

**Capture flow:**
1. User draws a signature in the canvas panel.
2. If `require_printed_name = 1`, a text field for the signer's name is also shown.
3. On submit, JavaScript base64-encodes the canvas as a PNG data URL and POSTs to `api/save_signature.php` with `field_id`, `item_code`, `signature_data`, and optionally `printed_name`.
4. The API decodes the base64 PNG, saves it to `uploads/signatures/`, and upserts a row in `item_signatures`.

**Re-capture:** Re-submitting the widget overwrites the existing row (there is at most one signature per field per item). The old PNG file is deleted from disk.

**Delete flow:**
- POSTs to `api/delete_signature.php` with `signature_id` and `item_code`.

---

## Reading Field Values

### In PHP pages

```php
$fields  = FieldHelper::getFields($item_public_code);
$scalars = FieldHelper::getScalarValues($item_public_code);   // keyed by field_id
$photos  = FieldHelper::getAllPhotos($item_public_code);       // keyed by field_id
$docs    = FieldHelper::getAllDocuments($item_public_code);    // keyed by field_id
$sigs    = FieldHelper::getAllSignatures($item_public_code);   // keyed by field_id
```

`$fields` is an ordered array (by `sort_order`). Each element contains all `item_fields` columns. Pages iterate `$fields` and look up the corresponding value from the relevant keyed array.

### Public QR view

`index.php` calls the same helpers for any item code in `?Q=`. All field types are rendered read-only. No authentication is required.

---

## Field Key Generation

When a new field is added via `api/add_field.php`:

1. The label is lower-cased and non-alphanumeric characters are replaced with underscores (e.g. `"Serial Number"` → `"serial_number"`).
2. The database is checked for an existing field with that `field_key` on the same item.
3. If a collision exists, a numeric suffix is appended (e.g. `serial_number_2`).

The `field_key` is not currently exposed in URLs or API calls — `field_id` (integer) is used for all lookups. The key is available for future export/import scenarios.
