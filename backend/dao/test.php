<?php
require_once 'UserDao.php';
require_once 'CategoryDao.php';
require_once 'ProductDao.php';
require_once 'CartDao.php';
require_once 'CartItemsDao.php';
require_once 'OrderDao.php';
require_once 'OrderProductsDao.php';
require_once 'PaymentDao.php';

// Helper function to display test results
function printTest($name, $result) {
    $status = $result ? "\033[32m✓ PASS\033[0m" : "\033[31m✗ FAIL\033[0m";
    echo str_pad($name, 50) . " $status\n";
}

echo "\n\033[1;33m=== STARTING E-COMMERCE DAO TESTS ===\033[0m\n\n";

// Initialize all DAOs
$userDao = new UserDao();
$categoryDao = new CategoryDao();
$productDao = new ProductDao();
$cartDao = new CartDao();
$cartItemsDao = new CartItemsDao();
$orderDao = new OrderDao();
$orderProductsDao = new OrderProductsDao();
$paymentDao = new PaymentDao();

// =============================================
// TEST 1: USER REGISTRATION
// =============================================
echo "\033[1;34mUSER REGISTRATION TESTS:\033[0m\n";

$testUser = [
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test@example.com',
    'password' => 'password123',
    'city' => 'Test City',
    'address' => '123 Test St',
    'zip' => '12345'
];

// Register new user
$userId = $userDao->register($testUser);
printTest("User registration", $userId !== false);

// Get user by email
$user = $userDao->getUserByEmail('test@example.com');
printTest("Get user by email", $user && $user['first_name'] === 'Test');

// =============================================
// TEST 2: CATEGORY MANAGEMENT
// =============================================
echo "\n\033[1;34mCATEGORY TESTS:\033[0m\n";

// Add category
$categoryAdded = $categoryDao->addCategory('Wheels');
printTest("Add category", $categoryAdded);

// Get all categories
$categories = $categoryDao->getAllCategories();
printTest("Get all categories", !empty($categories));
$categoryId = $categories[0]['id'];

// =============================================
// TEST 3: PRODUCT MANAGEMENT
// =============================================
echo "\n\033[1;34mPRODUCT TESTS:\033[0m\n";

// Add product
$productData = [
    'name' => 'BBS RS Wheels',
    'description' => 'Premium forged wheels',
    'category_id' => $categoryId,
    'price' => 2500,
    'stock_quantity' => 10,
    'image' => 'wheels.jpg'
];
$productId = $productDao->addProduct(
    $productData['name'],
    $productData['description'],
    $productData['category_id'],
    $productData['price'],
    $productData['stock_quantity'],
    $productData['image']
);
printTest("Add product", $productId !== false);

// Get product by ID
$product = $productDao->getProductById($productId);
printTest("Get product by ID", $product && $product['name'] === 'BBS RS Wheels');

// =============================================
// TEST 4: CART OPERATIONS
// =============================================
echo "\n\033[1;34mCART TESTS:\033[0m\n";

// Create cart
$cartId = $cartDao->createCart($userId);
printTest("Create cart", $cartId !== false);

// Add item to cart
$cartItemAdded = $cartItemsDao->addToCart($cartId, $productId, 2);
printTest("Add item to cart", $cartItemAdded);

// Get cart items
$cartItems = $cartItemsDao->getCartItems($cartId);
printTest("Get cart items", count($cartItems) === 1);

// =============================================
// TEST 5: ORDER PROCESSING
// =============================================
echo "\n\033[1;34mORDER TESTS:\033[0m\n";

// Create order
$orderTotal = $cartItems[0]['quantity'] * $cartItems[0]['price'];
$orderId = $orderDao->createOrder($userId, $orderTotal);
printTest("Create order", $orderId !== false);

// Add order products
foreach ($cartItems as $item) {
    $orderProductAdded = $orderProductsDao->addOrderProduct(
        $orderId,
        $item['product_id'],
        $item['quantity']
    );
    printTest("Add product to order", $orderProductAdded);
}

// =============================================
// TEST 6: PAYMENT PROCESSING
// =============================================
echo "\n\033[1;34mPAYMENT TESTS:\033[0m\n";

// Create payment
$paymentAdded = $paymentDao->createPayment($orderId, $userId, $orderTotal);
printTest("Create payment", $paymentAdded);

// =============================================
// TEST 7: CLEANUP
// =============================================
echo "\n\033[1;34mCLEANUP TESTS:\033[0m\n";

// Clear cart
$cartCleared = $cartItemsDao->clearCart($cartId);
printTest("Clear cart", $cartCleared);

// Verify cart is empty
$cartItems = $cartItemsDao->getCartItems($cartId);
printTest("Verify cart is empty", empty($cartItems));

// =============================================
// FINAL RESULTS
// =============================================
echo "\n\033[1;33m=== TEST RESULTS ===\033[0m\n";
echo "Generated IDs:\n";
echo "- User ID: $userId\n";
echo "- Category ID: $categoryId\n";
echo "- Product ID: $productId\n";
echo "- Cart ID: $cartId\n";
echo "- Order ID: $orderId\n";

// Display final data
echo "\nUser Details:\n";
print_r($userDao->getUserByEmail('test@example.com'));

echo "\nAll Products:\n";
print_r($productDao->getAllProducts());

echo "\nOrder Details:\n";
$orderProducts = $orderProductsDao->getOrderProducts($orderId);
print_r($orderProducts);

echo "\n\033[1;32mTesting complete!\033[0m\n";
?>