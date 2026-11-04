-- Seed data for SBCInv
-- public_code must be exactly 10 lowercase hex characters

-- Brands (legacy placeholder; retained for backward compatibility)
INSERT INTO brands (name, description, is_default) VALUES
('Brand A', 'First brand',  0),
('Brand B', 'Second brand', 0),
('Brand C', 'Third brand',  1);

-- Clients
INSERT INTO clients (name, description, is_default) VALUES
('Client A', 'First client',  0),
('Client B', 'Second client', 0),
('Client C', 'Third client',  1);

-- Users (each user belongs to a client)
-- Alice, Charlie, and Diana are designated admins for their respective clients.
-- Bob is a regular user and should not be able to create root items.
INSERT INTO users (client_id, name, is_default, is_admin) VALUES
((SELECT id FROM clients WHERE name = 'Client A'), 'Alice',   1, 1),
((SELECT id FROM clients WHERE name = 'Client A'), 'Bob',     0, 0),
((SELECT id FROM clients WHERE name = 'Client B'), 'Charlie', 1, 1),
((SELECT id FROM clients WHERE name = 'Client C'), 'Diana',   1, 1);

-- Root items (self-referencing; only admin may create root items)
-- Each root item is its own parent (location_item_id = public_code)
-- Items are scoped to a client via client_id.
INSERT INTO items (public_code, name, description, is_container, location_item_id, client_id) VALUES
('a1b2c3d4e5', 'Warehouse A', 'Root container - main warehouse',      1, 'a1b2c3d4e5', (SELECT id FROM clients WHERE name = 'Client A')),
('b2c3d4e5f6', 'Warehouse B', 'Root container - secondary warehouse', 1, 'b2c3d4e5f6', (SELECT id FROM clients WHERE name = 'Client B')),
('c3d4e5f6a7', 'Warehouse C', 'Root container - offsite storage',     1, 'c3d4e5f6a7', (SELECT id FROM clients WHERE name = 'Client C'));

-- Sample child items
INSERT INTO items (public_code, name, description, is_container, location_item_id, client_id) VALUES
('d4e5f6a7b8', 'Item 1', 'Sample item 1 in Warehouse A', 0, 'a1b2c3d4e5', (SELECT id FROM clients WHERE name = 'Client A')),
('e5f6a7b8c9', 'Item 2', 'Sample item 2 in Warehouse A', 0, 'a1b2c3d4e5', (SELECT id FROM clients WHERE name = 'Client A')),
('f6a7b8c9d0', 'Item 3', 'Sample item 3 in Warehouse B', 0, 'b2c3d4e5f6', (SELECT id FROM clients WHERE name = 'Client B')),
('a7b8c9d0e1', 'Item 4', 'Sample item 4 in Warehouse C', 0, 'c3d4e5f6a7', (SELECT id FROM clients WHERE name = 'Client C'));

-- Facility condition review data-driven categories and inspection items
INSERT INTO facility_review_categories (name, description, sort_order) VALUES
('Building Envelope', 'Exterior wall, roof, and window conditions', 10),
('Life Safety', 'Fire, alarm, and emergency system checks', 20),
('Mechanical Systems', 'HVAC, plumbing, and equipment system status', 30);

INSERT INTO facility_review_items (category_id, code, name, description, default_severity, sort_order, is_active)
SELECT id, 'ENV-001', 'Roof Condition', 'Inspect for leaks, flashing, and drainage issues', 'fair', 10, 1
FROM facility_review_categories WHERE name = 'Building Envelope';

INSERT INTO facility_review_items (category_id, code, name, description, default_severity, sort_order, is_active)
SELECT id, 'ENV-002', 'Exterior Walls', 'Check cracks, moisture intrusion, and finish condition', 'good', 20, 1
FROM facility_review_categories WHERE name = 'Building Envelope';

INSERT INTO facility_review_items (category_id, code, name, description, default_severity, sort_order, is_active)
SELECT id, 'LIF-001', 'Fire Alarm System', 'Verify panel status and device coverage', 'good', 10, 1
FROM facility_review_categories WHERE name = 'Life Safety';

INSERT INTO facility_review_items (category_id, code, name, description, default_severity, sort_order, is_active)
SELECT id, 'LIF-002', 'Emergency Lighting', 'Check battery backup and illumination levels', 'fair', 20, 1
FROM facility_review_categories WHERE name = 'Life Safety';

INSERT INTO facility_review_items (category_id, code, name, description, default_severity, sort_order, is_active)
SELECT id, 'MEC-001', 'HVAC Units', 'Review airflow, filters, and operating condition', 'poor', 10, 1
FROM facility_review_categories WHERE name = 'Mechanical Systems';

INSERT INTO facility_review_items (category_id, code, name, description, default_severity, sort_order, is_active)
SELECT id, 'MEC-002', 'Domestic Water', 'Check pressure, leaks, and temperature regulation', 'fair', 20, 1
FROM facility_review_categories WHERE name = 'Mechanical Systems';