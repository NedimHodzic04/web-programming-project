<?php
// Autoload dependencies
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/dao/CartDao.php';
require_once __DIR__ . '/dao/CartItemsDao.php';
require_once __DIR__ . '/dao/PaymentDao.php';
require_once __DIR__ . '/dao/OrderDao.php';
require_once __DIR__ . '/dao/OrderProductsDao.php';
require_once __DIR__ . '/dao/CategoryDao.php';

// Add missing DAO files if needed
require_once __DIR__ . '/dao/ProductDao.php';
require_once __DIR__ . '/dao/UserDao.php';

// Include service classes
require_once __DIR__ . '/services/CartService.php';
require_once __DIR__ . '/services/CartItemsService.php';
require_once __DIR__ . '/services/PaymentService.php';
require_once __DIR__ . '/services/OrderService.php';
require_once __DIR__ . '/services/OrderProductsService.php';
require_once __DIR__ . '/services/CategoryService.php';
require_once __DIR__ . '/services/ProductService.php';
require_once __DIR__ . '/services/UserService.php';

// Register services
Flight::register('cart_service', 'CartService');
Flight::register('cartItems_service', 'CartItemsService');
Flight::register('payment_service', 'PaymentService');
Flight::register('order_service', 'OrderService');
Flight::register('orderProducts_service', 'OrderProductsService');
Flight::register('category_service', 'CategoryService');
Flight::register('product_service', 'ProductService');
Flight::register('user_service', 'UserService');

// Require the route files
require __DIR__ . '/routes/routes_cart.php';
require __DIR__ . '/routes/routes_cart_items.php';
require __DIR__ . '/routes/routes_category.php';
require __DIR__ . '/routes/routes_order_products.php';
require __DIR__ . '/routes/routes_order.php';
require __DIR__ . '/routes/routes_payment.php';
require __DIR__ . '/routes/routes_products.php';
require __DIR__ . '/routes/routes_users.php';

// Add a simple test route to verify routing works
Flight::route('GET /test', function() {
    echo 'Routing system is working!';
});

// Start the Flight Framework
Flight::start();