-- Migration 003: Single-tenant — relax multi-client constraints on users
--
-- The application is now single-tenant. client_id is retained in the
-- schema for backward compatibility but is no longer required or used
-- for filtering.
--
-- 1. Make users.client_id nullable so users can be created without a client.
-- 2. Replace the per-client unique name constraint with a global unique name.
-- 3. Update the FK to SET NULL on client deletion instead of CASCADE.

ALTER TABLE users
    MODIFY COLUMN client_id BIGINT UNSIGNED NULL;

ALTER TABLE users
    DROP FOREIGN KEY fk_users_client;

ALTER TABLE users
    ADD CONSTRAINT fk_users_client
        FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE SET NULL;

ALTER TABLE users
    DROP INDEX uq_users_client_name;

ALTER TABLE users
    ADD UNIQUE KEY uq_users_name (name);
