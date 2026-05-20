<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!POSFunctions::isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create_order':
        createOrder();
        break;
        
    case 'get_receipt':
        getReceipt();
        break;
        
    case 'get_order_details':
        getOrderDetails();
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        break;
}

function createOrder() {
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data || !isset($data['items']) || !isset($data['customer_data'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid data']);
        return;
    }
    
    $result = POSFunctions::createOrder($data['items'], $data['customer_data']);
    echo json_encode($result);
}

function getReceipt() {
    $order_id = $_GET['order_id'] ?? 0;
    
    if (!$order_id) {
        echo json_encode(['success' => false, 'error' => 'Order ID required']);
        return;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Get order details
    $stmt = $db->prepare("
        SELECT o.*, u.full_name as cashier 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_result = $stmt->get_result();
    
    if ($order_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        return;
    }
    
    $order = $order_result->fetch_assoc();
    
    // Get order items
    $stmt = $db->prepare("
        SELECT oi.*, p.name as product_name 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    
    $items = [];
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
    }
    
    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);
}

function getOrderDetails() {
    $order_id = $_GET['order_id'] ?? 0;
    
    if (!$order_id) {
        echo json_encode(['success' => false, 'error' => 'Order ID required']);
        return;
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Get order details
    $stmt = $db->prepare("
        SELECT o.*, u.full_name as cashier 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        WHERE o.id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_result = $stmt->get_result();
    
    if ($order_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Order not found']);
        return;
    }
    
    $order = $order_result->fetch_assoc();
    
    // Get order items
    $stmt = $db->prepare("
        SELECT oi.*, p.name as product_name 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $items_result = $stmt->get_result();
    
    $items = [];
    while ($item = $items_result->fetch_assoc()) {
        $items[] = $item;
    }
    
    echo json_encode([
        'success' => true,
        'order' => $order,
        'items' => $items
    ]);
}
?>