<?php
// Quick Login Fix for Nikas Restaurant POS
echo "<!DOCTYPE html>
<html>
<head>
    <title>Fix Login - Nikas Restaurant</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .card { max-width: 800px; margin: 0 auto; }
        pre { background: #f1f3f4; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class='card'>
        <div class='card-header bg-primary text-white'>
            <h3>Nikas Restaurant POS - Login Fix</h3>
        </div>
        <div class='card-body'>";

// Step 1: Create config.php if not exists
$config_content = '<?php
// Nikas Restaurant POS - Configuration
define("DB_HOST", "localhost");
define("DB_NAME", "nikas_restaurant");
define("DB_USER", "root");
define("DB_PASS", "");
define("APP_NAME", "Nikas Restaurant POS");
define("APP_VERSION", "2.0");
define("TAX_RATE", 0.12);
define("CURRENCY", "₱");
define("SITE_URL", "http://localhost/nikas_restaurant_pos/");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set("display_errors", 1);
date_default_timezone_set("Asia/Manila");
?>';

if (!file_exists('config.php')) {
    file_put_contents('config.php', $config_content);
    echo "<div class='alert alert-success'>✓ Created config.php</div>";
} else {
    echo "<div class='alert alert-info'>✓ config.php already exists</div>";
}

// Step 2: Check database connection
echo "<h4>Step 1: Database Connection Test</h4>";
try {
    $conn = new mysqli('localhost', 'root', '', 'nikas_restaurant');
    
    if ($conn->connect_error) {
        echo "<div class='alert alert-danger'>✗ Database Connection Failed: " . $conn->connect_error . "</div>";
        
        // Try to create database
        echo "<h4>Step 2: Creating Database</h4>";
        $conn2 = new mysqli('localhost', 'root', '');
        if ($conn2->connect_error) {
            echo "<div class='alert alert-danger'>✗ MySQL Server Error: " . $conn2->connect_error . "</div>";
            echo "<p>Make sure XAMPP MySQL is running!</p>";
        } else {
            $sql = "CREATE DATABASE IF NOT EXISTS nikas_restaurant CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            if ($conn2->query($sql)) {
                echo "<div class='alert alert-success'>✓ Database 'nikas_restaurant' created</div>";
                
                // Create tables
                $conn2->select_db('nikas_restaurant');
                
                // Create users table
                $sql = "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    full_name VARCHAR(100) NOT NULL,
                    role ENUM('admin', 'cashier', 'manager') DEFAULT 'cashier',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                
                if ($conn2->query($sql)) {
                    echo "<div class='alert alert-success'>✓ Users table created</div>";
                    
                    // Insert admin user
                    $password = 'admin123';
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    
                    $sql = "INSERT IGNORE INTO users (username, password, full_name, role) VALUES 
                            ('admin', '$hash', 'Administrator', 'admin'),
                            ('cashier', '$hash', 'Juan Cashier', 'cashier')";
                    
                    if ($conn2->query($sql)) {
                        echo "<div class='alert alert-success'>✓ Admin user created (admin/admin123)</div>";
                    }
                }
            }
            $conn2->close();
        }
    } else {
        echo "<div class='alert alert-success'>✓ Database Connected Successfully</div>";
        
        // Check if users table exists
        $result = $conn->query("SHOW TABLES LIKE 'users'");
        if ($result->num_rows == 0) {
            echo "<div class='alert alert-warning'>✗ Users table not found. Creating...</div>";
            
            $sql = "CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                role ENUM('admin', 'cashier', 'manager') DEFAULT 'cashier',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            if ($conn->query($sql)) {
                echo "<div class='alert alert-success'>✓ Users table created</div>";
            }
        }
        
        // Check if admin user exists
        $result = $conn->query("SELECT * FROM users WHERE username = 'admin'");
        if ($result->num_rows == 0) {
            echo "<div class='alert alert-warning'>✗ Admin user not found. Creating...</div>";
            
            $password = 'admin123';
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO users (username, password, full_name, role) VALUES 
                    ('admin', '$hash', 'Administrator', 'admin')";
            
            if ($conn->query($sql)) {
                echo "<div class='alert alert-success'>✓ Admin user created (admin/admin123)</div>";
            }
        } else {
            echo "<div class='alert alert-success'>✓ Admin user exists</div>";
            
            // Check password hash
            $user = $result->fetch_assoc();
            if (!password_verify('admin123', $user['password'])) {
                echo "<div class='alert alert-warning'>✗ Password hash incorrect. Fixing...</div>";
                
                $hash = password_hash('admin123', PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password = '$hash' WHERE username = 'admin'");
                echo "<div class='alert alert-success'>✓ Password fixed</div>";
            }
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}

echo "<h4>Step 3: Testing Login</h4>";
echo "<div class='alert alert-info'>
    <p><strong>Test Credentials:</strong></p>
    <ul>
        <li>Username: <strong>admin</strong></li>
        <li>Password: <strong>admin123</strong></li>
    </ul>
    <p><a href='login.php' class='btn btn-primary'>Go to Login Page</a></p>
</div>";

echo "<h4>Step 4: Direct Login Test</h4>";
echo '<form action="login_test.php" method="POST" class="row g-3">
    <div class="col-md-4">
        <input type="text" name="username" class="form-control" placeholder="Username" value="admin">
    </div>
    <div class="col-md-4">
        <input type="password" name="password" class="form-control" placeholder="Password" value="admin123">
    </div>
    <div class="col-md-4">
        <button type="submit" class="btn btn-success">Test Login</button>
    </div>
</form>';

echo "</div></div></body></html>";
?>