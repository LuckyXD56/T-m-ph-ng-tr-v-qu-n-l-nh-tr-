-- Dữ liệu mẫu (Mật khẩu mặc định của tất cả tài khoản: 123456)
-- Bcrypt hash cho '123456': $2y$12$K06rcl7BHkdcJab9tQwFi.RTTx4h1X.3N4IrCM/9hpNDMQS9QIsny

INSERT INTO users (id, role, fullname, phone, password, landlord_id, is_active) VALUES
(1, 'admin', 'Quản trị hệ thống', '0900000001', '$2y$12$K06rcl7BHkdcJab9tQwFi.RTTx4h1X.3N4IrCM/9hpNDMQS9QIsny', NULL, TRUE),
(2, 'chu_tro', 'Nguyễn Văn Hùng (Chủ trọ)', '0901111111', '$2y$12$K06rcl7BHkdcJab9tQwFi.RTTx4h1X.3N4IrCM/9hpNDMQS9QIsny', NULL, TRUE),
(3, 'quan_ly', 'Trần Thị Mai (QL Khu A)', '0902222222', '$2y$12$K06rcl7BHkdcJab9tQwFi.RTTx4h1X.3N4IrCM/9hpNDMQS9QIsny', 2, TRUE),
(4, 'quan_ly', 'Lê Minh Tuấn (QL Khu B)', '0903333333', '$2y$12$K06rcl7BHkdcJab9tQwFi.RTTx4h1X.3N4IrCM/9hpNDMQS9QIsny', 2, TRUE),
(5, 'sinh_vien', 'Phimmasone Khamphouvanh', '0904444444', '$2y$12$K06rcl7BHkdcJab9tQwFi.RTTx4h1X.3N4IrCM/9hpNDMQS9QIsny', NULL, TRUE),
(6, 'sinh_vien', 'Trương Nguyễn Ngọc Phú', '0905555555', '$2y$12$K06rcl7BHkdcJab9tQwFi.RTTx4h1X.3N4IrCM/9hpNDMQS9QIsny', NULL, TRUE),
(7, 'sinh_vien', 'Nguyễn Quý Minh Hoàng', '0906666666', '$2y$12$K06rcl7BHkdcJab9tQwFi.RTTx4h1X.3N4IrCM/9hpNDMQS9QIsny', NULL, TRUE);

INSERT INTO motels (id, landlord_id, manager_id, name, address, district, latitude, longitude, description) VALUES
(1, 2, 3, 'Khu Trọ Bình Minh - ĐHSP', '459 Tôn Đức Thắng, Hòa Khánh Nam', 'Liên Chiểu', 16.0609, 108.1583, 'Gần Đại học Sư phạm và Bách khoa Đà Nẵng, an ninh 24/7, camera hành lang, giờ giấc tự do.'),
(2, 2, 4, 'Nhà Trọ Xanh - Hải Châu', '128 Nguyễn Văn Linh', 'Hải Châu', 16.0601, 108.2156, 'Trung tâm thành phố, gần chợ và bến xe buýt, có thang máy và khu để xe rộng.'),
(3, 2, 3, 'Chung Cư Mini Hòa Khánh', '72 Phạm Như Xương', 'Liên Chiểu', 16.0672, 108.1510, 'Phòng mới xây, có gác lửng, wifi tốc độ cao, phù hợp sinh viên ở ghép 2-3 người.');

INSERT INTO rooms (id, motel_id, room_name, price, area, has_ac, has_water_heater, has_parking, electric_price, water_price, water_calc_type, service_fee, description, status) VALUES
(1, 1, 'P101', 2200000, 22.0, TRUE, TRUE, TRUE, 3500, 15000, 'theo_khoi', 100000, 'Phòng tầng 1 thoáng mát, cửa sổ lớn, đầy đủ điều hòa, nóng lạnh, kệ bếp.', 'da_thue'),
(2, 1, 'P102', 2000000, 20.0, TRUE, FALSE, TRUE, 3500, 15000, 'theo_khoi', 100000, 'Phòng sạch sẽ, có sẵn giường và tủ quần áo, gần cổng chính.', 'trong'),
(3, 1, 'P201', 2500000, 25.0, TRUE, TRUE, TRUE, 3500, 50000, 'theo_nguoi', 120000, 'Phòng ban công rộng, có máy lạnh Inverter tiết kiệm điện, chỗ phơi đồ riêng.', 'trong'),
(4, 2, 'P301', 3200000, 28.0, TRUE, TRUE, TRUE, 3800, 18000, 'theo_khoi', 150000, 'Phòng cao cấp trung tâm Hải Châu, nội thất cơ bản, khóa vân tay.', 'da_thue'),
(5, 2, 'P302', 2900000, 24.0, TRUE, TRUE, TRUE, 3800, 18000, 'theo_khoi', 150000, 'Phòng yên tĩnh, thích hợp học tập và làm việc, gần siêu thị.', 'trong'),
(6, 3, 'P401', 1800000, 18.0, FALSE, TRUE, TRUE, 3500, 50000, 'theo_nguoi', 80000, 'Phòng giá sinh viên, có nóng lạnh và gác lửng rộng rãi.', 'trong');

INSERT INTO room_images (room_id, image_url) VALUES
(1, 'uploads/rooms/room_6b07b1b602d23.jpg'),
(2, 'uploads/rooms/room_6b07b1b602d23.jpg');

INSERT INTO appointments (id, room_id, student_id, appointment_date, appointment_time, note, status) VALUES
(1, 2, 5, '2026-11-28', '09:30', 'Em muốn xem phòng P102 vào buổi sáng ạ.', 'cho_duyet'),
(2, 5, 7, '2026-11-29', '14:00', 'Xem phòng để chuyển vào đầu tháng sau.', 'da_duyet');

INSERT INTO contracts (id, room_id, student_id, start_date, end_date, deposit, monthly_rent, electric_price, water_price, water_calc_type, service_fee, num_occupants, initial_electric_index, initial_water_index, student_cccd, note, status) VALUES
(1, 1, 5, '2026-09-01', '2027-09-01', 2200000, 2200000, 3500, 15000, 'theo_khoi', 100000, 2, 1200, 45, '048204001234', 'Hợp đồng 12 tháng, đóng tiền từ ngày 01-05 hằng tháng.', 'active'),
(2, 4, 6, '2026-10-01', '2027-10-01', 3200000, 3200000, 3800, 18000, 'theo_khoi', 150000, 1, 500, 20, '048204005678', 'Hợp đồng 12 tháng, bao gồm phí thang máy & rác.', 'active');

INSERT INTO invoices (id, contract_id, room_id, motel_id, student_id, billing_month, billing_year, room_price, electric_old, electric_new, electric_usage, electric_unit_price, electric_total, water_calc_type, water_old, water_new, water_usage, water_unit_price, water_total, service_fee, other_fee, total_amount, note, status, paid_at, created_by) VALUES
(1, 1, 1, 1, 5, 10, 2026, 2200000, 1200, 1310, 110, 3500, 385000, 'theo_khoi', 45, 53, 8, 15000, 120000, 100000, 0, 2805000, 'Hóa đơn tháng 10/2026', 'da_thanh_toan', '2026-11-03 10:15:00', 3),
(2, 1, 1, 1, 5, 11, 2026, 2200000, 1310, 1405, 95, 3500, 332500, 'theo_khoi', 53, 60, 7, 15000, 105000, 100000, 0, 2737500, 'Hóa đơn tháng 11/2026', 'chua_thanh_toan', NULL, 3),
(3, 2, 4, 2, 6, 11, 2026, 3200000, 500, 580, 80, 3800, 304000, 'theo_khoi', 20, 25, 5, 18000, 90000, 150000, 0, 3744000, 'Hóa đơn tháng 11/2026', 'cho_xac_nhan', NULL, 4);
