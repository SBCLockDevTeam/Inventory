-- Seed data for SBCInv
-- public_code must be exactly 10 lowercase hex characters

-- Brands (brand selector placeholder; no auth yet)
INSERT INTO brands (name, description, is_default) VALUES
('Brand A', 'First brand',  0),
('Brand B', 'Second brand', 0),
('Brand C', 'Third brand',  1);

-- Root items (self-referencing; only admin may create root items)
-- Each root item is its own parent (location_item_id = public_code)
INSERT INTO items (public_code, brand_id, name, description, is_container, location_item_id) VALUES
('a1b2c3d4e5', 1, 'Warehouse A', 'Root container for Brand A', 1, 'a1b2c3d4e5'),
('b2c3d4e5f6', 2, 'Warehouse B', 'Root container for Brand B', 1, 'b2c3d4e5f6'),
('c3d4e5f6a7', 3, 'Warehouse C', 'Root container for Brand C', 1, 'c3d4e5f6a7');

-- Sample child items
INSERT INTO items (public_code, brand_id, name, description, is_container, location_item_id) VALUES
('d4e5f6a7b8', 1, 'Item 1', 'Sample item 1 in Warehouse A', 0, 'a1b2c3d4e5'),
('e5f6a7b8c9', 1, 'Item 2', 'Sample item 2 in Warehouse A', 0, 'a1b2c3d4e5'),
('f6a7b8c9d0', 2, 'Item 3', 'Sample item 3 in Warehouse B', 0, 'b2c3d4e5f6'),
('a7b8c9d0e1', 3, 'Item 4', 'Sample item 4 in Warehouse C', 0, 'c3d4e5f6a7');