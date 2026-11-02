-- Fresh-database demo setup for MySQL 8.0.16 or newer.
-- This script never drops or truncates data. CREATE TABLE IF NOT EXISTS preserves
-- existing tables; the sample INSERT statements below skip rows with their demo keys.
CREATE DATABASE IF NOT EXISTS hotel_management
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE hotel_management;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'receptionist',
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email),
    KEY users_role_index (role),
    CONSTRAINT users_role_valid CHECK (role IN ('administrator', 'receptionist'))
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS room_types (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(80) NOT NULL,
    description TEXT NULL,
    capacity TINYINT UNSIGNED NOT NULL,
    base_rate DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY room_types_name_unique (name),
    CONSTRAINT room_types_positive_base_rate CHECK (base_rate > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rooms (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    room_type_id BIGINT UNSIGNED NOT NULL,
    room_number VARCHAR(12) NOT NULL,
    floor SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('available', 'maintenance') NOT NULL DEFAULT 'available',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY rooms_room_number_unique (room_number),
    KEY rooms_room_type_status_index (room_type_id, status),
    CONSTRAINT rooms_room_type_id_foreign
        FOREIGN KEY (room_type_id) REFERENCES room_types (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS guests (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    first_name VARCHAR(80) NOT NULL,
    last_name VARCHAR(80) NOT NULL,
    email VARCHAR(190) NOT NULL,
    phone VARCHAR(30) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY guests_email_unique (email),
    KEY guests_name_index (last_name, first_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reservations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    guest_id BIGINT UNSIGNED NOT NULL,
    created_by BIGINT UNSIGNED NULL,
    confirmation_code VARCHAR(16) NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    status ENUM('pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY reservations_confirmation_code_unique (confirmation_code),
    KEY reservations_status_dates_index (status, check_in, check_out),
    CONSTRAINT reservations_guest_id_foreign
        FOREIGN KEY (guest_id) REFERENCES guests (id)
        ON DELETE RESTRICT,
    CONSTRAINT reservations_created_by_foreign
        FOREIGN KEY (created_by) REFERENCES users (id)
        ON DELETE SET NULL,
    CONSTRAINT reservations_valid_dates CHECK (check_out > check_in)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reservation_rooms (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reservation_id BIGINT UNSIGNED NOT NULL,
    room_id BIGINT UNSIGNED NOT NULL,
    nightly_rate DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY reservation_rooms_reservation_room_unique (reservation_id, room_id),
    KEY reservation_rooms_room_id_index (room_id),
    CONSTRAINT reservation_rooms_reservation_id_foreign
        FOREIGN KEY (reservation_id) REFERENCES reservations (id)
        ON DELETE CASCADE,
    CONSTRAINT reservation_rooms_room_id_foreign
        FOREIGN KEY (room_id) REFERENCES rooms (id)
        ON DELETE RESTRICT,
    CONSTRAINT reservation_rooms_positive_nightly_rate CHECK (nightly_rate > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reservation_id BIGINT UNSIGNED NOT NULL,
    recorded_by BIGINT UNSIGNED NULL,
    amount DECIMAL(10, 2) NOT NULL,
    method ENUM('cash', 'card', 'bank_transfer') NOT NULL,
    status ENUM('paid', 'refunded') NOT NULL DEFAULT 'paid',
    paid_at DATETIME NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY payments_reservation_paid_at_index (reservation_id, paid_at),
    CONSTRAINT payments_reservation_id_foreign
        FOREIGN KEY (reservation_id) REFERENCES reservations (id)
        ON DELETE RESTRICT,
    CONSTRAINT payments_recorded_by_foreign
        FOREIGN KEY (recorded_by) REFERENCES users (id)
        ON DELETE SET NULL,
    CONSTRAINT payments_positive_amount CHECK (amount > 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS services (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY services_name_unique (name),
    CONSTRAINT services_nonnegative_price CHECK (price >= 0)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reservation_services (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    reservation_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    quantity SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    unit_price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY reservation_services_reservation_service_unique (reservation_id, service_id),
    CONSTRAINT reservation_services_reservation_id_foreign
        FOREIGN KEY (reservation_id) REFERENCES reservations (id)
        ON DELETE CASCADE,
    CONSTRAINT reservation_services_service_id_foreign
        FOREIGN KEY (service_id) REFERENCES services (id)
        ON DELETE RESTRICT,
    CONSTRAINT reservation_services_positive_quantity CHECK (quantity > 0),
    CONSTRAINT reservation_services_nonnegative_price CHECK (unit_price >= 0)
) ENGINE=InnoDB;

INSERT INTO services (name, description, price)
SELECT 'Breakfast', 'Daily continental breakfast.', 500.00
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Breakfast');

INSERT INTO services (name, description, price)
SELECT 'Airport transfer', 'One-way transfer to the airport.', 1500.00
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Airport transfer');

INSERT INTO services (name, description, price)
SELECT 'Laundry service', 'Standard laundry service per stay.', 700.00
WHERE NOT EXISTS (SELECT 1 FROM services WHERE name = 'Laundry service');

INSERT INTO room_types (name, description, capacity, base_rate)
SELECT 'Single', 'Comfortable room for one guest.', 1, 4500.00
WHERE NOT EXISTS (SELECT 1 FROM room_types WHERE name = 'Single');

INSERT INTO room_types (name, description, capacity, base_rate)
SELECT 'Double', 'Spacious room with one queen bed.', 2, 6500.00
WHERE NOT EXISTS (SELECT 1 FROM room_types WHERE name = 'Double');

INSERT INTO room_types (name, description, capacity, base_rate)
SELECT 'Suite', 'Premium suite with a separate living area.', 4, 11000.00
WHERE NOT EXISTS (SELECT 1 FROM room_types WHERE name = 'Suite');

INSERT INTO rooms (room_type_id, room_number, floor, status)
SELECT id, '101', 1, 'available' FROM room_types
WHERE name = 'Single' AND NOT EXISTS (SELECT 1 FROM rooms WHERE room_number = '101');

INSERT INTO rooms (room_type_id, room_number, floor, status)
SELECT id, '102', 1, 'available' FROM room_types
WHERE name = 'Single' AND NOT EXISTS (SELECT 1 FROM rooms WHERE room_number = '102');

INSERT INTO rooms (room_type_id, room_number, floor, status)
SELECT id, '201', 2, 'available' FROM room_types
WHERE name = 'Double' AND NOT EXISTS (SELECT 1 FROM rooms WHERE room_number = '201');

INSERT INTO rooms (room_type_id, room_number, floor, status)
SELECT id, '202', 2, 'available' FROM room_types
WHERE name = 'Double' AND NOT EXISTS (SELECT 1 FROM rooms WHERE room_number = '202');

INSERT INTO rooms (room_type_id, room_number, floor, status)
SELECT id, '301', 3, 'available' FROM room_types
WHERE name = 'Suite' AND NOT EXISTS (SELECT 1 FROM rooms WHERE room_number = '301');

INSERT INTO guests (first_name, last_name, email, phone)
SELECT 'Mekdes', 'Abebe', 'mekdes.abebe@example.test', '+251 911 234 501'
WHERE NOT EXISTS (SELECT 1 FROM guests WHERE email = 'mekdes.abebe@example.test');

INSERT INTO guests (first_name, last_name, email, phone)
SELECT 'Dawit', 'Tesfaye', 'dawit.tesfaye@example.test', '+251 911 234 502'
WHERE NOT EXISTS (SELECT 1 FROM guests WHERE email = 'dawit.tesfaye@example.test');

INSERT INTO guests (first_name, last_name, email, phone)
SELECT 'Hana', 'Bekele', 'hana.bekele@example.test', '+251 911 234 503'
WHERE NOT EXISTS (SELECT 1 FROM guests WHERE email = 'hana.bekele@example.test');

INSERT INTO reservations (guest_id, confirmation_code, check_in, check_out, status, notes)
SELECT id, 'DEMO1001', DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'confirmed', NULL
FROM guests WHERE email = 'mekdes.abebe@example.test'
AND NOT EXISTS (SELECT 1 FROM reservations WHERE confirmation_code = 'DEMO1001');

INSERT INTO reservations (guest_id, confirmation_code, check_in, check_out, status, notes)
SELECT id, 'DEMO1002', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), 'checked_in', NULL
FROM guests WHERE email = 'dawit.tesfaye@example.test'
AND NOT EXISTS (SELECT 1 FROM reservations WHERE confirmation_code = 'DEMO1002');

INSERT INTO reservations (guest_id, confirmation_code, check_in, check_out, status, notes)
SELECT id, 'DEMO1003', DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'confirmed', NULL
FROM guests WHERE email = 'hana.bekele@example.test'
AND NOT EXISTS (SELECT 1 FROM reservations WHERE confirmation_code = 'DEMO1003');

INSERT INTO reservation_rooms (reservation_id, room_id, nightly_rate)
SELECT reservation.id, room.id, 4500.00 FROM reservations AS reservation
JOIN rooms AS room ON room.room_number = '101'
WHERE reservation.confirmation_code = 'DEMO1001'
AND NOT EXISTS (SELECT 1 FROM reservation_rooms AS rr WHERE rr.reservation_id = reservation.id AND rr.room_id = room.id);

INSERT INTO reservation_rooms (reservation_id, room_id, nightly_rate)
SELECT reservation.id, room.id, 6500.00 FROM reservations AS reservation
JOIN rooms AS room ON room.room_number = '201'
WHERE reservation.confirmation_code = 'DEMO1002'
AND NOT EXISTS (SELECT 1 FROM reservation_rooms AS rr WHERE rr.reservation_id = reservation.id AND rr.room_id = room.id);

INSERT INTO reservation_rooms (reservation_id, room_id, nightly_rate)
SELECT reservation.id, room.id, 11000.00 FROM reservations AS reservation
JOIN rooms AS room ON room.room_number = '301'
WHERE reservation.confirmation_code = 'DEMO1003'
AND NOT EXISTS (SELECT 1 FROM reservation_rooms AS rr WHERE rr.reservation_id = reservation.id AND rr.room_id = room.id);

INSERT INTO reservation_services (reservation_id, service_id, quantity, unit_price)
SELECT reservation.id, service.id, 2, 500.00 FROM reservations AS reservation
JOIN services AS service ON service.name = 'Breakfast'
WHERE reservation.confirmation_code = 'DEMO1003'
AND NOT EXISTS (SELECT 1 FROM reservation_services AS rs WHERE rs.reservation_id = reservation.id AND rs.service_id = service.id);

INSERT INTO payments (reservation_id, amount, method, status, paid_at)
SELECT id, 6500.00, 'card', 'paid', check_in
FROM reservations
WHERE confirmation_code = 'DEMO1002'
AND NOT EXISTS (
    SELECT 1 FROM payments
    WHERE reservation_id = reservations.id
      AND amount = 6500.00
      AND method = 'card'
      AND status = 'paid'
      AND DATE(paid_at) = reservations.check_in
);
