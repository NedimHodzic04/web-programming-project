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
Flight::register('auth_service', 'AuthService');
Flight::register('auth_middleware_instance', 'AuthMiddleware'); // Register an instance of AuthMiddleware

// Custom error handling for JSON responses
Flight::map('error', function(Exception $ex){
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

// Global authentication middleware using Flight::before
Flight::before('start', function(){
    $requestUri = Flight::request()->url;
    $requestMethod = Flight::request()->method; // Get the request method

    // Define public routes that do NOT require authentication
    // Consider using a more robust regex or specific route mapping for public access
    $publicRoutes = [
        '/auth/login' => ['POST'],
        '/auth/register' => ['POST'],
        '/products' => ['GET'], // Allow public to view all products
        '/products/' => ['GET'], // Allow public to view all products (trailing slash)
        '/categories' => ['GET'], // Allow public to view all categories
        '/categories/' => ['GET'], // Allow public to view all categories (trailing slash)
        '/test' => ['GET'] // Your test route
    ];

    // Check if the current request URI and method are in the public routes list
    $isPublic = false;
    foreach ($publicRoutes as $routePattern => $methods) {
        // Use strpos for simple prefix matching or more complex regex if needed
        if (strpos($requestUri, $routePattern) === 0 && in_array($requestMethod, $methods)) {
            $isPublic = true;
            break;
        }
    }

    if ($isPublic) {
        return; // Skip authentication for public routes
    }

    // For all other routes, attempt to verify the token using the AuthMiddleware instance
    $token = Flight::request()->getHeader("Authentication");

    if ($token && strpos($token, 'Bearer ') === 0) {
        $token = substr($token, 7); // Remove "Bearer " prefix
    }

    // Use the registered AuthMiddleware instance to verify the token
    Flight::auth_middleware_instance()->verifyToken($token);
    // If verifyToken fails, it will call Flight::halt() and execution will stop.
    // If it succeeds, Flight::set('user', ...) is called, and execution continues.
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
require __DIR__ . '/routes/AuthRoutes.php';

// Add a simple test route to verify routing works
Flight::route('GET /test', function() {
    echo 'Routing system is working!';
});

Flight::start();