<?php
require_once 'db_connect.php';

class POSFunctions {
    
    public static function login($username, $password) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT id, username, password, full_name, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                return true;
            }
        }
        return false;
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header("Location: login.php");
            exit();
        }
    }
    
    public static function requireRole($required_role) {
        self::requireLogin();
        if ($_SESSION['role'] != $required_role && $_SESSION['role'] != 'admin') {
            header("Location: index.php");
            exit();
        }
    }
    
    public static function generateOrderNumber() {
        $prefix = "NIKA'S " . date("ym");
        $random = strtoupper(substr(md5(microtime()), 0, 6));
        return $prefix . $random;
    }
    
    public static function formatPrice($amount) {
        return CURRENCY . number_format($amount, 2);
    }
    
    public static function getCategories() {
        $db = Database::getInstance()->getConnection();
        $categories = [];
        
        $result = $db->query("SELECT * FROM categories ORDER BY display_order, name");
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        return $categories;
    }
    
    public static function getProducts($category_id = null) {
        $db = Database::getInstance()->getConnection();
        $products = [];
        
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.is_available = 1";
        
        if ($category_id) {
            $sql .= " AND p.category_id = $category_id";
        }
        
        $sql .= " ORDER BY p.name";
        
        $result = $db->query($sql);
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        return $products;
    }
    
    public static function getProduct($id) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    public static function addProduct($data) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("INSERT INTO products (category_id, name, description, price, cost, image_url, is_available) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issddss", 
            $data['category_id'],
            $data['name'],
            $data['description'],
            $data['price'],
            $data['cost'],
            $data['image_url'],
            $data['is_available']
        );
        
        return $stmt->execute();
    }
    
    public static function updateProduct($id, $data) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("UPDATE products SET category_id = ?, name = ?, description = ?, price = ?, cost = ?, image_url = ?, is_available = ? WHERE id = ?");
        $stmt->bind_param("issddssi", 
            $data['category_id'],
            $data['name'],
            $data['description'],
            $data['price'],
            $data['cost'],
            $data['image_url'],
            $data['is_available'],
            $id
        );
        
        return $stmt->execute();
    }
    
    public static function deleteProduct($id) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        return $stmt->execute();
    }
    
    public static function createOrder($items, $customer_data) {
        $db = Database::getInstance()->getConnection();
        $order_number = self::generateOrderNumber();
        
        // Calculate totals
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }
        
        $tax_amount = $subtotal * TAX_RATE;
        $final_amount = $subtotal + $tax_amount;
        
        // Start transaction
        $db->begin_transaction();
        
        try {
            // Insert order
            $stmt = $db->prepare("INSERT INTO orders (order_number, customer_name, table_number, total_amount, tax_amount, final_amount, payment_method, notes, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssdddssi", 
                $order_number,
                $customer_data['customer_name'],
                $customer_data['table_number'],
                $subtotal,
                $tax_amount,
                $final_amount,
                $customer_data['payment_method'],
                $customer_data['notes'],
                $_SESSION['user_id']
            );
            $stmt->execute();
            
            $order_id = $db->insert_id;
            
            // Insert order items
            $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
            
            foreach ($items as $item) {
                $subtotal_item = $item['price'] * $item['quantity'];
                $stmt->bind_param("iiidd", $order_id, $item['id'], $item['quantity'], $item['price'], $subtotal_item);
                $stmt->execute();
            }
            
            $db->commit();
            return ['success' => true, 'order_id' => $order_id, 'order_number' => $order_number];
            
        } catch (Exception $e) {
            $db->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public static function getTodaySales() {
        $db = Database::getInstance()->getConnection();
        $today = date('Y-m-d');
        
        $result = $db->query("
            SELECT 
                COUNT(*) as total_orders,
                SUM(final_amount) as total_revenue,
                SUM(total_amount) as total_sales,
                SUM(tax_amount) as total_tax
            FROM orders 
            WHERE DATE(created_at) = '$today' AND status = 'paid'
        ");
        
        return $result->fetch_assoc();
    }
    
    public static function getRecentOrders($limit = 10) {
        $db = Database::getInstance()->getConnection();
        
        $result = $db->query("
            SELECT o.*, u.full_name as cashier 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            ORDER BY o.created_at DESC 
            LIMIT $limit
        ");
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        
        return $orders;
    }
    
    public static function getTopProducts($limit = 5) {
        $db = Database::getInstance()->getConnection();
        
        $result = $db->query("
            SELECT 
                p.name,
                SUM(oi.quantity) as total_sold,
                SUM(oi.subtotal) as total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status = 'paid'
            GROUP BY p.id
            ORDER BY total_sold DESC
            LIMIT $limit
        ");
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        return $products;
    }
}

// Global helper functions
function formatPrice($amount) {
    return CURRENCY . number_format($amount, 2);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

function getStatusBadge($status) {
    $badges = [
        'pending' => 'warning',
        'preparing' => 'info',
        'ready' => 'primary',
        'served' => 'success',
        'paid' => 'success',
        'cancelled' => 'danger'
    ];
    
    $class = $badges[$status] ?? 'secondary';
    return "<span class='badge bg-$class'>" . ucfirst($status) . "</span>";
}
?>