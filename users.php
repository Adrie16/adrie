<?php
require_once 'includes/functions.php';
POSFunctions::requireLogin();
POSFunctions::requireRole('admin');

$page_title = "Manage Users";

$db = Database::getInstance()->getConnection();
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;
$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $role = $_POST['role'] ?? 'cashier';
    
    if ($action == 'add') {
        // Check if username exists
        $check = $db->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $message = 'Username already exists!';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (username, password, full_name, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $hash, $full_name, $role);
            
            if ($stmt->execute()) {
                $message = 'User added successfully!';
                header("Location: users.php?message=" . urlencode($message));
                exit();
            }
        }
    } elseif ($action == 'edit' && $id > 0) {
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET username = ?, password = ?, full_name = ?, role = ? WHERE id = ?");
            $stmt->bind_param("ssssi", $username, $hash, $full_name, $role, $id);
        } else {
            $stmt = $db->prepare("UPDATE users SET username = ?, full_name = ?, role = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $full_name, $role, $id);
        }
        
        if ($stmt->execute()) {
            $message = 'User updated successfully!';
            header("Location: users.php?message=" . urlencode($message));
            exit();
        }
    }
}

// Handle delete
if ($action == 'delete' && $id > 0) {
    // Prevent deleting yourself
    if ($id == $_SESSION['user_id']) {
        $message = 'You cannot delete your own account!';
    } else {
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'User deleted successfully!';
            header("Location: users.php?message=" . urlencode($message));
            exit();
        }
    }
}

// Get all users
$result = $db->query("SELECT * FROM users ORDER BY role, username");
$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
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
    $user = [];
    if ($action == 'edit' && $id > 0) {
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        if (!$user) {
            header("Location: users.php");
            exit();
        }
    }
?>
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <?php echo $action == 'add' ? 'Add New User' : 'Edit User'; ?>
        </h5>
    </div>
    <div class="card-body">
        <form method="POST" action="users.php?action=<?php echo $action; ?><?php echo $id ? "&id=$id" : ''; ?>">
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" 
                               value="<?php echo $user['username'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo $user['full_name'] ?? ''; ?>" required>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <?php echo $action == 'add' ? 'Password *' : 'Password (leave blank to keep current)'; ?>
                        </label>
                        <input type="password" class="form-control" id="password" name="password" 
                               <?php echo $action == 'add' ? 'required' : ''; ?>>
                    </div>
                    
                    <div class="mb-3">
                        <label for="role" class="form-label">Role *</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="cashier" <?php echo ($user['role'] ?? '') == 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                            <option value="manager" <?php echo ($user['role'] ?? '') == 'manager' ? 'selected' : ''; ?>>Manager</option>
                            <option value="admin" <?php echo ($user['role'] ?? '') == 'admin' ? 'selected' : ''; ?>>Administrator</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>
                    <?php echo $action == 'add' ? 'Add User' : 'Update User'; ?>
                </button>
                <a href="users.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Users</h5>
        <a href="users.php?action=add" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add User
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-users fa-2x mb-2"></i>
                            <p>No users found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><strong><?php echo $user['username']; ?></strong></td>
                        <td><?php echo $user['full_name']; ?></td>
                        <td>
                            <?php 
                            $badge_color = [
                                'admin' => 'danger',
                                'manager' => 'warning',
                                'cashier' => 'success'
                            ];
                            $color = $badge_color[$user['role']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?>"><?php echo ucfirst($user['role']); ?></span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="users.php?action=edit&id=<?php echo $user['id']; ?>" 
                                   class="btn btn-outline-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <button type="button" class="btn btn-outline-danger" 
                                        onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo addslashes($user['username']); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php else: ?>
                                <button type="button" class="btn btn-outline-secondary" disabled title="Cannot delete your own account">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
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
function deleteUser(id, username) {
    if (confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
        window.location.href = `users.php?action=delete&id=${id}`;
    }
}
</script>

<?php endif; ?>

<?php include 'includes/footer.php'; ?>