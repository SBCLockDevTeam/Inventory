-- Migration 005: Add last_seen_at to items
-- This column is updated every time a public QR lookup page (?Q=) is viewed.
ALTER TABLE items
  ADD COLUMN last_seen_at TIMESTAMP NULL DEFAULT NULL
    COMMENT 'Set to NOW() each time the public item page is loaded via QR scan';
