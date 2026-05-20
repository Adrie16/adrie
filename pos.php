<?php
require_once 'includes/functions.php';
POSFunctions::requireLogin();

$page_title = "Point of Sale";

// Get categories and products
$categories = POSFunctions::getCategories();
?>

<?php include 'includes/header.php'; ?>

<div class="row">
    <!-- Left Column - Menu Items -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Menu Items</h5>
                <div class="input-group" style="width: 300px;">
                    <input type="text" class="form-control" placeholder="Search products..." id="searchProduct">
                    <button class="btn btn-outline-secondary" type="button">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Category Tabs -->
                <ul class="nav nav-tabs" id="categoryTabs" role="tablist">
                    <?php foreach ($categories as $index => $category): 
                        $products = POSFunctions::getProducts($category['id']);
                        if (!empty($products)): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo $index == 0 ? 'active' : ''; ?>" 
                                id="cat-<?php echo $category['id']; ?>"
                                data-bs-toggle="tab" 
                                data-bs-target="#tab-<?php echo $category['id']; ?>" 
                                type="button">
                            <?php echo $category['name']; ?>
                            <span class="badge bg-secondary ms-1"><?php echo count($products); ?></span>
                        </button>
                    </li>
                    <?php endif; endforeach; ?>
                </ul>
                
                <!-- Menu Items Grid -->
                <div class="tab-content mt-3" id="categoryContent">
                    <?php foreach ($categories as $index => $category): 
                        $products = POSFunctions::getProducts($category['id']);
                        if (!empty($products)): ?>
                    <div class="tab-pane fade <?php echo $index == 0 ? 'show active' : ''; ?>" 
                         id="tab-<?php echo $category['id']; ?>">
                        <div class="row">
                            <?php foreach ($products as $product): ?>
                            <div class="col-md-4 col-lg-3 mb-3">
                                <div class="card pos-item h-100" onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>', <?php echo $product['price']; ?>)">
                                    <div class="card-body text-center">
                                        <?php if (!empty($product['image_url'])): ?>
                                        <img src="<?php echo $product['image_url']; ?>" class="img-fluid mb-2 rounded" style="height: 100px; object-fit: cover;">
                                        <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center mb-2 rounded" style="height: 100px;">
                                            <i class="fas fa-utensils fa-2x text-muted"></i>
                                        </div>
                                        <?php endif; ?>
                                        <h6 class="card-title mb-1"><?php echo $product['name']; ?></h6>
                                        <p class="card-text text-muted small mb-2">
                                            <?php echo $product['category_name']; ?>
                                        </p>
                                        <p class="card-text price-tag mb-0">
                                            <?php echo formatPrice($product['price']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right Column - Order Cart -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Current Order</h5>
            </div>
            <div class="card-body">
                <!-- Order Items -->
                <div id="orderItems" class="mb-3" style="max-height: 300px; overflow-y: auto;">
                    <div class="text-center text-muted py-5" id="emptyCart">
                        <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                        <p>No items in cart</p>
                    </div>
                    <div id="cartItems"></div>
                </div>
                
                <!-- Order Summary -->
                <div class="border-top pt-3">
                    <div class="row mb-2">
                        <div class="col-6">Subtotal:</div>
                        <div class="col-6 text-end" id="subtotal">₱0.00</div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6"><strong>Total:</strong></div>
                        <div class="col-6 text-end"><strong id="total" class="currency">₱0.00</strong></div>
                    </div>
                    
                    <!-- Order Details Form -->
                    <form id="orderForm">
                        <div class="mb-3">
                            <label for="customerName" class="form-label">Customer Name</label>
                            <input type="text" class="form-control" id="customerName" placeholder="Walk-in customer" value="Walk-in Customer">
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-6">
                                <label for="tableNumber" class="form-label">Table #</label>
                                <select class="form-select" id="tableNumber">
                                    <option value="">Takeout</option>
                                    <?php for($i = 1; $i <= 20; $i++): ?>
                                    <option value="T<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>">T<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <label for="paymentMethod" class="form-label">Payment</label>
                                <select class="form-select" id="paymentMethod">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="gcash">GCash</option>
                                    <option value="paymaya">PayMaya</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="orderNotes" class="form-label">Notes</label>
                            <textarea class="form-control" id="orderNotes" rows="2" placeholder="Special instructions..."></textarea>
                        </div>
                    </form>
                    
                    <!-- Action Buttons -->
                    <div class="d-grid gap-2">
                        <button class="btn btn-danger" onclick="clearCart()">
                            <i class="fas fa-trash me-2"></i>Clear Cart
                        </button>
                        <button class="btn btn-success" onclick="processOrder()" id="processOrderBtn" disabled>
                            <i class="fas fa-check-circle me-2"></i>Process Order
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="card-title">Quick Actions</h6>
                <div class="row g-2">
                    <?php 
                    $quick_tables = ['T01', 'T02', 'T03', 'T04', 'T05', 'T06'];
                    foreach ($quick_tables as $table): ?>
                    <div class="col-4">
                        <button class="btn btn-outline-secondary w-100" onclick="quickTable('<?php echo $table; ?>')">
                            <?php echo $table; ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="receiptContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printReceipt()">
                    <i class="fas fa-print me-1"></i>Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Cart data
let cart = [];
let cartTotal = 0;

// Add item to cart
function addToCart(productId, productName, price) {
    // Check if item already in cart
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        existingItem.quantity += 1;
        existingItem.subtotal = existingItem.quantity * existingItem.price;
    } else {
        cart.push({
            id: productId,
            name: productName,
            price: parseFloat(price),
            quantity: 1,
            subtotal: parseFloat(price)
        });
    }
    
    updateCartDisplay();
    showToast('Added: ' + productName, 'success');
}

// Remove item from cart
function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    updateCartDisplay();
}

// Update quantity
function updateQuantity(productId, change) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        item.quantity += change;
        if (item.quantity < 1) {
            removeFromCart(productId);
            return;
        }
        item.subtotal = item.quantity * item.price;
        updateCartDisplay();
    }
}

// Update cart display
function updateCartDisplay() {
    const cartItemsDiv = document.getElementById('cartItems');
    const emptyCartDiv = document.getElementById('emptyCart');
    const processOrderBtn = document.getElementById('processOrderBtn');
    
    if (cart.length === 0) {
        emptyCartDiv.style.display = 'block';
        cartItemsDiv.innerHTML = '';
        processOrderBtn.disabled = true;
    } else {
        emptyCartDiv.style.display = 'none';
        
        let html = '';
        let subtotal = 0;
        
        cart.forEach(item => {
            subtotal += item.subtotal;
            html += `
            <div class="order-item">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1">${item.name}</h6>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-secondary" onclick="updateQuantity(${item.id}, -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <button type="button" class="btn btn-outline-secondary" disabled>${item.quantity}</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="updateQuantity(${item.id}, 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="text-end">
                        <div class="text-success fw-bold">₱${item.subtotal.toFixed(2)}</div>
                        <small>₱${item.price.toFixed(2)} each</small>
                        <br>
                        <button class="btn btn-sm btn-outline-danger mt-1" onclick="removeFromCart(${item.id})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
            `;
        });
        
        cartItemsDiv.innerHTML = html;
        processOrderBtn.disabled = false;
    }
    
    // Update totals
    const tax = subtotal * <?php echo TAX_RATE; ?>;
    const total = subtotal + tax;
    
    document.getElementById('subtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('tax').textContent = '₱' + tax.toFixed(2);
    document.getElementById('total').textContent = '₱' + total.toFixed(2);
    
    cartTotal = total;
}

// Clear cart
function clearCart() {
    if (cart.length === 0) return;
    
    if (confirm('Are you sure you want to clear the cart?')) {
        cart = [];
        updateCartDisplay();
        showToast('Cart cleared', 'warning');
    }
}

// Quick table buttons
function quickTable(table) {
    document.getElementById('tableNumber').value = table;
}

// Process order
function processOrder() {
    if (cart.length === 0) {
        alert('Cart is empty!');
        return;
    }
    
    const customerName = document.getElementById('customerName').value;
    const tableNumber = document.getElementById('tableNumber').value;
    const paymentMethod = document.getElementById('paymentMethod').value;
    const orderNotes = document.getElementById('orderNotes').value;
    
    // Prepare order data
    const orderData = {
        items: cart,
        customer_data: {
            customer_name: customerName,
            table_number: tableNumber,
            payment_method: paymentMethod,
            notes: orderNotes
        }
    };
    
    // Show loading
    const btn = document.getElementById('processOrderBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    btn.disabled = true;
    
    // Send to server
    fetch('api/sales.php?action=create_order', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(orderData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showReceipt(data.order_id, data.order_number);
            // Reset form
            cart = [];
            updateCartDisplay();
            document.getElementById('orderForm').reset();
            document.getElementById('customerName').value = 'Walk-in Customer';
        } else {
            alert('Error: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Thank You For Your Order');
    })
    .finally(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
    });
}

// Show receipt
function showReceipt(orderId, orderNumber) {
    fetch(`api/sales.php?action=get_receipt&order_id=${orderId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const receiptContent = document.getElementById('receiptContent');
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
            
            receiptContent.innerHTML = `
            <div class="receipt" style="font-family: 'Courier New', monospace;">
                <div class="text-center mb-3">
                    <h3>NIKA'S RESTAURANT</h3>
                    <p>MV Hechanova, Jaro Iloilo City,Beside JIGA</p>
                    <p>Tel: 09072662186</p>
                </div>
                
                <hr>
                
                <div class="text-center mb-3">
                    <h5>SALES INVOICE</h5>
                    <p>Order #: ${orderNumber}</p>
                    <p>Date: ${new Date().toLocaleDateString()}</p>
                    <p>Time: ${new Date().toLocaleTimeString()}</p>
                </div>
                
                <div class="mb-3">
                    <p><strong>Customer:</strong> ${order.customer_name}</p>
                    <p><strong>Table:</strong> ${order.table_number || 'Takeout'}</p>
                    <p><strong>Cashier:</strong> ${order.cashier}</p>
                    <p><strong>Payment:</strong> ${order.payment_method.toUpperCase()}</p>
                </div>
                
                <hr>
                
                <table class="table table-sm" style="font-family: 'Courier New', monospace;">
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
                </table>
                
                <hr>
                
                <div class="text-end">
                    <p>Subtotal: ₱${parseFloat(order.total_amount).toFixed(2)}</p>
                    <h4 class="mt-3">TOTAL: ₱${parseFloat(order.final_amount).toFixed(2)}</h4>
                </div>
                
                <hr>
                
                <div class="text-center mt-4">
                    <p>*** THANK YOU FOR ORDER WITH US! ***</p>
                    <p>Please come again!</p>
                </div>
            </div>
            `;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('receiptModal'));
            modal.show();
        }
    });
}

// Print receipt
function printReceipt() {
    const printContent = document.getElementById('receiptContent').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    window.location.reload();
}

// Show toast notification
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.appendChild(toast);
    
    document.body.appendChild(container);
    
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', function () {
        document.body.removeChild(container);
    });
}

// Search products
document.getElementById('searchProduct').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const items = document.querySelectorAll('.pos-item');
    
    items.forEach(item => {
        const name = item.querySelector('.card-title').textContent.toLowerCase();
        if (name.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateCartDisplay();
});
</script>

<?php include 'includes/footer.php'; ?>