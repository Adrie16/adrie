<?php
// Nikas Restaurant POS - Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'nikas_restaurant');
define('DB_USER', 'root');
define('DB_PASS', '');
define('APP_NAME', 'Nikas Restaurant');
define('APP_VERSION', '2.0');
define('TAX_RATE', 0.0); // 12% VAT
define('CURRENCY', '₱'); // Philippine Peso
define('SITE_URL', 'http://localhost/nikas_restaurant/');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>