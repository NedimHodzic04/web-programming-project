<?php
require_once __DIR__ . '/../dao/BaseDao.php';

class CartItemsDao extends BaseDao {
    public function __construct() {
        parent::__construct("cart_items");
    }

    /**
     * Add a product to the cart. If the product already exists, increase the quantity.
     * @param int $cart_id
     * @param int $product_id
     * @param int $quantity
     * @return bool
     */
    public function addToCart($cart_id, $product_id, $quantity) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO cart_items (cart_id, product_id, quantity)
                VALUES (:cart_id, :product_id, :quantity)
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
            ");
            return $stmt->execute([
                ':cart_id' => $cart_id,
                ':product_id' => $product_id,
                ':quantity' => $quantity
            ]);
        } catch (Exception $e) {
            error_log("Error adding to cart: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retrieve all items in a specific cart.
     * @param int $cart_id
     * @return array
     */
    public function getCartItems($cart_id) {
        try {
            $stmt = $this->connection->prepare("
                SELECT ci.*, p.name, p.price, p.image 
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.cart_id = :cart_id
            ");
            $stmt->execute([':cart_id' => $cart_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error retrieving cart items: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update the quantity of a product in the cart.
     * @param int $cart_id
     * @param int $product_id
     * @param int $quantity
     * @return bool
     */
    public function updateQuantity($cart_id, $product_id, $quantity) {
        try {
            $stmt = $this->connection->prepare("
                UPDATE cart_items SET 
                quantity = :quantity
                WHERE cart_id = :cart_id AND product_id = :product_id
            ");
            return $stmt->execute([
                ':cart_id' => $cart_id,
                ':product_id' => $product_id,
                ':quantity' => $quantity
            ]);
        } catch (Exception $e) {
            error_log("Error updating quantity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove a product from the cart.
     * @param int $cart_id
     * @param int $product_id
     * @return bool
     */
    public function removeItem($cart_id, $product_id) {
        try {
            $stmt = $this->connection->prepare("
                DELETE FROM cart_items 
                WHERE cart_id = :cart_id AND product_id = :product_id
            ");
            return $stmt->execute([
                ':cart_id' => $cart_id,
                ':product_id' => $product_id
            ]);
        } catch (Exception $e) {
            error_log("Error removing item from cart: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear all items from a specific cart.
     * @param int $cart_id
     * @return bool
     */
    public function clearCart($cart_id) {
        try {
            $stmt = $this->connection->prepare("
                DELETE FROM cart_items WHERE cart_id = :cart_id
            ");
            return $stmt->execute([':cart_id' => $cart_id]);
        } catch (Exception $e) {
            error_log("Error clearing cart: " . $e->getMessage());
            return false;
        }
    }
}
?>
