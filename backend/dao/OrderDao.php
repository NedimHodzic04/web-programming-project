<?php
require_once 'BaseDao.php';

class OrderDao extends BaseDao {
    public function __construct() {
        parent::__construct("orders");
    }

    public function createOrder($user_id, $total_price) {
        $stmt = $this->connection->prepare("
            INSERT INTO orders (user_id, total_price, order_date)
            VALUES (:user_id, :total_price, NOW())
        ");
        $stmt->execute([
            ':user_id' => $user_id,
            ':total_price' => $total_price
        ]);
        return $this->connection->lastInsertId();
    }

    public function getUserOrders($user_id) {
        $stmt = $this->connection->prepare("
            SELECT o.*, COUNT(op.product_id) as item_count
            FROM orders o
            LEFT JOIN order_products op ON o.id = op.order_id
            WHERE o.user_id = :user_id
            GROUP BY o.id
            ORDER BY o.order_date DESC
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>