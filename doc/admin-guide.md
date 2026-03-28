# Admin Guide

This guide covers all admin-only features available under the **Admin** menu.

> Admin access is determined by the `is_admin` flag on the user's account. All admin pages check `ClientHelper::isActiveUserAdmin()` and redirect non-admin users away.

---

## User Management (`Admin → Users`)

### Adding a user

1. Go to **Admin → Users → Add User**.
2. Fill in:
   - **Name** — Display name (must be unique within the tenant).
   - **Email** — The user's Microsoft 365 email address. This must exactly match what Microsoft sends in the ID token. The user cannot log in without this.
   - **Is Admin** — Check to grant full admin privileges.
3. Click **Save**.

The new user can now log in by visiting the site and clicking **Sign in with Microsoft**.

On first login, the system matches the user by email and writes their `entra_oid` to the record. Subsequent logins are matched by `entra_oid`.

### Editing a user

Go to **Admin → Users**, find the user, click **Edit**. You can change their name, email, or admin status.

> If a user's Microsoft email address changes, update it here. Until the email is updated, the user will fail to log in (their `entra_oid` may still match, but if `entra_oid` has not yet been written, only email matching is available).

### Deleting a user

Go to **Admin → Users**, click **Delete** next to the user. This removes the user record. The user immediately loses access. Any items they created remain in the system.

---

## Printer Management (`Admin → Printers`)

Printers are network label printers that receive ESC/P jobs over TCP.

### Printer network architecture

The printers are on a private LAN behind a Comcast gateway and a Ubiquiti UDM Pro router. A print job travels the following path:

```
Application server
  → pierround.com : 9101   (TCP)
  → Comcast gateway         (NAT: forwards port 9101–9150 inbound to UDM Pro)
  → UDM Pro                 (port-based routing: maps each external port to one printer's LAN IP on port 9100)
  → Printer  192.168.x.x : 9100
```

**Key points:**

- `pierround.com` is used **only as a reverse-DNS hostname** that always resolves to the Comcast gateway's current public IP. The Ubiquiti UDM Pro keeps this record updated via its dynamic DNS integration. It is not a print server or a web URL.
- The **Comcast gateway** accepts inbound TCP on the port range 9101–9150 and NATs each connection to the UDM Pro's LAN address.
- The **UDM Pro** performs port-based forwarding: the destination port number determines which printer receives the job. For example, external port `9101` maps to Printer 1's static LAN IP, `9102` maps to Printer 2, and so on. All printers listen on port `9100` locally.
- Each printer entry in the `printers` table therefore stores `host = pierround.com` and a unique `port` (9101, 9102, 9103 …) that identifies it through the UDM Pro routing rules.

**Troubleshooting connectivity:**

| Symptom | Likely cause |
|---|---|
| All printers fail | `pierround.com` DNS not resolving / Comcast IP changed and DDNS not yet updated |
| One printer fails | That printer's UDM Pro port-forwarding rule is wrong, or the printer is offline/power-cycled |
| Intermittent failures | DDNS propagation lag after a Comcast IP change |

To verify the current public IP: `dig pierround.com +short`  
To verify a port reaches the printer: `nc -zv pierround.com 9101`

### Adding a printer

1. Go to **Admin → Printers → Add Printer**.
2. Fill in:
   - **Name** — Label shown in the print UI (e.g. `Front Desk Printer`).
   - **Hostname or URL** — Use `pierround.com` for all printers on the site LAN (see architecture above). An IP address can be used for a directly reachable printer.
   - **Port** — The **external** port that the UDM Pro maps to this printer (e.g. `9101`, `9102`, `9103`). Each printer must have a unique port.
   - **Active** — Uncheck to hide this printer from users without deleting it.
   - **Default** — Check to make this printer the default selection for users who have no saved preference.
3. Click **Save**.

### Editing a printer

Go to **Admin → Printers**, click **Edit**. You can change any property including host and port.

### Deleting a printer

Go to **Admin → Printers**, click **Delete**. If any users have this printer saved as their preference (`users.preferred_printer_id`), that field is set to `NULL` (ON DELETE SET NULL). Those users will fall back to the system default printer.

### Printer selection logic (user-facing)

When a user opens an item's print panel, the printer dropdown pre-selects in this priority order:

1. The user's saved preference (`users.preferred_printer_id`).
2. The system default printer (`printers.is_default = 1`).
3. The first active printer in sort order.

When the user changes the selection, the choice is saved to the server automatically via `api/set_printer.php`.

---

## Log Viewer (`Admin → Logs`)

### General Log (`admin/logs/index.php`)

Every create, update, and delete operation is recorded here. Each entry shows:

- **User** — Who performed the action (`user_identifier` from the session).
- **Action type** — A short keyword (e.g. `item_created`, `field_value_updated`).
- **Item** — The `public_code` of the affected item (clickable link).
- **Field** — The `field_id` of the affected field (if applicable).
- **Before** — Previous value.
- **After** — New value.
- **Notes** — Any supplemental context.
- **Timestamp** — When the action occurred.

The log is searchable by user, action type, and item code.

### Exceptions Log (`admin/logs/exceptions.php`)

A simplified log of error events. Each entry shows:

- **Item** — The affected item (if applicable).
- **Summary** — A short description of what went wrong.
- **Timestamp**.

This log is intended for non-technical review; detailed error information is written to the PHP/Apache error log.

---

## Item Management (Admin vs. Regular Users)

Most item operations are available to all authenticated users. The following actions are **admin-only**:

| Action | Restriction |
|---|---|
| Create a **root item** (item with no parent / self-referencing parent) | Admin only |
| Add/edit/delete **dynamic fields** for an item | Admin only |
| Reorder dynamic fields | Admin only |
| Access the **Admin** menu at all | Admin only |

Regular users can:

- Browse the item tree.
- Create items **inside existing containers**.
- Edit item details (name, description, location, scalar field values).
- Upload photos, documents, and signatures.
- Print labels.
- Clone items.

---

## Cloning an Item (`items/clone.php`)

Any authenticated user can clone an item. Cloning creates:

- A new item with a new `public_code`.
- Copies of all `item_fields` definitions (field types, labels, settings).
- The cloned item starts with **no field values** — all uploads and scalar values are empty.

The clone is placed at the root by default; the user can move it to a container by editing it after creation.

---

## Ask for Changes (Feedback Modal)

Any authenticated user can submit feedback, a feature request, or a bug report using the **Ask for Changes** button. This opens a modal where the user fills in their name, email, category, subject, and message. The submission is sent to the admin email address configured in `config/secrets.php` via `api/ask_changes.php`.

---

## QR Labels and Printing

### Printing a label

1. Open an item's detail page (`items/view.php`).
2. Select a printer from the dropdown.
3. Click **Print Label**.
4. The system sends the item name and description as an ESC/P job to the selected printer.

The print request goes through `api/print.php` → `bin/printer` C binary → TCP connection to the printer's host and port.

### What is printed

The current label format is:

- Line 1: Item name
- Line 2: Item description (if provided)
- The QR code itself is **not** printed by the software — it must be pre-printed on the label stock or generated separately. The label text identifies the item that the QR code refers to.

> The QR code on a label encodes the URL `https://sbcqr.com/qr/?Q=<public_code>`. Labels can be generated externally (e.g. with a QR code generator) and the URL is the value to encode.
