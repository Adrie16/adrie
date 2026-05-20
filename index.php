<?php
require_once 'includes/functions.php';
POSFunctions::requireLogin();

$page_title = "Dashboard";

// Get today's sales
$today_sales = POSFunctions::getTodaySales();
$recent_orders = POSFunctions::getRecentOrders(5);
$top_products = POSFunctions::getTopProducts(5);

// Get counts
$db = Database::getInstance()->getConnection();
$result = $db->query("SELECT COUNT(*) as count FROM products WHERE is_available = 1");
$total_products = $result->fetch_assoc();

$result = $db->query("SELECT COUNT(*) as count FROM categories");
$total_categories = $result->fetch_assoc();

$result = $db->query("SELECT COUNT(*) as count FROM orders WHERE status IN ('pending', 'preparing')");
$pending_orders = $result->fetch_assoc();
?>

<?php include 'includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card border-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Today's Sales</h6>
                        <h3 class="currency"><?php echo formatPrice($today_sales['total_revenue'] ?? 0); ?></h3>
                    </div>
                    <div class="bg-primary text-white rounded-circle p-3">
                        <i class="fas fa-peso-sign fa-2x"></i>
                    </div>
                </div>
                <small class="text-muted"><?php echo $today_sales['total_orders'] ?? 0; ?> orders</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-success">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Pending Orders</h6>
                        <h3><?php echo $pending_orders['count'] ?? 0; ?></h3>
                    </div>
                    <div class="bg-success text-white rounded-circle p-3">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
                <small class="text-muted">To be prepared</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Products</h6>
                        <h3><?php echo $total_products['count'] ?? 0; ?></h3>
                    </div>
                    <div class="bg-warning text-white rounded-circle p-3">
                        <i class="fas fa-hamburger fa-2x"></i>
                    </div>
                </div>
                <small class="text-muted">Available items</small>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Categories</h6>
                        <h3><?php echo $total_categories['count'] ?? 0; ?></h3>
                    </div>
                    <div class="bg-info text-white rounded-circle p-3">
                        <i class="fas fa-tags fa-2x"></i>
                    </div>
                </div>
                <small class="text-muted">Menu categories</small>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Orders</h5>
                <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_orders)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-receipt fa-2x mb-2"></i>
                                    <p>No recent orders</p>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($recent_orders as $order): ?>
                            <tr>
                                <td><strong><?php echo $order['order_number']; ?></strong></td>
                                <td><?php echo $order['customer_name'] ?: 'Walk-in'; ?></td>
                                <td class="currency"><?php echo formatPrice($order['final_amount']); ?></td>
                                <td><?php echo getStatusBadge($order['status']); ?></td>
                                <td><?php echo date('h:i A', strtotime($order['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Top Selling Items</h5>
            </div>
            <div class="card-body">
                <?php if (empty($top_products)): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-chart-bar fa-2x mb-2"></i>
                    <p>No sales data yet</p>
                </div>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($top_products as $index => $product): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-primary me-2">#<?php echo $index + 1; ?></span>
                            <span><?php echo $product['name']; ?></span>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success rounded-pill"><?php echo $product['total_sold']; ?> sold</span>
                            <br>
                            <small class="currency"><?php echo formatPrice($product['total_revenue']); ?></small>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-body text-center">
                <h5>Quick Actions</h5>
                <div class="row mt-3">
                    <div class="col-6">
                        <a href="pos.php" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-cash-register fa-2x mb-2"></i><br>
                            New Order
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="products.php?action=add" class="btn btn-success w-100 mb-2">
                            <i class="fas fa-plus fa-2x mb-2"></i><br>
                            Add Product
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>