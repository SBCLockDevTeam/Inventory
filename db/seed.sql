-- Seed data for SBCInv
-- public_code must be exactly 10 lowercase hex characters

-- Brands (brand selector placeholder; no auth yet)
INSERT INTO brands (name, description, is_default) VALUES
('Brand A', 'First brand',  0),
('Brand B', 'Second brand', 0),
('Brand C', 'Third brand',  1);

-- Root items (self-referencing; only admin may create root items)
-- Each root item is its own parent (location_item_id = public_code)
-- Brand is NOT stored on items; it is a session-level theming stub only.
INSERT INTO items (public_code, name, description, is_container, location_item_id) VALUES
('a1b2c3d4e5', 'Warehouse A', 'Root container - main warehouse', 1, 'a1b2c3d4e5'),
('b2c3d4e5f6', 'Warehouse B', 'Root container - secondary warehouse', 1, 'b2c3d4e5f6'),
('c3d4e5f6a7', 'Warehouse C', 'Root container - offsite storage', 1, 'c3d4e5f6a7');

-- Sample child items
INSERT INTO items (public_code, name, description, is_container, location_item_id) VALUES
('d4e5f6a7b8', 'Item 1', 'Sample item 1 in Warehouse A', 0, 'a1b2c3d4e5'),
('e5f6a7b8c9', 'Item 2', 'Sample item 2 in Warehouse A', 0, 'a1b2c3d4e5'),
('f6a7b8c9d0', 'Item 3', 'Sample item 3 in Warehouse B', 0, 'b2c3d4e5f6'),
('a7b8c9d0e1', 'Item 4', 'Sample item 4 in Warehouse C', 0, 'c3d4e5f6a7');