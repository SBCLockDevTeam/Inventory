# SBC QR Inventory — Documentation

This directory contains comprehensive technical and operational documentation for the SBC QR Inventory system.

---

## Documents

| Document | Description |
|---|---|
| [architecture.md](architecture.md) | System architecture, tech stack, and directory layout |
| [database.md](database.md) | Full database schema reference (tables, columns, relationships) |
| [authentication.md](authentication.md) | Microsoft Entra ID / OAuth2 PKCE authentication flow |
| [dynamic-fields.md](dynamic-fields.md) | Dynamic fields system — types, behavior, and upload widgets |
| [api.md](api.md) | All AJAX/JSON API endpoints |
| [deployment.md](deployment.md) | Server setup, installation, and deployment guide |
| [admin-guide.md](admin-guide.md) | Admin user guide (users, printers, logs, items) |
| [development.md](development.md) | Developer guide (PHP helpers, conventions, migrations) |

---

## System at a Glance

The SBC QR Inventory system is a PHP/MariaDB web application that lets staff track physical items using printed QR code labels. Key capabilities:

- **Hierarchical inventory** — Items live inside containers; the full tree is browseable and searchable.
- **Dynamic fields** — Admins define per-item custom fields (text, photos, documents, signatures, etc.) without touching code.
- **QR scanning** — Any QR-code scan opens a public read-only detail page (no login required).
- **Label printing** — Authenticated users send ESC/P label jobs to network printers directly from the browser.
- **Microsoft Entra ID login** — All write operations require authentication via the organisation's Microsoft 365 tenant.

See [architecture.md](architecture.md) for a deeper overview.
