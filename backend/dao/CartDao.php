<?php
require_once 'BaseDao.php';

class CartDao extends BaseDao {
    public function __construct() {
        parent::__construct("carts");
    }

    public function createCart($user_id) {
        $stmt = $this->connection->prepare("
            INSERT INTO carts (user_id) VALUES (:user_id)
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $this->connection->lastInsertId();
    }

    public function getCartByUser($user_id) {
        $stmt = $this->connection->prepare("
            SELECT * FROM carts WHERE user_id = :user_id
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>