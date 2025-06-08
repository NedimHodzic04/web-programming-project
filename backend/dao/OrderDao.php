<?php
require_once 'BaseDao.php';

class OrderDao extends BaseDao {
    public function __construct() {
        parent::__construct("orders");
    }

    // Existing method for dashboard stats (no change needed here)
    public function getOrderCount() {
        $stmt = $this->connection->query("SELECT COUNT(*) as count FROM orders");
        return $stmt->fetchColumn();
    }

    // CORRECTED METHOD: Get all orders with customer details and item count
    public function getAllOrdersWithDetails() {
        $stmt = $this->connection->query("
            SELECT
                o.id AS order_id,
                o.total_price,
                o.order_date,
                u.first_name,
                u.last_name,
                COUNT(op.product_id) AS item_count
            FROM
                orders o
            JOIN
                users u ON o.user_id = u.id
            LEFT JOIN
                order_products op ON o.id = op.order_id
            GROUP BY
                o.id, u.id, o.total_price, o.order_date, u.first_name, u.last_name
            ORDER BY
                o.order_date DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // CORRECTED METHOD: Get order details including all products in the order
    // Assuming 'products' table has an 'id' and 'price' column.
    public function getOrderDetails($order_id) {
        $stmt = $this->connection->prepare("
            SELECT
                o.id AS order_id,
                o.total_price,
                o.order_date,
                u.first_name,
                u.last_name,
                u.address AS shipping_address,
                u.city AS shipping_city,
                u.zip AS shipping_zip,
                p.name AS product_name,
                op.quantity,
                p.price AS product_price, -- Get price from products table
                (op.quantity * p.price) AS item_total -- Calculate item total
            FROM
                orders o
            JOIN
                users u ON o.user_id = u.id
            JOIN
                order_products op ON o.id = op.order_id
            JOIN
                products p ON op.product_id = p.id
            WHERE
                o.id = :order_id
            ORDER BY
                p.name ASC
        ");
        $stmt->execute([':order_id' => $order_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createOrder($user_id, $total_price) {
        $stmt = $this->connection->prepare("
            INSERT INTO orders (user_id, total_price, order_date) -- Removed 'status' column
            VALUES (:user_id, :total_price, NOW())
        ");
        $stmt->execute([
            ':user_id' => $user_id,
            ':total_price' => $total_price
        ]);
        return $this->connection->lastInsertId();
    }

    // This method is for user-specific orders, also removing status
    public function getUserOrders($user_id) {
        $stmt = $this->connection->prepare("
            SELECT o.id, o.user_id, o.total_price, o.order_date, COUNT(op.product_id) as item_count
            FROM orders o
            LEFT JOIN order_products op ON o.id = op.order_id
            WHERE o.user_id = :user_id
            GROUP BY o.id, o.user_id, o.total_price, o.order_date
            ORDER BY o.order_date DESC
        ");
        $stmt->execute([':user_id' => $user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>