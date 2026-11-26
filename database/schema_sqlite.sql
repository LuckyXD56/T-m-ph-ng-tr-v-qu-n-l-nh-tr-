-- Schema SQLite cho hệ thống Tìm phòng trọ và Quản lý nhà trọ (LTW19)

PRAGMA foreign_keys = ON;

CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role TEXT NOT NULL CHECK (role IN ('admin', 'chu_tro', 'quan_ly', 'sinh_vien')),
    fullname TEXT NOT NULL,
    phone TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    landlord_id INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS motels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    landlord_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    manager_id INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
    name TEXT NOT NULL,
    address TEXT NOT NULL,
    district TEXT NOT NULL,
    latitude REAL NULL DEFAULT 16.054407,
    longitude REAL NULL DEFAULT 108.202167,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    motel_id INTEGER NOT NULL REFERENCES motels(id) ON DELETE CASCADE,
    room_name TEXT NOT NULL,
    price INTEGER NOT NULL CHECK (price > 0),
    area REAL NOT NULL CHECK (area > 0),
    has_ac INTEGER NOT NULL DEFAULT 0,
    has_water_heater INTEGER NOT NULL DEFAULT 0,
    has_parking INTEGER NOT NULL DEFAULT 0,
    electric_price INTEGER NOT NULL DEFAULT 3500,
    water_price INTEGER NOT NULL DEFAULT 15000,
    water_calc_type TEXT NOT NULL DEFAULT 'theo_khoi' CHECK (water_calc_type IN ('theo_khoi', 'theo_nguoi')),
    service_fee INTEGER NOT NULL DEFAULT 100000,
    description TEXT NULL,
    status TEXT NOT NULL DEFAULT 'trong' CHECK (status IN ('trong', 'da_thue', 'bao_tri')),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS room_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL REFERENCES rooms(id) ON DELETE CASCADE,
    image_url TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS appointments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL REFERENCES rooms(id) ON DELETE CASCADE,
    student_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    appointment_date TEXT NOT NULL,
    appointment_time TEXT NOT NULL,
    note TEXT NULL,
    status TEXT NOT NULL DEFAULT 'cho_duyet' CHECK (status IN ('cho_duyet', 'da_duyet', 'huy', 'hoan_thanh')),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS contracts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    room_id INTEGER NOT NULL REFERENCES rooms(id) ON DELETE CASCADE,
    student_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    start_date TEXT NOT NULL,
    end_date TEXT NULL,
    deposit INTEGER NOT NULL DEFAULT 0,
    monthly_rent INTEGER NOT NULL DEFAULT 0,
    electric_price INTEGER NOT NULL DEFAULT 3500,
    water_price INTEGER NOT NULL DEFAULT 15000,
    water_calc_type TEXT NOT NULL DEFAULT 'theo_khoi' CHECK (water_calc_type IN ('theo_khoi', 'theo_nguoi')),
    service_fee INTEGER NOT NULL DEFAULT 100000,
    num_occupants INTEGER NOT NULL DEFAULT 1,
    initial_electric_index INTEGER NOT NULL DEFAULT 0,
    initial_water_index INTEGER NOT NULL DEFAULT 0,
    student_cccd TEXT NULL,
    note TEXT NULL,
    status TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active', 'ended')),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS invoices (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    contract_id INTEGER NOT NULL REFERENCES contracts(id) ON DELETE CASCADE,
    room_id INTEGER NOT NULL REFERENCES rooms(id) ON DELETE CASCADE,
    motel_id INTEGER NOT NULL REFERENCES motels(id) ON DELETE CASCADE,
    student_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    billing_month INTEGER NOT NULL CHECK (billing_month BETWEEN 1 AND 12),
    billing_year INTEGER NOT NULL CHECK (billing_year >= 2020),
    room_price INTEGER NOT NULL DEFAULT 0,
    electric_old INTEGER NOT NULL DEFAULT 0,
    electric_new INTEGER NOT NULL DEFAULT 0,
    electric_usage INTEGER NOT NULL DEFAULT 0,
    electric_unit_price INTEGER NOT NULL DEFAULT 3500,
    electric_total INTEGER NOT NULL DEFAULT 0,
    water_calc_type TEXT NOT NULL DEFAULT 'theo_khoi' CHECK (water_calc_type IN ('theo_khoi', 'theo_nguoi')),
    water_old INTEGER NOT NULL DEFAULT 0,
    water_new INTEGER NOT NULL DEFAULT 0,
    water_usage INTEGER NOT NULL DEFAULT 0,
    water_unit_price INTEGER NOT NULL DEFAULT 15000,
    water_total INTEGER NOT NULL DEFAULT 0,
    service_fee INTEGER NOT NULL DEFAULT 0,
    other_fee INTEGER NOT NULL DEFAULT 0,
    total_amount INTEGER NOT NULL DEFAULT 0,
    note TEXT NULL,
    status TEXT NOT NULL DEFAULT 'chua_thanh_toan' CHECK (status IN ('chua_thanh_toan', 'cho_xac_nhan', 'da_thanh_toan')),
    paid_at DATETIME NULL,
    created_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (contract_id, billing_month, billing_year)
);

CREATE INDEX IF NOT EXISTS idx_motels_landlord ON motels(landlord_id);
CREATE INDEX IF NOT EXISTS idx_motels_manager ON motels(manager_id);
CREATE INDEX IF NOT EXISTS idx_rooms_motel_status ON rooms(motel_id, status);
CREATE INDEX IF NOT EXISTS idx_appointments_room ON appointments(room_id);
CREATE INDEX IF NOT EXISTS idx_contracts_room_status ON contracts(room_id, status);
CREATE INDEX IF NOT EXISTS idx_invoices_motel_period ON invoices(motel_id, billing_year, billing_month);
CREATE INDEX IF NOT EXISTS idx_invoices_student ON invoices(student_id);
