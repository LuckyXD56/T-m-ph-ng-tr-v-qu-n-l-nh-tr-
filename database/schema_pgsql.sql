-- Schema PostgreSQL cho hệ thống Tìm phòng trọ và Quản lý nhà trọ (LTW19)
-- Chạy lệnh: psql -U postgres -d tro_sinh_vien -f database/schema_pgsql.sql

DROP TABLE IF EXISTS invoices CASCADE;
DROP TABLE IF EXISTS contracts CASCADE;
DROP TABLE IF EXISTS appointments CASCADE;
DROP TABLE IF EXISTS room_images CASCADE;
DROP TABLE IF EXISTS rooms CASCADE;
DROP TABLE IF EXISTS motels CASCADE;
DROP TABLE IF EXISTS users CASCADE;

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    role VARCHAR(20) NOT NULL CHECK (role IN ('admin', 'chu_tro', 'quan_ly', 'sinh_vien')),
    fullname VARCHAR(120) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    landlord_id INT NULL REFERENCES users(id) ON DELETE SET NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE motels (
    id SERIAL PRIMARY KEY,
    landlord_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    manager_id INT NULL REFERENCES users(id) ON DELETE SET NULL,
    name VARCHAR(150) NOT NULL,
    address VARCHAR(255) NOT NULL,
    district VARCHAR(80) NOT NULL,
    latitude DOUBLE PRECISION NULL DEFAULT 16.054407,
    longitude DOUBLE PRECISION NULL DEFAULT 108.202167,
    description TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE rooms (
    id SERIAL PRIMARY KEY,
    motel_id INT NOT NULL REFERENCES motels(id) ON DELETE CASCADE,
    room_name VARCHAR(80) NOT NULL,
    price INTEGER NOT NULL CHECK (price > 0),
    area NUMERIC(6,2) NOT NULL CHECK (area > 0),
    has_ac BOOLEAN NOT NULL DEFAULT FALSE,
    has_water_heater BOOLEAN NOT NULL DEFAULT FALSE,
    has_parking BOOLEAN NOT NULL DEFAULT FALSE,
    electric_price INTEGER NOT NULL DEFAULT 3500,
    water_price INTEGER NOT NULL DEFAULT 15000,
    water_calc_type VARCHAR(20) NOT NULL DEFAULT 'theo_khoi' CHECK (water_calc_type IN ('theo_khoi', 'theo_nguoi')),
    service_fee INTEGER NOT NULL DEFAULT 100000,
    description TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'trong' CHECK (status IN ('trong', 'da_thue', 'bao_tri')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE room_images (
    id SERIAL PRIMARY KEY,
    room_id INT NOT NULL REFERENCES rooms(id) ON DELETE CASCADE,
    image_url VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE appointments (
    id SERIAL PRIMARY KEY,
    room_id INT NOT NULL REFERENCES rooms(id) ON DELETE CASCADE,
    student_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    note TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'cho_duyet' CHECK (status IN ('cho_duyet', 'da_duyet', 'huy', 'hoan_thanh')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE contracts (
    id SERIAL PRIMARY KEY,
    room_id INT NOT NULL REFERENCES rooms(id) ON DELETE CASCADE,
    student_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    start_date DATE NOT NULL,
    end_date DATE NULL,
    deposit INTEGER NOT NULL DEFAULT 0,
    monthly_rent INTEGER NOT NULL DEFAULT 0,
    electric_price INTEGER NOT NULL DEFAULT 3500,
    water_price INTEGER NOT NULL DEFAULT 15000,
    water_calc_type VARCHAR(20) NOT NULL DEFAULT 'theo_khoi' CHECK (water_calc_type IN ('theo_khoi', 'theo_nguoi')),
    service_fee INTEGER NOT NULL DEFAULT 100000,
    num_occupants INTEGER NOT NULL DEFAULT 1,
    initial_electric_index INTEGER NOT NULL DEFAULT 0,
    initial_water_index INTEGER NOT NULL DEFAULT 0,
    student_cccd VARCHAR(30) NULL,
    note TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'ended')),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE invoices (
    id SERIAL PRIMARY KEY,
    contract_id INT NOT NULL REFERENCES contracts(id) ON DELETE CASCADE,
    room_id INT NOT NULL REFERENCES rooms(id) ON DELETE CASCADE,
    motel_id INT NOT NULL REFERENCES motels(id) ON DELETE CASCADE,
    student_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    billing_month SMALLINT NOT NULL CHECK (billing_month BETWEEN 1 AND 12),
    billing_year SMALLINT NOT NULL CHECK (billing_year >= 2020),
    room_price INTEGER NOT NULL DEFAULT 0,
    electric_old INTEGER NOT NULL DEFAULT 0,
    electric_new INTEGER NOT NULL DEFAULT 0,
    electric_usage INTEGER NOT NULL DEFAULT 0,
    electric_unit_price INTEGER NOT NULL DEFAULT 3500,
    electric_total INTEGER NOT NULL DEFAULT 0,
    water_calc_type VARCHAR(20) NOT NULL DEFAULT 'theo_khoi' CHECK (water_calc_type IN ('theo_khoi', 'theo_nguoi')),
    water_old INTEGER NOT NULL DEFAULT 0,
    water_new INTEGER NOT NULL DEFAULT 0,
    water_usage INTEGER NOT NULL DEFAULT 0,
    water_unit_price INTEGER NOT NULL DEFAULT 15000,
    water_total INTEGER NOT NULL DEFAULT 0,
    service_fee INTEGER NOT NULL DEFAULT 0,
    other_fee INTEGER NOT NULL DEFAULT 0,
    total_amount INTEGER NOT NULL DEFAULT 0,
    note TEXT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'chua_thanh_toan' CHECK (status IN ('chua_thanh_toan', 'cho_xac_nhan', 'da_thanh_toan')),
    paid_at TIMESTAMP NULL,
    created_by INT NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (contract_id, billing_month, billing_year)
);

CREATE INDEX idx_motels_landlord ON motels(landlord_id);
CREATE INDEX idx_motels_manager ON motels(manager_id);
CREATE INDEX idx_rooms_motel_status ON rooms(motel_id, status);
CREATE INDEX idx_appointments_room ON appointments(room_id);
CREATE INDEX idx_contracts_room_status ON contracts(room_id, status);
CREATE INDEX idx_invoices_motel_period ON invoices(motel_id, billing_year, billing_month);
CREATE INDEX idx_invoices_student ON invoices(student_id);
