<?php
require_once 'includes/functions.php';
POSFunctions::requireLogin();

$page_title = "Sales Reports";

$db = Database::getInstance()->getConnection();

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$category_id = $_GET['category_id'] ?? '';

// Get daily sales report
$sql = "SELECT 
            DATE(created_at) as sale_date,
            COUNT(*) as total_orders,
            SUM(total_amount) as total_sales,
            SUM(tax_amount) as total_tax,
            SUM(final_amount) as total_revenue
        FROM orders 
        WHERE status = 'paid' 
        AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at) 
        ORDER BY sale_date DESC";

$stmt = $db->prepare($sql);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$daily_report = [];
$summary = [
    'total_orders' => 0,
    'total_sales' => 0,
    'total_tax' => 0,
    'total_revenue' => 0
];

while ($row = $result->fetch_assoc()) {
    $daily_report[] = $row;
    $summary['total_orders'] += $row['total_orders'];
    $summary['total_sales'] += $row['total_sales'];
    $summary['total_tax'] += $row['total_tax'];
    $summary['total_revenue'] += $row['total_revenue'];
}

// Get top products
$top_products_sql = "SELECT 
                        p.name,
                        c.name as category_name,
                        SUM(oi.quantity) as total_sold,
                        SUM(oi.subtotal) as total_revenue
                     FROM order_items oi
                     JOIN products p ON oi.product_id = p.id
                     JOIN categories c ON p.category_id = c.id
                     JOIN orders o ON oi.order_id = o.id
                     WHERE o.status = 'paid' 
                     AND DATE(o.created_at) BETWEEN ? AND ?
                     " . ($category_id ? " AND p.category_id = ?" : "") . "
                     GROUP BY p.id
                     ORDER BY total_sold DESC
                     LIMIT 10";

if ($category_id) {
    $stmt = $db->prepare($top_products_sql);
    $stmt->bind_param("ssi", $start_date, $end_date, $category_id);
} else {
    $stmt = $db->prepare($top_products_sql);
    $stmt->bind_param("ss", $start_date, $end_date);
}
$stmt->execute();
$top_result = $stmt->get_result();

$top_products = [];
while ($row = $top_result->fetch_assoc()) {
    $top_products[] = $row;
}

// Get categories for filter
$categories = POSFunctions::getCategories();
?>

<?php include 'includes/header.php'; ?>

<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">Sales Reports</h5>
    </div>
    <div class="card-body">
        <!-- Filter Form -->
        <form method="GET" class="row g-3 mb-4">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date" 
                       value="<?php echo $start_date; ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date" 
                       value="<?php echo $end_date; ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="col-md-3">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" 
                        <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo $category['name']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">Generate Report</button>
                <a href="reports.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
        
        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Orders</h6>
                        <h3 class="text-primary"><?php echo $summary['total_orders']; ?></h3>
                        <small>Orders</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Sales</h6>
                        <h3 class="currency"><?php echo formatPrice($summary['total_sales']); ?></h3>
                        <small>Total Sales</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6 class="text-muted">Total Revenue</h6>
                        <h3 class="currency text-success"><?php echo formatPrice($summary['total_revenue']); ?></h3>
                        <small>Total Revenu</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Daily Sales Report</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Orders</th>
                                        <th class="text-end">Sales</th>
                                        <th class="text-end">VAT</th>
                                        <th class="text-end">Revenue</th>
                                        <th class="text-end">Avg. Order</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($daily_report)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="fas fa-chart-line fa-2x mb-2"></i>
                                            <p>No sales data for selected period</p>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($daily_report as $day): ?>
                                    <tr>
                                        <td><?php echo formatDate($day['sale_date']); ?></td>
                                        <td><?php echo $day['total_orders']; ?></td>
                                        <td class="text-end currency"><?php echo formatPrice($day['total_sales']); ?></td>
                                        <td class="text-end currency"><?php echo formatPrice($day['total_tax']); ?></td>
                                        <td class="text-end currency"><strong><?php echo formatPrice($day['total_revenue']); ?></strong></td>
                                        <td class="text-end currency">
                                            <?php echo formatPrice($day['total_orders'] > 0 ? $day['total_sales'] / $day['total_orders'] : 0); ?>
                                        </td>
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
                        <h6 class="card-title mb-0">Top Selling Products</h6>
                    </div>
                    <div class="card-body">
                        <?php if (empty($top_products)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-chart-bar fa-2x mb-2"></i>
                            <p>No product sales data</p>
                        </div>
                        <?php else: ?>
                        <ul class="list-group list-group-flush">
                            <?php foreach ($top_products as $index => $product): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary me-2">#<?php echo $index + 1; ?></span>
                                    <div>
                                        <h6 class="mb-0"><?php echo $product['name']; ?></h6>
                                        <small class="text-muted"><?php echo $product['category_name']; ?></small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-success rounded-pill"><?php echo $product['total_sold']; ?></span>
                                    <br>
                                    <small class="currency"><?php echo formatPrice($product['total_revenue']); ?></small>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
                
                            <button class="btn btn-warning" onclick="exportReport('print')">
                                <i class="fas fa-print me-2"></i>Print Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportReport(format) {
    const start_date = document.getElementById('start_date').value;
    const end_date = document.getElementById('end_date').value;
    const category_id = document.getElementById('category_id').value;
    
    if (format === 'csv') {
        window.open(`api/reports.php?action=export&format=csv&start_date=${start_date}&end_date=${end_date}&category_id=${category_id}`, '_blank');
    } else if (format === 'print') {
        window.print();
    }
}
</script>

<?php include 'includes/footer.php'; ?>