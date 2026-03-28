# API Endpoints

All endpoints live in the `api/` directory and return JSON. All require an authenticated session — an unauthenticated request returns HTTP `401` with `{ "success": false, "error": "Unauthorized" }`.

Requests must be `POST` unless otherwise noted.

---

## Photo Endpoints

### `POST /api/upload_photo.php`

Upload a photo to a `photo`-type dynamic field.

**Request:** `multipart/form-data`

| Parameter | Type | Required | Description |
|---|---|---|---|
| `item_code` | string | Yes | 10-char item `public_code` |
| `field_id` | int | Yes | ID of the `photo` field |
| `photo` | file | Yes | Image file to upload |

**Response (success):**
```json
{
  "success": true,
  "image_id": 42,
  "url": "/qr/uploads/photos/a3f9c12e40_field7_1234567890.jpg"
}
```

**Response (error):**
```json
{ "success": false, "error": "Field not found or wrong type." }
```

**Notes:**
- Validates that the referenced field exists and has `field_type = 'photo'`.
- If `allow_multiple = 0` and a photo already exists for this field+item, the upload is rejected.
- File is saved to `SERVER_ROOT/uploads/photos/`.

---

### `POST /api/delete_photo.php`

Delete an uploaded photo.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `image_id` | int | Yes | ID from `item_images` table |
| `item_code` | string | Yes | Owning item `public_code` (for authorisation) |

**Response:**
```json
{ "success": true }
```

Deletes the file from disk and the row from `item_images`.

---

## Document Endpoints

### `POST /api/upload_document.php`

Upload a document to a `document`-type dynamic field.

**Request:** `multipart/form-data`

| Parameter | Type | Required | Description |
|---|---|---|---|
| `item_code` | string | Yes | 10-char item `public_code` |
| `field_id` | int | Yes | ID of the `document` field |
| `document` | file | Yes | File to upload |

**Response (success):**
```json
{
  "success": true,
  "doc_id": 17,
  "original_filename": "warranty_certificate.pdf",
  "mime_type": "application/pdf",
  "url": "/qr/uploads/documents/a3f9c12e40_field3_1234567890.pdf"
}
```

File is saved to `SERVER_ROOT/uploads/documents/`.

---

### `POST /api/delete_document.php`

Delete an uploaded document.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `doc_id` | int | Yes | ID from `item_documents` table |
| `item_code` | string | Yes | Owning item `public_code` |

**Response:**
```json
{ "success": true }
```

---

## Signature Endpoints

### `POST /api/save_signature.php`

Save a canvas signature capture to a `signature`-type field.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `item_code` | string | Yes | 10-char item `public_code` |
| `field_id` | int | Yes | ID of the `signature` field |
| `signature_data` | string | Yes | Base64-encoded PNG data URL (`data:image/png;base64,...`) |
| `printed_name` | string | No | Signer's printed name (required when `require_printed_name = 1`) |

**Response (success):**
```json
{ "success": true, "signature_id": 5 }
```

Decodes the base64 PNG and saves it to `SERVER_ROOT/uploads/signatures/`. Upserts a row in `item_signatures` (re-capture replaces existing).

---

### `POST /api/delete_signature.php`

Delete a signature.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `signature_id` | int | Yes | ID from `item_signatures` table |
| `item_code` | string | Yes | Owning item `public_code` |

**Response:**
```json
{ "success": true }
```

---

## Field Management Endpoints

### `POST /api/add_field.php`

Add a new dynamic field definition to an item.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `item_code` | string | Yes | 10-char item `public_code` |
| `label` | string | Yes | Display label (e.g. `"Serial Number"`) |
| `field_type` | string | Yes | One of: `text`, `textarea`, `number`, `date`, `checkbox`, `photo`, `document`, `signature` |
| `required` | int | No | `1` = required; default `0` |
| `allow_multiple` | int | No | `1` = multiple uploads; default `0` |
| `instructions` | string | No | Help text shown below the field |
| `require_printed_name` | int | No | `1` = capture signer name (signature fields only); default `0` |

**Response (success):**
```json
{
  "success": true,
  "field_id": 23,
  "field_key": "serial_number",
  "label": "Serial Number",
  "field_type": "text"
}
```

`field_key` is auto-generated from `label` (slugified, collision-suffixed). Admin access is required.

---

### `POST /api/reorder_field.php`

Move a field up or down in its display order.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `item_code` | string | Yes | 10-char item `public_code` |
| `field_id` | int | Yes | Field to move |
| `direction` | string | Yes | `up` or `down` |

**Response:**
```json
{ "success": true }
```

Swaps the `sort_order` value of the target field with its neighbour in the specified direction. Admin access is required.

---

## Printer Endpoints

### `POST /api/print.php`

Send an ESC/P label job to a network printer.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `printer_id` | int | Yes | ID from `printers` table |
| `item_name` | string | Yes | Primary line printed on the label |
| `description` | string | No | Secondary line printed on the label |

**Response (success):**
```json
{ "success": true }
```

**Response (error):**
```json
{ "success": false, "error": "Print failed: Could not connect to printer." }
```

**Flow:**
1. Looks up printer `host` and `port` from the `printers` table.
2. Builds an ESC/P payload (escape codes + label text).
3. Pipes the payload to `bin/printer <host> <port>` via `proc_open()`.
4. The binary resolves the hostname to an IP address (DNS) or connects directly if an IP was provided, then opens a TCP socket and sends the payload.
5. For site printers, `host` is `pierround.com` and `port` is an external port (e.g. `9101`) that the network routes to the correct printer's LAN address on port `9100`. See [admin-guide.md — Printer network architecture](admin-guide.md#printer-network-architecture).
6. Non-zero exit from the binary returns the user-friendly error above; verbose stderr is written to the server error log only.

---

### `POST /api/set_printer.php`

Save the authenticated user's printer preference.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `printer_id` | int | Yes | ID of an active printer |

**Response:**
```json
{ "success": true }
```

Persists `printer_id` to `users.preferred_printer_id`. On subsequent page loads, this printer will be pre-selected in the print UI.

---

## Feedback Endpoint

### `POST /api/ask_changes.php`

Submit a feedback, feature request, or bug report.

| Parameter | Type | Required | Description |
|---|---|---|---|
| `submitter_name` | string | Yes | Name of the person submitting |
| `submitter_email` | string | Yes | Contact email |
| `category` | string | Yes | `feature_request`, `bug_report`, or `feedback` |
| `subject` | string | Yes | Short subject line |
| `message` | string | Yes | Full message body |

**Response (success):**
```json
{ "success": true }
```

Sends an email to the configured admin/support address using the credentials in `config/secrets.php` (`EMAIL_USER`, `EMAIL_PASS`).

---

## Error Response Format

All endpoints return a consistent error envelope on failure:

```json
{
  "success": false,
  "error": "Human-readable error message."
}
```

HTTP status codes used:
- `200` — Success or handled application error (check `success` key)
- `401` — Not authenticated
- `405` — Wrong HTTP method (endpoint requires POST)
