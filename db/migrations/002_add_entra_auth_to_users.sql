-- Migration 002: Add Microsoft Entra ID authentication columns to users table
-- Run once on existing installations to support Entra ID login.
-- New installations using schema.sql already include these columns.

ALTER TABLE users
  ADD COLUMN email VARCHAR(255) NULL AFTER name,
  ADD COLUMN entra_oid VARCHAR(64) NULL AFTER email,
  ADD UNIQUE KEY uq_users_email (email),
  ADD UNIQUE KEY uq_users_entra_oid (entra_oid);
