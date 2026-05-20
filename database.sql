CREATE DATABASE nikas_restaurant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE nikas_restaurant;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'cashier', 'manager') DEFAULT 'cashier',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,  -- in ₱ Philippine Peso
    cost DECIMAL(10, 2),            -- cost price in ₱
    image_url VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    customer_name VARCHAR(100),
    table_number VARCHAR(20),
    total_amount DECIMAL(10, 2) NOT NULL,    -- subtotal before tax
    tax_amount DECIMAL(10, 2) DEFAULT 0,     -- 12% VAT
    discount_amount DECIMAL(10, 2) DEFAULT 0,
    final_amount DECIMAL(10, 2) NOT NULL,    -- total after tax
    status ENUM('pending', 'preparing', 'ready', 'served', 'cancelled', 'paid') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'gcash', 'paymaya') DEFAULT 'cash',
    notes TEXT,
    user_id INT,  -- cashier who created the order
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,  -- price at time of order
    subtotal DECIMAL(10, 2) NOT NULL,    -- quantity * unit_price
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO users (username, password, full_name, role) VALUES
('admin', 'hashed_password', 'Administrator', 'admin'),
('cashier', 'hashed_password', 'Juan Dela Cruz', 'cashier'),
('manager', 'hashed_password', 'Maria Santos', 'manager');
INSERT INTO categories (name, description, display_order) VALUES
('Appetizers', 'Start your meal right', 1),
('Main Courses', 'Delicious main dishes', 2),
('Desserts', 'Sweet endings', 3),
('Drinks', 'Refreshing beverages', 4),
('Specials', 'Chef''s specials', 5),
('Filipino Dishes', 'Traditional Filipino food', 6);
INSERT INTO products (category_id, name, description, price, cost) VALUES
(1, 'Lumpia Shanghai', 'Crispy spring rolls with pork filling', 120.00, 40.00),
(1, 'Calamari', 'Crispy fried squid rings with garlic mayo', 150.00, 50.00),
(2, 'Crispy Pata', 'Deep fried pork knuckle', 450.00, 180.00),
(2, 'Chicken Inasal', 'Grilled chicken with annatto oil', 220.00, 80.00),
(2, 'Beef Kare-Kare', 'Oxtail stew with peanut sauce', 320.00, 120.00),
(2, 'Sinigang na Baboy', 'Sour pork soup with vegetables', 280.00, 100.00),
(2, 'Adobo', 'Chicken/Pork adobo with rice', 180.00, 60.00),
(3, 'Halo-Halo', 'Mixed shaved ice with fruits and leche flan', 120.00, 35.00),
(3, 'Leche Flan', 'Caramel custard', 80.00, 20.00),
(4, 'Softdrinks (330ml)', 'Coke, Pepsi, Sprite, Royal', 35.00, 10.00),
(4, 'Iced Tea', 'Fresh brewed iced tea', 50.00, 12.00),
(4, 'Fresh Buko Juice', 'Fresh coconut water', 60.00, 15.00),
(5, 'Chef Special Seafood Platter', 'Mixed seafood grill for 2-3 persons', 650.00, 250.00),
(6, 'Sisig', 'Sizzling chopped pork with onions and chili', 220.00, 80.00),
(6, 'Kwek-Kwek', 'Deep fried quail eggs in orange batter', 75.00, 25.00);
