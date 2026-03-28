-- Migration 004: Add printers table
--
-- Stores label printer definitions (name, host, port).
-- Host may be a hostname/domain (e.g. pierround.com) or an IP address.
-- A single printer can be marked as default; the UI pre-selects it
-- but each user's current choice is persisted in localStorage.

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

-- Pre-populate the three known printers.
-- NOTE: Update host/port values to match your actual network configuration.
INSERT INTO printers (name, host, port, is_active, is_default, sort_order) VALUES
('Office Label Printer', 'pierround.com', 9101, 1, 1, 1),
('MBR Label Printer',    'pierround.com', 9102, 1, 0, 2),
('Kitchen Label Printer','pierround.com', 9103, 1, 0, 3);
