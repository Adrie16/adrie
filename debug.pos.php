<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>POS Debug</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <div class="card">
        <div class="card-header">
            <h3>POS System Debug</h3>
        </div>
        <div class="card-body">
            <h4>Test API Endpoints</h4>
            <div class="mb-3">
                <button class="btn btn-primary me-2" onclick="testCreateOrder()">Test Create Order</button>
                <button class="btn btn-secondary me-2" onclick="testGetReceipt()">Test Get Receipt</button>
                <button class="btn btn-info me-2" onclick="checkCart()">Check Local Cart</button>
            </div>
            
            <div id="result" class="mt-3 p-3 bg-light rounded"></div>
            
            <h4 class="mt-4">Troubleshooting</h4>
            <ul>
                <li><strong>Issue:</strong> Cart not updating - Check JavaScript console for errors</li>
                <li><strong>Issue:</strong> Order not processing - Check network tab in browser dev tools</li>
                <li><strong>Issue:</strong> Receipt not showing - Verify API response in network tab</li>
                <li><strong>Fix:</strong> Clear browser cache and cookies</li>
                <li><strong>Fix:</strong> Check if JavaScript is enabled</li>
            </ul>
        </div>
    </div>
    
    <script>
    function testCreateOrder() {
        const testData = {
            items: [
                { id: 1, name: "Test Burger", price: 120, quantity: 2, subtotal: 240 },
                { id: 2, name: "Test Fries", price: 60, quantity: 1, subtotal: 60 }
            ],
            customer_name: "Debug Customer",
            table_number: "DEBUG01",
            payment_method: "cash",
            notes: "Test order from debug"
        };
        
        fetch('api/sales.php?action=create_order', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(testData)
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('result').innerHTML = `
                <h5>API Response:</h5>
                <pre>${JSON.stringify(data, null, 2)}</pre>
                ${data.success ? 
                    `<div class="alert alert-success">✓ Order created! ID: ${data.order_id}, Number: ${data.order_number}</div>` : 
                    `<div class="alert alert-danger">✗ Error: ${data.error}</div>`}
            `;
        })
        .catch(error => {
            document.getElementById('result').innerHTML = `
                <div class="alert alert-danger">Network error: ${error.message}</div>
            `;
        });
    }
    
    function testGetReceipt() {
        const orderId = prompt("Enter Order ID to test receipt:");
        if (!orderId) return;
        
        fetch(`api/sales.php?action=get_receipt&order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('result').innerHTML = `
                <h5>Receipt API Response:</h5>
                <pre>${JSON.stringify(data, null, 2)}</pre>
            `;
        });
    }
    
    function checkCart() {
        // Check if pos.js is loaded
        if (typeof cart !== 'undefined') {
            document.getElementById('result').innerHTML = `
                <h5>Cart State:</h5>
                <pre>${JSON.stringify(cart, null, 2)}</pre>
                <p>Items: ${cart.length}, Total: ${cartTotal || 0}</p>
            `;
        } else {
            document.getElementById('result').innerHTML = `
                <div class="alert alert-warning">Cart variable not found. Make sure you're on the POS page.</div>
            `;
        }
    }
    </script>
</body>
</html>