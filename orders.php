<?php
require_once 'includes/functions.php';
POSFunctions::requireLogin();

$page_title = "Orders";

$db = Database::getInstance()->getConnection();

// Handle status update
if (isset($_POST['update_status']) && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    $stmt->execute();
    
    header("Location: orders.php?updated=1");
    exit();
}

// Get filter parameters
$status = $_GET['status'] ?? '';
$date = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT o.*, u.full_name as cashier FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE 1=1";
$params = [];
$types = "";

if ($status && $status != 'all') {
    $sql .= " AND o.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($date) {
    $sql .= " AND DATE(o.created_at) = ?";
    $params[] = $date;
    $types .= "s";
}

if ($search) {
    $sql .= " AND (o.order_number LIKE ? OR o.customer_name LIKE ? OR o.table_number LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$sql .= " ORDER BY o.created_at DESC";

// Execute query
if (!empty($params)) {
    $stmt = $db->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $db->query($sql);
}

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
?>

<?php include 'includes/header.php'; ?>

<?php if (isset($_GET['updated'])): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    Order status updated successfully!
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Orders</h5>
    </div>
    <div class="card-body">
        <!-- Filter Form -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="all">All Status</option>
                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="preparing" <?php echo $status == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                    <option value="ready" <?php echo $status == 'ready' ? 'selected' : ''; ?>>Ready</option>
                    <option value="served" <?php echo $status == 'served' ? 'selected' : ''; ?>>Served</option>
                    <option value="paid" <?php echo $status == 'paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date" class="form-label">Date</label>
                <input type="date" class="form-control" id="date" name="date" value="<?php echo $date; ?>">
            </div>
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="Order #, Customer, Table..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Filter</button>
                <a href="orders.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
        
        <!-- Orders Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Table</th>
                        <th class="text-end">Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Cashier</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-receipt fa-2x mb-2"></i>
                            <p>No orders found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong><?php echo $order['order_number']; ?></strong></td>
                        <td><?php echo $order['customer_name'] ?: 'Walk-in'; ?></td>
                        <td><?php echo $order['table_number'] ?: 'Takeout'; ?></td>
                        <td class="text-end currency"><?php echo formatPrice($order['final_amount']); ?></td>
                        <td><?php echo getStatusBadge($order['status']); ?></td>
                        <td><?php echo ucfirst($order['payment_method']); ?></td>
                        <td><?php echo $order['cashier']; ?></td>
                        <td><?php echo date('h:i A', strtotime($order['created_at'])); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" 
                                        onclick="viewOrder(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'manager'): ?>
                                <button type="button" class="btn btn-outline-warning" 
                                        data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $order['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php endif; ?>
                                <a href="api/sales.php?action=get_receipt&order_id=<?php echo $order['id']; ?>&print=1" 
                                   target="_blank" class="btn btn-outline-success">
                                    <i class="fas fa-print"></i>
                                </a>
                            </div>
                            
                            <!-- Status Update Modal -->
                            <div class="modal fade" id="statusModal<?php echo $order['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Update Order Status</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST" action="orders.php">
                                            <div class="modal-body">
                                                <p><strong>Order #:</strong> <?php echo $order['order_number']; ?></p>
                                                <p><strong>Customer:</strong> <?php echo $order['customer_name']; ?></p>
                                                
                                                <div class="mb-3">
                                                    <label for="status<?php echo $order['id']; ?>" class="form-label">Status</label>
                                                    <select class="form-select" id="status<?php echo $order['id']; ?>" name="status" required>
                                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                                        <option value="ready" <?php echo $order['status'] == 'ready' ? 'selected' : ''; ?>>Ready</option>
                                                        <option value="served" <?php echo $order['status'] == 'served' ? 'selected' : ''; ?>>Served</option>
                                                        <option value="paid" <?php echo $order['status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                </div>
                                                
                                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
function viewOrder(orderId) {
    fetch(`api/sales.php?action=get_order_details&order_id=${orderId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const order = data.order;
            const items = data.items;
            
            let itemsHtml = '';
            items.forEach(item => {
                itemsHtml += `
                <tr>
                    <td>${item.product_name}</td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-end">₱${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td class="text-end">₱${parseFloat(item.subtotal).toFixed(2)}</td>
                </tr>
                `;
            });
            
            const content = `
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Order #:</strong> ${order.order_number}</p>
                    <p><strong>Customer:</strong> ${order.customer_name}</p>
                    <p><strong>Table:</strong> ${order.table_number || 'Takeout'}</p>
                    <p><strong>Cashier:</strong> ${order.cashier}</p>
                </div>
                <div class="col-md-6">
                    <p><strong>Date:</strong> ${new Date(order.created_at).toLocaleString()}</p>
                    <p><strong>Status:</strong> ${order.status.toUpperCase()}</p>
                    <p><strong>Payment:</strong> ${order.payment_method.toUpperCase()}</p>
                    <p><strong>Notes:</strong> ${order.notes || 'None'}</p>
                </div>
            </div>
            
            <hr>
            
            <h6>Order Items</h6>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                            <td class="text-end">₱${parseFloat(order.total_amount).toFixed(2)}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                            <td class="text-end"><strong>₱${parseFloat(order.final_amount).toFixed(2)}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            `;
            
            document.getElementById('orderDetailsContent').innerHTML = content;
            const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
            modal.show();
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>