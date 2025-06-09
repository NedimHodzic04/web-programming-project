<?php
// Autoload dependencies
require_once __DIR__ . '/vendor/autoload.php';
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
require_once __DIR__ . '/services/DashboardService.php';
require_once __DIR__ . '/services/AuthService.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/dao/config.php'; // Ensure Config is loaded before it's used

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException; // Make sure this is imported

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); // Uncomment for debugging if needed

// Register services
Flight::register('cart_service', 'CartService');
Flight::register('cartItems_service', 'CartItemsService');
Flight::register('payment_service', 'PaymentService');
Flight::register('order_service', 'OrderService');
Flight::register('orderProducts_service', 'OrderProductsService');
Flight::register('category_service', 'CategoryService');
Flight::register('product_service', 'ProductService');
Flight::register('user_service', 'UserService');
Flight::register('dashboard_service', 'DashboardService');
Flight::register('auth_service', 'AuthService');
Flight::register('auth_middleware_instance', 'AuthMiddleware'); // Register an instance of AuthMiddleware

// Custom error handling for JSON responses
Flight::map('error', function(Throwable $ex){
    // Log the error
    error_log($ex->getMessage());

    // Send JSON error response
    $code = $ex->getCode() ?: 500; // Use exception code if available, otherwise 500
    if ($code < 100 || $code >= 600) { // Ensure HTTP status code is valid
        $code = 500;
    }

    Flight::json([
        "success" => false,
        "message" => $ex->getMessage()
    ], $code);
});

// Global Authorization middleware using Flight::before
// In your main index.php file

Flight::before('start', function(){
    $request_path = Flight::request()->url;
    $request_method = Flight::request()->method;

    // Define specific routes that DO NOT require authentication (public routes)
    // Use method + path combinations for precise control
    $public_routes = [
        // Auth routes
        'POST /auth/login',
        'POST /auth/register',
        
        // Public Product routes (READ-ONLY)
        'GET /api/products',
        'GET /api/products/search',
        'GET /api/products/featured',
        // Note: GET /api/products/{id} and GET /api/products/category/{category_id} 
        // will need regex matching if you want them public
        
        // Public Category routes (READ-ONLY)
        'GET /api/categories',
        
        // Test route
        'GET /test'
    ];

    // Create current request signature
    $current_request = $request_method . ' ' . $request_path;
    
    $is_public = false;
    
    // Check for exact matches first
    if (in_array($current_request, $public_routes)) {
        $is_public = true;
    }
    
    // Check for pattern matches (for routes with parameters)
    if (!$is_public) {
        // Handle GET requests to /api/products/{id} pattern
        if ($request_method === 'GET' && preg_match('#^/api/products/\d+$#', $request_path)) {
            $is_public = true;
        }
        
        // Handle GET requests to /api/products/category/{category_id} pattern  
        if ($request_method === 'GET' && preg_match('#^/api/products/category/\d+$#', $request_path)) {
            $is_public = true;
        }
        
        // Handle GET requests to /api/products/search/{query} pattern
        if ($request_method === 'GET' && preg_match('#^/api/products/search/.+$#', $request_path)) {
            $is_public = true;
        }
        
        // Handle GET requests to /api/products/featured/{limit} pattern
        if ($request_method === 'GET' && preg_match('#^/api/products/featured/\d+$#', $request_path)) {
            $is_public = true;
        }
    }
    
    if ($is_public) {
        error_log("Skipping token verification for public route: " . $current_request);
        return;
    }

    // For all protected routes, run authentication
    error_log("Attempting token verification for protected route: " . $current_request);
    try {
        Flight::auth_middleware_instance()->verifyTokenFromHeaders();
        error_log("Token verification SUCCESS for: " . $request_path);
    } catch (Exception $e) {
        error_log("Token verification FAILED for: " . $request_path . " - " . $e->getMessage());
        // verifyTokenFromHeaders should handle the response and Flight::stop()
    }
});


// Require the route files
require __DIR__ . '/routes/routes_cart.php';
require __DIR__ . '/routes/routes_cart_items.php';
require __DIR__ . '/routes/routes_category.php';
require __DIR__ . '/routes/routes_order_products.php';
require __DIR__ . '/routes/routes_order.php';
require __DIR__ . '/routes/routes_payment.php';
require __DIR__ . '/routes/routes_products.php';
require __DIR__ . '/routes/routes_users.php';
require __DIR__ . '/routes/routes_dashboard.php';
require __DIR__ . '/routes/AuthRoutes.php';

// Add a simple test route to verify routing works
Flight::route('GET /test', function() {
    echo 'Routing system is working!';
});

Flight::start();