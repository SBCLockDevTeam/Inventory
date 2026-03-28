-- SBCQR Inventory (MariaDB / MySQL)
-- public_code (10-hex) is the PRIMARY KEY for items.
-- Items can be containers. Each item belongs to exactly one container (location_item_id).
-- ROOT items are their own parent (location_item_id = public_code); only admin can create root items.

-- ============================================================
-- BRANDS (legacy — retained for backward compatibility)
-- ============================================================
CREATE TABLE IF NOT EXISTS brands (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(128) NOT NULL UNIQUE,
  description TEXT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- CLIENTS
-- A client is the top-level owner. Users belong to a client.
-- Items are scoped to a client via client_id.
-- ============================================================
CREATE TABLE IF NOT EXISTS clients (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(128) NOT NULL UNIQUE,
  description TEXT NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- USERS
-- A user belongs to a client. The header selector shows "Client - User".
-- Changing the selected user redirects to the home page.
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  client_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(128) NOT NULL,
  email VARCHAR(255) NULL,
  entra_oid VARCHAR(64) NULL,
  is_default TINYINT(1) NOT NULL DEFAULT 0,
  is_admin TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_users_client
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
  UNIQUE KEY uq_users_client_name (client_id, name),
  UNIQUE KEY uq_users_email (email),
  UNIQUE KEY uq_users_entra_oid (entra_oid)
) ENGINE=InnoDB;

-- ============================================================
-- ITEMS
-- Each item is scoped to a client via client_id.
-- ============================================================
CREATE TABLE IF NOT EXISTS items (
  public_code CHAR(10) NOT NULL,
  name VARCHAR(255) NOT NULL,
  description TEXT NULL,
  is_container TINYINT(1) NOT NULL DEFAULT 0,
  location_item_id CHAR(10) NOT NULL,
  client_id BIGINT UNSIGNED NULL,
  primary_image VARCHAR(512) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (public_code),
  KEY idx_items_location (location_item_id),
  KEY idx_items_client (client_id),
  CONSTRAINT fk_items_location
    FOREIGN KEY (location_item_id) REFERENCES items(public_code),
  CONSTRAINT fk_items_client
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- ITEM DYNAMIC FIELDS (defined per item by admin)
-- ============================================================
CREATE TABLE IF NOT EXISTS item_fields (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  item_public_code CHAR(10) NOT NULL,
  field_key VARCHAR(64) NOT NULL,
  label VARCHAR(128) NOT NULL,
  field_type ENUM('text','textarea','number','date','checkbox','photo','document','signature') NOT NULL,
  required TINYINT(1) NOT NULL DEFAULT 0,
  sort_order INT NOT NULL DEFAULT 0,
  allow_multiple TINYINT(1) NOT NULL DEFAULT 0,
  instructions TEXT NULL,
  require_printed_name TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_item_fields_item
    FOREIGN KEY (item_public_code) REFERENCES items(public_code) ON DELETE CASCADE,
  UNIQUE KEY uq_item_field (item_public_code, field_key)
) ENGINE=InnoDB;

-- ============================================================
-- ITEM FIELD VALUES (text, number, date, checkbox)
-- ============================================================
CREATE TABLE IF NOT EXISTS item_field_values (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  item_public_code CHAR(10) NOT NULL,
  field_id BIGINT UNSIGNED NOT NULL,
  value_text TEXT NULL,
  value_number DECIMAL(18,4) NULL,
  value_date DATE NULL,
  value_bool TINYINT(1) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_field_values_item
    FOREIGN KEY (item_public_code) REFERENCES items(public_code) ON DELETE CASCADE,
  CONSTRAINT fk_field_values_field
    FOREIGN KEY (field_id) REFERENCES item_fields(id) ON DELETE CASCADE,
  UNIQUE KEY uq_item_field_value (item_public_code, field_id)
) ENGINE=InnoDB;

-- ============================================================
-- ITEM IMAGES (multi-photo fields)
-- ============================================================
CREATE TABLE IF NOT EXISTS item_images (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  item_public_code CHAR(10) NOT NULL,
  field_id BIGINT UNSIGNED NOT NULL,
  file_path VARCHAR(512) NOT NULL,
  caption VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_item_images (item_public_code, field_id, sort_order),
  CONSTRAINT fk_item_images_item
    FOREIGN KEY (item_public_code) REFERENCES items(public_code) ON DELETE CASCADE,
  CONSTRAINT fk_item_images_field
    FOREIGN KEY (field_id) REFERENCES item_fields(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ITEM DOCUMENTS
-- ============================================================
CREATE TABLE IF NOT EXISTS item_documents (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  item_public_code CHAR(10) NOT NULL,
  field_id BIGINT UNSIGNED NOT NULL,
  file_path VARCHAR(512) NOT NULL,
  original_filename VARCHAR(255) NOT NULL,
  mime_type VARCHAR(128) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_item_documents (item_public_code, field_id),
  CONSTRAINT fk_item_documents_item
    FOREIGN KEY (item_public_code) REFERENCES items(public_code) ON DELETE CASCADE,
  CONSTRAINT fk_item_documents_field
    FOREIGN KEY (field_id) REFERENCES item_fields(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ITEM SIGNATURES
-- ============================================================
CREATE TABLE IF NOT EXISTS item_signatures (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  item_public_code CHAR(10) NOT NULL,
  field_id BIGINT UNSIGNED NOT NULL,
  signature_image_path VARCHAR(512) NOT NULL,
  printed_name VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_item_signatures (item_public_code, field_id),
  CONSTRAINT fk_item_signatures_item
    FOREIGN KEY (item_public_code) REFERENCES items(public_code) ON DELETE CASCADE,
  CONSTRAINT fk_item_signatures_field
    FOREIGN KEY (field_id) REFERENCES item_fields(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- GENERAL LOG (admin-facing, detailed)
-- ============================================================
CREATE TABLE IF NOT EXISTS general_log (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_identifier VARCHAR(255) NULL,
  action_type VARCHAR(64) NOT NULL,
  item_public_code CHAR(10) NULL,
  field_id BIGINT UNSIGNED NULL,
  value_before TEXT NULL,
  value_after TEXT NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_general_log_item (item_public_code),
  KEY idx_general_log_action (action_type)
) ENGINE=InnoDB;

-- ============================================================
-- PRINTERS (label printer definitions, managed in admin)
-- Host may be a domain name (e.g. pierround.com) or an IP address.
-- ============================================================
CREATE TABLE IF NOT EXISTS printers (
  id          BIGINT UNSIGNED  PRIMARY KEY AUTO_INCREMENT,
  name        VARCHAR(128)     NOT NULL UNIQUE,
  host        VARCHAR(255)     NOT NULL,
  port        SMALLINT UNSIGNED NOT NULL DEFAULT 9100,
  is_active   TINYINT(1)       NOT NULL DEFAULT 1,
  is_default  TINYINT(1)       NOT NULL DEFAULT 0,
  sort_order  INT              NOT NULL DEFAULT 0,
  created_at  TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP        NULL     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_printers_active (is_active),
  KEY idx_printers_sort   (sort_order)
) ENGINE=InnoDB;

-- ============================================================
-- EXCEPTIONS LOG (customer-facing, simplified)
-- ============================================================
CREATE TABLE IF NOT EXISTS exceptions_log (
  id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  item_public_code CHAR(10) NULL,
  event_summary VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_exceptions_log_item (item_public_code)
) ENGINE=InnoDB;