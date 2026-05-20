<?php
require_once 'includes/functions.php';
POSFunctions::requireLogin();
POSFunctions::requireRole('admin');

$page_title = "Manage Categories";

$db = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;
$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $display_order = $_POST['display_order'] ?? 0;
    
    if ($action == 'add') {
        $stmt = $db->prepare("INSERT INTO categories (name, description, display_order) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $name, $description, $display_order);
        if ($stmt->execute()) {
            $message = 'Category added successfully!';
            header("Location: categories.php?message=" . urlencode($message));
            exit();
        }
    } elseif ($action == 'edit' && $id > 0) {
        $stmt = $db->prepare("UPDATE categories SET name = ?, description = ?, display_order = ? WHERE id = ?");
        $stmt->bind_param("ssii", $name, $description, $display_order, $id);
        if ($stmt->execute()) {
            $message = 'Category updated successfully!';
            header("Location: categories.php?message=" . urlencode($message));
            exit();
        }
    }
}

// Handle delete
if ($action == 'delete' && $id > 0) {
    // Check if category has products
    $check = $db->query("SELECT COUNT(*) as count FROM products WHERE category_id = $id");
    $row = $check->fetch_assoc();
    
    if ($row['count'] > 0) {
        $message = 'Cannot delete category with products. Move products first.';
    } else {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'Category deleted successfully!';
            header("Location: categories.php?message=" . urlencode($message));
            exit();
        }
    }
}

// Get all categories
$result = $db->query("SELECT c.*, 
    (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) as product_count
    FROM categories c 
    ORDER BY display_order, name");
$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
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
    $category = [];
    if ($action == 'edit' && $id > 0) {
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $category = $result->fetch_assoc();
        if (!$category) {
            header("Location: categories.php");
            exit();
        }
    }
?>
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <?php echo $action == 'add' ? 'Add New Category' : 'Edit Category'; ?>
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="categories.php?action=<?php echo $action; ?><?php echo $id ? "&id=$id" : ''; ?>">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="name" class="form-label">Category Name *</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               value="<?php echo $category['name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="display_order" class="form-label">Display Order</label>
                        <input type="number" class="form-control" id="display_order" name="display_order" 
                               value="<?php echo $category['display_order'] ?? 0; ?>" min="0">
                        <small class="text-muted">Lower numbers appear first</small>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"><?php echo $category['description'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    <?php echo $action == 'add' ? 'Add Category' : 'Update Category'; ?>
                </button>
                <a href="categories.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Categories</h5>
        <a href="categories.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Category
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Display Order</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-tags fa-2x mb-2"></i>
                            <p>No categories found</p>
                            <a href="categories.php?action=add" class="btn btn-primary">Add Your First Category</a>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($categories as $category): ?>
                    <tr>
                        <td><?php echo $category['id']; ?></td>
                        <td><strong><?php echo $category['name']; ?></strong></td>
                        <td><?php echo $category['description'] ?: '-'; ?></td>
                        <td><?php echo $category['display_order']; ?></td>
                        <td>
                            <span class="badge bg-info"><?php echo $category['product_count']; ?> products</span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="categories.php?action=edit&id=<?php echo $category['id']; ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger" 
                                        onclick="deleteCategory(<?php echo $category['id']; ?>, '<?php echo addslashes($category['name']); ?>', <?php echo $category['product_count']; ?>)">
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
function deleteCategory(id, name, productCount) {
    if (productCount > 0) {
        alert(`Cannot delete "${name}" because it has ${productCount} products. Move or delete the products first.`);
        return;
    }
    
    if (confirm(`Are you sure you want to delete "${name}"?`)) {
        window.location.href = `categories.php?action=delete&id=${id}`;
    }
}
</script>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>