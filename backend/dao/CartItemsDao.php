<?php
require_once 'BaseDao.php';

class CartItemsDao extends BaseDao {
    public function __construct() {
        parent::__construct("cart_items");
    }

    public function addToCart($cart_id, $product_id, $quantity) {
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
    }

    public function getCartItems($cart_id) {
        $stmt = $this->connection->prepare("
            SELECT ci.*, p.name, p.price, p.image 
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.cart_id = :cart_id
        ");
        $stmt->execute([':cart_id' => $cart_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateQuantity($cart_id, $product_id, $quantity) {
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
    }

    public function removeItem($cart_id, $product_id) {
        $stmt = $this->connection->prepare("
            DELETE FROM cart_items 
            WHERE cart_id = :cart_id AND product_id = :product_id
        ");
        return $stmt->execute([
            ':cart_id' => $cart_id,
            ':product_id' => $product_id
        ]);
    }

    public function clearCart($cart_id) {
        $stmt = $this->connection->prepare("
            DELETE FROM cart_items WHERE cart_id = :cart_id
        ");
        return $stmt->execute([':cart_id' => $cart_id]);
    }
}
?>