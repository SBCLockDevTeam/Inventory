-- Migration 007: Add publicly_viewable to item_fields
-- Controls whether a field's value is shown on the public QR scan page.
-- Defaults to 1 (visible) to preserve existing behaviour for all current fields.

ALTER TABLE item_fields
    ADD COLUMN publicly_viewable TINYINT(1) NOT NULL DEFAULT 1
        AFTER require_printed_name;
