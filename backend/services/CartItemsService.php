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

    /**
     * Retrieve all items in a specific cart.
     * @param int $cart_id
     * @return array
     * @throws Exception If validation fails or database operation fails
     */
    public function getCartItems($cart_id) { // <-- THIS METHOD WAS MISSING IN PREVIOUS VERSIONS
        if (!is_numeric($cart_id)) {
            throw new Exception("Invalid cart ID.");
        }
        try {
            return $this->cartItemsDao->getCartItems($cart_id);
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve cart items: " . $e->getMessage());
        }
    }

    /**
     * Update the quantity of a product in the cart.
     * @param int $cart_id
     * @param int $product_id
     * @param int $quantity
     * @return bool
     * @throws Exception If validation fails or database operation fails
     */
    public function updateQuantity($cart_id, $product_id, $quantity) { // <-- THIS METHOD WAS MISSING IN PREVIOUS VERSIONS
        $this->validateCartItemParams($cart_id, $product_id, $quantity); // Re-use validation
        try {
            return $this->cartItemsDao->updateQuantity($cart_id, $product_id, $quantity);
        } catch (Exception $e) {
            throw new Exception("Failed to update item quantity: " . $e->getMessage());
        }
    }

    /**
     * Remove a product from the cart.
     * @param int $cart_id
     * @param int $product_id
     * @return bool
     * @throws Exception If validation fails or database operation fails
     */
    public function removeItem($cart_id, $product_id) { // <-- THIS METHOD WAS MISSING IN PREVIOUS VERSIONS
        if (!is_numeric($cart_id) || !is_numeric($product_id)) {
            throw new Exception("Invalid cart ID or product ID.");
        }
        try {
            return $this->cartItemsDao->removeItem($cart_id, $product_id);
        } catch (Exception $e) {
            throw new Exception("Failed to remove item from cart: " . $e->getMessage());
        }
    }

    /**
     * Clear all items from a specific cart.
     * @param int $cart_id
     * @return bool
     * @throws Exception If validation fails or database operation fails
     */
    public function clearCart($cart_id) { // <-- THIS METHOD WAS MISSING IN PREVIOUS VERSIONS
        if (!is_numeric($cart_id)) {
            throw new Exception("Invalid cart ID.");
        }
        try {
            return $this->cartItemsDao->clearCart($cart_id);
        } catch (Exception $e) {
            throw new Exception("Failed to clear cart: " . $e->getMessage());
        }
    }

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