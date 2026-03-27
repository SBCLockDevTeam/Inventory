-- Migration 001: Add is_admin column to users table
-- Run once on existing installations to add admin role support.
-- New installations using schema.sql already include this column.

ALTER TABLE users
  ADD COLUMN is_admin TINYINT(1) NOT NULL DEFAULT 0
  AFTER is_default;
