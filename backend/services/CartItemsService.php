<?php
require_once __DIR__ . '/../dao/CartItemsDao.php';

class CartItemsService {
    private $cartItemsDao;

    public function __construct(CartItemsDao $cartItemsDao = null) {
        $this->cartItemsDao = $cartItemsDao ?? new CartItemsDao();
    }

    /**
     * Add a product to the cart. If the product already exists, increase the quantity.
     * @param int $cart_id
     * @param int $product_id
     * @param int $quantity
     * @return bool
     * @throws Exception If validation fails or database operation fails
     */
    public function addToCart($cart_id, $product_id, $quantity) {
        $this->validateCartItemParams($cart_id, $product_id, $quantity);
        
        try {
            return $this->cartItemsDao->addToCart($cart_id, $product_id, $quantity);
        } catch (Exception $e) {
            throw new Exception("Failed to add item to cart: " . $e->getMessage());
        }
    }

    // ... (other methods remain similar but with try-catch blocks)

    /**
     * Validate cart item parameters
     * @throws Exception If any parameter is invalid
     */
    private function validateCartItemParams($cart_id, $product_id, $quantity) {
        if (!is_numeric($cart_id)) {
            throw new Exception("Invalid cart ID.");
        }
        
        if (!is_numeric($product_id)) {
            throw new Exception("Invalid product ID.");
        }
        
        if (!is_numeric($quantity) || $quantity <= 0) {
            throw new Exception("Quantity must be a positive number.");
        }
    }
}
?>