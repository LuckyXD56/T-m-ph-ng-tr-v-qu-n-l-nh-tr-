-- Dữ liệu mẫu cho PostgreSQL (bao gồm cập nhật sequence sau khi chèn ID tường minh)
-- Chạy sau schema_pgsql.sql:
-- psql -U postgres -d tro_sinh_vien -f database/seed_pgsql.sql

\i database/seed.sql

SELECT setval(pg_get_serial_sequence('users', 'id'), COALESCE((SELECT MAX(id) FROM users), 1));
SELECT setval(pg_get_serial_sequence('motels', 'id'), COALESCE((SELECT MAX(id) FROM motels), 1));
SELECT setval(pg_get_serial_sequence('rooms', 'id'), COALESCE((SELECT MAX(id) FROM rooms), 1));
SELECT setval(pg_get_serial_sequence('room_images', 'id'), COALESCE((SELECT MAX(id) FROM room_images), 1));
SELECT setval(pg_get_serial_sequence('appointments', 'id'), COALESCE((SELECT MAX(id) FROM appointments), 1));
SELECT setval(pg_get_serial_sequence('contracts', 'id'), COALESCE((SELECT MAX(id) FROM contracts), 1));
SELECT setval(pg_get_serial_sequence('invoices', 'id'), COALESCE((SELECT MAX(id) FROM invoices), 1));
