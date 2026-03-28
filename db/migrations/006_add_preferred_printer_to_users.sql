-- Migration 006: Add preferred_printer_id to users table
--
-- Stores each user's preferred printer selection so it persists
-- across browsers and devices (server-side instead of localStorage).
-- NULL means no preference set; the system default printer is used.

ALTER TABLE users
  ADD COLUMN preferred_printer_id BIGINT UNSIGNED NULL DEFAULT NULL
    COMMENT 'FK to printers.id — user''s last chosen printer; NULL = use system default',
  ADD CONSTRAINT fk_users_preferred_printer
    FOREIGN KEY (preferred_printer_id) REFERENCES printers(id) ON DELETE SET NULL;
