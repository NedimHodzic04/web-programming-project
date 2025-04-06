<?php
require_once 'BaseDao.php';

class OrderProductsDao extends BaseDao {
    public function __construct() {
        parent::__construct("order_products");
    }

    public function addOrderProduct($order_id, $product_id, $quantity) {
        $stmt = $this->connection->prepare("
            INSERT INTO order_products (order_id, product_id, quantity)
            VALUES (:order_id, :product_id, :quantity)
        ");
        return $stmt->execute([
            ':order_id' => $order_id,
            ':product_id' => $product_id,
            ':quantity' => $quantity
        ]);
    }

    public function getOrderProducts($order_id) {
        $stmt = $this->connection->prepare("
            SELECT op.*, p.name, p.price 
            FROM order_products op
            JOIN products p ON op.product_id = p.id
            WHERE op.order_id = :order_id
        ");
        $stmt->execute([':order_id' => $order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>