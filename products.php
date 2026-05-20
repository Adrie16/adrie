<?php
require_once 'includes/functions.php';
POSFunctions::requireLogin();
POSFunctions::requireRole('admin');

$page_title = "Manage Products";

$db = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;
$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = [
        'category_id' => $_POST['category_id'] ?? 0,
        'name' => $_POST['name'] ?? '',
        'description' => $_POST['description'] ?? '',
        'price' => $_POST['price'] ?? 0,
        'cost' => $_POST['cost'] ?? 0,
        'image_url' => $_POST['image_url'] ?? '',
        'is_available' => isset($_POST['is_available']) ? 1 : 0
    ];
    
    if ($action == 'add') {
        if (POSFunctions::addProduct($data)) {
            $message = 'Product added successfully!';
            header("Location: products.php?message=" . urlencode($message));
            exit();
        } else {
            $message = 'Error adding product.';
        }
    } elseif ($action == 'edit' && $id > 0) {
        if (POSFunctions::updateProduct($id, $data)) {
            $message = 'Product updated successfully!';
            header("Location: products.php?message=" . urlencode($message));
            exit();
        } else {
            $message = 'Error updating product.';
        }
    }
}

// Handle delete
if ($action == 'delete' && $id > 0) {
    if (POSFunctions::deleteProduct($id)) {
        $message = 'Product deleted successfully!';
        header("Location: products.php?message=" . urlencode($message));
        exit();
    } else {
        $message = 'Error deleting product.';
    }
}

// Get categories for dropdown
$categories = POSFunctions::getCategories();

// Get all products for listing
$result = $db->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.name
");
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
?>

<?php include 'includes/header.php'; ?>

<?php if ($message): ?>
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <?php echo $message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($action == 'add' || $action == 'edit'): 
    $product = [];
    if ($action == 'edit' && $id > 0) {
        $product = POSFunctions::getProduct($id);
        if (!$product) {
            header("Location: products.php");
            exit();
        }
    }
?>
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <?php echo $action == 'add' ? 'Add New Product' : 'Edit Product'; ?>
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="products.php?action=<?php echo $action; ?><?php echo $id ? "&id=$id" : ''; ?>">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo $product['name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category *</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo (($product['category_id'] ?? '') == $category['id']) ? 'selected' : ''; ?>>
                                <?php echo $category['name']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $product['description'] ?? ''; ?></textarea>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="price" class="form-label">Selling Price (₱) *</label>
                                <input type="number" class="form-control" id="price" name="price" 
                                       step="0.01" min="0" 
                                       value="<?php echo $product['price'] ?? ''; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cost" class="form-label">Cost Price (₱)</label>
                                <input type="number" class="form-control" id="cost" name="cost" 
                                       step="0.01" min="0" 
                                       value="<?php echo $product['cost'] ?? ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="image_url" class="form-label">Image URL</label>
                        <input type="url" class="form-control" id="image_url" name="image_url" 
                               placeholder="https://example.com/image.jpg"
                               value="<?php echo $product['image_url'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_available" name="is_available" 
                               <?php echo ($product['is_available'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_available">Available for sale</label>
                    </div>
                </div>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    <?php echo $action == 'add' ? 'Add Product' : 'Update Product'; ?>
                </button>
                <a href="products.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Products</h5>
        <a href="products.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Product
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Category</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Cost</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-box-open fa-2x mb-2"></i>
                            <p>No products found</p>
                            <a href="products.php?action=add" class="btn btn-primary">Add Your First Product</a>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?php echo $product['id']; ?></td>
                        <td>
                            <strong><?php echo $product['name']; ?></strong>
                            <?php if ($product['description']): ?>
                            <br><small class="text-muted"><?php echo substr($product['description'], 0, 50); ?>...</small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $product['category_name'] ?? 'Uncategorized'; ?></td>
                        <td class="text-end currency"><?php echo formatPrice($product['price']); ?></td>
                        <td class="text-end"><?php echo $product['cost'] ? formatPrice($product['cost']) : '-'; ?></td>
                        <td>
                            <?php if ($product['is_available']): ?>
                            <span class="badge bg-success">Available</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Unavailable</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger" 
                                        onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
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

<script>
function deleteProduct(id, name) {
    if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
        window.location.href = `products.php?action=delete&id=${id}`;
    }
}
</script>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>