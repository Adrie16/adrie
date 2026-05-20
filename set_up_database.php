<?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>Setup Database - Nikas Restaurant</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .card { max-width: 900px; margin: 0 auto; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        pre { background: #f1f3f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .step { padding: 10px; margin: 10px 0; border-left: 4px solid #007bff; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='card'>
            <div class='card-header bg-primary text-white'>
                <h3><i class='fas fa-database'></i> Nikas Restaurant POS - Database Setup</h3>
            </div>
            <div class='card-body'>";

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'nikas_restaurant';

// Create connection
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("<div class='alert alert-danger'><i class='fas fa-exclamation-circle'></i> Connection failed: " . $conn->connect_error . "</div></div></div></body></html>");
}

echo "<div class='step'>
        <h4><i class='fas fa-cogs'></i> Step 1: Creating Database</h4>";

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "<p class='success'><i class='fas fa-check'></i> Database '$dbname' created successfully</p>";
} else {
    echo "<p class='error'><i class='fas fa-times'></i> Error creating database: " . $conn->error . "</p>";
}

// Select database
if (!$conn->select_db($dbname)) {
    echo "<p class='error'><i class='fas fa-times'></i> Error selecting database: " . $conn->error . "</p>";
    exit;
}

echo "</div><div class='step'>
        <h4><i class='fas fa-table'></i> Step 2: Creating Tables</h4>";

// SQL for creating tables
$tables = array(
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        role ENUM('admin', 'cashier', 'manager') DEFAULT 'cashier',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
    
    "categories" => "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        display_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
    
    "products" => "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        category_id INT,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        cost DECIMAL(10, 2),
        image_url VARCHAR(255),
        is_available BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
    
    "orders" => "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(20) UNIQUE NOT NULL,
        customer_name VARCHAR(100),
        table_number VARCHAR(20),
        total_amount DECIMAL(10, 2) NOT NULL,
        tax_amount DECIMAL(10, 2) DEFAULT 0,
        discount_amount DECIMAL(10, 2) DEFAULT 0,
        final_amount DECIMAL(10, 2) NOT NULL,
        status ENUM('pending', 'preparing', 'ready', 'served', 'cancelled', 'paid') DEFAULT 'pending',
        payment_method ENUM('cash', 'card', 'gcash', 'paymaya') DEFAULT 'cash',
        notes TEXT,
        user_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
    
    "order_items" => "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        product_id INT,
        quantity INT NOT NULL,
        unit_price DECIMAL(10, 2) NOT NULL,
        subtotal DECIMAL(10, 2) NOT NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB"
);

// Create tables
foreach ($tables as $table_name => $sql) {
    if ($conn->query($sql) === TRUE) {
        echo "<p class='success'><i class='fas fa-check'></i> Table '$table_name' created</p>";
    } else {
        echo "<p class='error'><i class='fas fa-times'></i> Error creating '$table_name': " . $conn->error . "</p>";
    }
}

// Add foreign keys after tables are created
$foreign_keys = array(
    "ALTER TABLE products ADD FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL",
    "ALTER TABLE orders ADD FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL",
    "ALTER TABLE order_items ADD FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE",
    "ALTER TABLE order_items ADD FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL"
);

foreach ($foreign_keys as $fk) {
    if ($conn->query($fk) !== TRUE) {
        echo "<p class='text-warning'><i class='fas fa-exclamation-triangle'></i> Note: Foreign key constraint: " . $conn->error . "</p>";
    }
}

echo "</div><div class='step'>
        <h4><i class='fas fa-users'></i> Step 3: Creating Users</h4>";

// Insert users with hashed passwords
$admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
$cashier_pass = password_hash('cashier123', PASSWORD_DEFAULT);
$manager_pass = password_hash('manager123', PASSWORD_DEFAULT);

$users_sql = "INSERT IGNORE INTO users (username, password, full_name, role) VALUES
    ('admin', '$admin_pass', 'Administrator', 'admin'),
    ('cashier', '$cashier_pass', 'Juan Dela Cruz', 'cashier'),
    ('manager', '$manager_pass', 'Maria Santos', 'manager')";

if ($conn->query($users_sql) === TRUE) {
    $affected = $conn->affected_rows;
    echo "<p class='success'><i class='fas fa-check'></i> $affected user(s) created</p>";
} else {
    echo "<p class='error'><i class='fas fa-times'></i> Error creating users: " . $conn->error . "</p>";
}

echo "</div><div class='step'>
        <h4><i class='fas fa-utensils'></i> Step 4: Creating Categories</h4>";

$categories_sql = "INSERT IGNORE INTO categories (name, description, display_order) VALUES
    ('Appetizers', 'Start your meal right', 1),
    ('Main Courses', 'Delicious main dishes', 2),
    ('Desserts', 'Sweet endings', 3),
    ('Drinks', 'Refreshing beverages', 4),
    ('Specials', 'Chef''s specials', 5),
    ('Filipino Dishes', 'Traditional Filipino food', 6)";

if ($conn->query($categories_sql) === TRUE) {
    $affected = $conn->affected_rows;
    echo "<p class='success'><i class='fas fa-check'></i> $affected categories created</p>";
} else {
    echo "<p class='error'><i class='fas fa-times'></i> Error creating categories: " . $conn->error . "</p>";
}

echo "</div><div class='step'>
        <h4><i class='fas fa-hamburger'></i> Step 5: Creating Products</h4>";

$products_sql = "INSERT IGNORE INTO products (category_id, name, description, price, cost) VALUES
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
    (6, 'Kwek-Kwek', 'Deep fried quail eggs in orange batter', 75.00, 25.00)";

if ($conn->query($products_sql) === TRUE) {
    $affected = $conn->affected_rows;
    echo "<p class='success'><i class='fas fa-check'></i> $affected products created</p>";
} else {
    echo "<p class='error'><i class='fas fa-times'></i> Error creating products: " . $conn->error . "</p>";
}

echo "</div><div class='step'>
        <h4><i class='fas fa-clipboard-check'></i> Step 6: Verification</h4>";

// Verify data
echo "<div class='row'>";
$tables_to_check = array('users', 'categories', 'products');
foreach ($tables_to_check as $table) {
    $result = $conn->query("SELECT COUNT(*) as count FROM $table");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "<div class='col-md-4'>
                <div class='card mb-3'>
                    <div class='card-body text-center'>
                        <h5>$table</h5>
                        <h3 class='text-primary'>" . $row['count'] . "</h3>
                        <small>records</small>
                    </div>
                </div>
              </div>";
    }
}
echo "</div>";

echo "</div>
    <div class='alert alert-success mt-4'>
        <h4><i class='fas fa-check-circle'></i> Setup Complete!</h4>
        <p>Database has been successfully set up with sample data.</p>
        <hr>
        <h5>Login Credentials:</h5>
        <div class='row'>
            <div class='col-md-4'>
                <div class='card'>
                    <div class='card-body'>
                        <h6>Administrator</h6>
                        <p><strong>Username:</strong> admin</p>
                        <p><strong>Password:</strong> admin123</p>
                    </div>
                </div>
            </div>
            <div class='col-md-4'>
                <div class='card'>
                    <div class='card-body'>
                        <h6>Cashier</h6>
                        <p><strong>Username:</strong> cashier</p>
                        <p><strong>Password:</strong> cashier123</p>
                    </div>
                </div>
            </div>
            <div class='col-md-4'>
                <div class='card'>
                    <div class='card-body'>
                        <h6>Manager</h6>
                        <p><strong>Username:</strong> manager</p>
                        <p><strong>Password:</strong> manager123</p>
                    </div>
                </div>
            </div>
        </div>
        <div class='mt-3'>
            <a href='login.php' class='btn btn-primary btn-lg'><i class='fas fa-sign-in-alt'></i> Go to Login</a>
            <a href='index.php' class='btn btn-success btn-lg'><i class='fas fa-tachometer-alt'></i> Go to Dashboard</a>
        </div>
    </div>";

echo "<h5>Database Information:</h5>
<pre>
Host: $host
Database: $dbname
Username: $user
Currency: " . CURRENCY . " (Philippine Peso)
</pre>";

$conn->close();

echo "</div></div></div></body></html>";
?>