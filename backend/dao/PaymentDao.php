<?php
require_once 'BaseDao.php';

class PaymentDao extends BaseDao {
    public function __construct() {
        parent::__construct("payments");
    }

    public function createPayment($order_id, $user_id, $total_amount) {
        $stmt = $this->connection->prepare("
            INSERT INTO payments (order_id, user_id, total_amount, payment_status, date)
            VALUES (:order_id, :user_id, :total_amount, 'completed', NOW())
        ");
        return $stmt->execute([
            ':order_id' => $order_id,
            ':user_id' => $user_id,
            ':total_amount' => $total_amount
        ]);
    }

    public function getOrderPayments($order_id) {
        $stmt = $this->connection->prepare("
            SELECT * FROM payments WHERE order_id = :order_id
        ");
        $stmt->execute([':order_id' => $order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>