-- Brands
INSERT INTO brands (id, name) VALUES
(1, 'Brand A'),
(2, 'Brand B'),
(3, 'Brand C');

-- Items
INSERT INTO items (id, name, brand_id, price) VALUES
(1, 'Item 1', 1, 9.99),
(2, 'Item 2', 1, 19.99),
(3, 'Item 3', 2, 29.99),
(4, 'Item 4', 3, 39.99);

-- Sample Data
INSERT INTO sample_data (item_id, quantity, created_at) VALUES
(1, 100, '2026-03-25 05:14:18'),
(2, 150, '2026-03-25 05:14:18'),
(3, 200, '2026-03-25 05:14:18');