-- Brands
INSERT INTO brands (name, description, is_default) VALUES
('Brand A', 'First brand', 0),
('Brand B', 'Second brand', 0),
('Brand C', 'Third brand', 1);

-- Root items (containers)
INSERT INTO items (public_code, brand_id, name, is_container, location_item_id) VALUES
('ROOT0001', 1, 'Warehouse A', 1, 'ROOT0001'),
('ROOT0002', 2, 'Warehouse B', 1, 'ROOT0002'),
('ROOT0003', 3, 'Warehouse C', 1, 'ROOT0003');

-- Sample items in containers
INSERT INTO items (public_code, brand_id, name, description, is_container, location_item_id) VALUES
('ITEM0001', 1, 'Item 1', 'Sample item 1', 0, 'ROOT0001'),
('ITEM0002', 1, 'Item 2', 'Sample item 2', 0, 'ROOT0001'),
('ITEM0003', 2, 'Item 3', 'Sample item 3', 0, 'ROOT0002'),
('ITEM0004', 3, 'Item 4', 'Sample item 4', 0, 'ROOT0003');
