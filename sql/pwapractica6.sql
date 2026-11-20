-- ============================================================
--  Hotel Mangle · Sistema de reservas
--  Práctica 6 · Programación Web Avanzada · Grupo 04
--  Base de datos para MySQL 8 / MariaDB 10.4
-- ============================================================

DROP DATABASE IF EXISTS pwapractica6;
CREATE DATABASE pwapractica6 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pwapractica6;

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- Roles
-- ------------------------------------------------------------
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(20) NOT NULL
);

INSERT INTO roles (name) VALUES
    ('Administrator'),
    ('Hotel Manager'),
    ('Receptionist'),
    ('Customer'),
    ('Supplier');

-- ------------------------------------------------------------
-- Usuarios
-- ------------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(100) NOT NULL,
    role_id INT NOT NULL,
    phone VARCHAR(20) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);

-- Las claves están cifradas con password_hash() de PHP (bcrypt).
INSERT INTO users (name, email, password, role_id, phone) VALUES
    ('Ana Zambrano', 'admin@hotelmangle.ec', '$2y$10$NU8ZQXbAmGXBcWgdKKm5Y.A0IDlJ0Me3Tq8AJCx1FlVQcD/yL7pHC', 1, '099 100 1001'),
    ('Carlos Vera', 'gerente@hotelmangle.ec', '$2y$10$ZFne0yjrst8c/rLdtZ.U1.ya/6f0d3Vnw2Ep1RFsFFaz0XAU.Z1yS', 2, '099 100 1002'),
    ('María López', 'recepcion@hotelmangle.ec', '$2y$10$ogvU8PDsn3jNp0dv33XmteAUDJJNco1kvTpEccD0Pu8cQLq8zVw4.', 3, '099 100 1003'),
    ('Jorge Villamar', 'cliente@hotelmangle.ec', '$2y$10$3stsMTFshZkal7cqjAyj9eDq9hcKv9vHClHWaWnNleyDiJHQUBXKq', 4, '098 200 2004'),
    ('Distribuidora Pacífico', 'proveedor@hotelmangle.ec', '$2y$10$omsegt7Wcg487ZThaGN7RuGhjSfJBLGGaGK7iumdnfxz7wlvO/IBW', 5, '097 300 3005'),
    ('Daniela Ortiz', 'daniela@correo.com', '$2y$10$CGNTiLjv0AmlHKJ7CKQGPOef/wijsf2AZrESJxhJ3kR2qAjK/OJbu', 4, '096 400 4006');

-- ------------------------------------------------------------
-- Habitaciones
-- ------------------------------------------------------------
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number INT NOT NULL UNIQUE,
    room_type VARCHAR(20) NOT NULL,
    room_price DECIMAL(10, 2) NOT NULL,
    is_available BOOLEAN NOT NULL DEFAULT TRUE,
    capacity TINYINT NOT NULL DEFAULT 2,
    description VARCHAR(255) NULL,
    image VARCHAR(60) NULL
);

INSERT INTO rooms (room_number, room_type, room_price, is_available, capacity, description, image) VALUES
    (101, 'Doble',   78.00,  TRUE, 2, 'Cama king, balcón lateral y ducha de lluvia.',        'hab1.jpg'),
    (102, 'Doble',   78.00,  TRUE, 2, 'Dos camas individuales, ideal para amigos.',          'hab2.jpg'),
    (103, 'Suite',  126.00,  TRUE, 2, 'Sala pequeña, tina y vista al jardín de mangle.',     'sala.jpg'),
    (104, 'Suite',  132.00,  TRUE, 2, 'Terraza privada con hamaca y cafetera de filtro.',    'sala.jpg'),
    (201, 'Familiar', 148.00, TRUE, 4, 'Dos ambientes, litera y mesa para cuatro.',           'hab2.jpg'),
    (202, 'Familiar', 148.00, TRUE, 4, 'Planta alta, ventanales y ventilador de techo.',      'hab2.jpg'),
    (203, 'Doble',   84.00,  FALSE, 2, 'En mantenimiento: cambio de aire acondicionado.',    'hab1.jpg'),
    (204, 'Suite',  139.00,  TRUE, 3, 'Cama king más sofá cama, baño con tina.',             'sala.jpg');

-- ------------------------------------------------------------
-- Reservas
-- ------------------------------------------------------------
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    room_id INT NOT NULL,
    booking_date DATE NOT NULL,
    check_in_date DATE NOT NULL,
    check_out_date DATE NOT NULL,
    guests TINYINT NOT NULL DEFAULT 1,
    total DECIMAL(10, 2) NOT NULL DEFAULT 0,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id),
    INDEX idx_fechas (room_id, check_in_date, check_out_date)
);

INSERT INTO bookings (user_id, room_id, booking_date, check_in_date, check_out_date, guests, total, status, notes) VALUES
    (4, 1, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY),  DATE_ADD(CURDATE(), INTERVAL 8 DAY),  2, 234.00, 'confirmed', 'Llegada después de las 18:00.'),
    (6, 4, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 DAY),  DATE_ADD(CURDATE(), INTERVAL 3 DAY),  2, 264.00, 'pending',   'Aniversario; preparar botella de agua.'),
    (4, 5, DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 7 DAY), 4, 444.00, 'completed', NULL),
    (6, 2, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 12 DAY), DATE_ADD(CURDATE(), INTERVAL 15 DAY), 1, 234.00, 'confirmed', NULL),
    (6, 6, CURDATE(), CURDATE(), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 2, 296.00, 'confirmed', 'Llegada hoy; preparar cuna.'),
    (6, 5, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), CURDATE(), 2, 296.00, 'confirmed', NULL);

-- ------------------------------------------------------------
-- Provisiones (necesidades del hotel)
-- ------------------------------------------------------------
CREATE TABLE supplies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    quantity INT NOT NULL,
    supplier_id INT NULL,
    status ENUM('requested', 'offered', 'received') NOT NULL DEFAULT 'requested',
    needed_by DATE NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO supplies (name, quantity, supplier_id, status, needed_by, notes) VALUES
    ('Toallas de baño', 40, NULL, 'requested', DATE_ADD(CURDATE(), INTERVAL 6 DAY),  'Algodón 500 g, color arena.'),
    ('Café de altura', 15, NULL, 'requested', DATE_ADD(CURDATE(), INTERVAL 4 DAY),   'Molido medio, origen Loja.'),
    ('Fruta de temporada', 30, NULL, 'requested', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Piña, papaya y banano para el desayuno.'),
    ('Jabón de tocador', 200, 5, 'offered', DATE_ADD(CURDATE(), INTERVAL 8 DAY),     'Caja de 200 unidades, aroma neutro.'),
    ('Fundas biodegradables', 500, 5, 'received', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Entrega recibida en bodega.');

-- ------------------------------------------------------------
-- Ofertas de proveedores
-- ------------------------------------------------------------
CREATE TABLE supply_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supply_id INT NOT NULL,
    supplier_id INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    message VARCHAR(255) NULL,
    status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supply_id) REFERENCES supplies(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO supply_offers (supply_id, supplier_id, price, message, status) VALUES
    (4, 5, 0.85, 'Precio por unidad, entrega en 48 horas.', 'accepted'),
    (5, 5, 0.12, 'Fundas compostables de 30 x 40 cm.', 'accepted'),
    (1, 5, 6.50, 'Toallas de 500 g, juego de tres piezas.', 'pending');
