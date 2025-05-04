<?php
require_once __DIR__ . '/../dao/OrderDao.php';

class OrderService {
    private $orderDao;

    public function __construct() {
        $this->orderDao = new OrderDao();
    }

    /**
     * Create a new order for a user.
     * @param int $user_id
     * @param float $total_price
     * @return int|null The ID of the newly created order or null if failed.
     */
    public function createOrder($user_id, $total_price) {
        // Example validation
        if (empty($user_id) || $total_price <= 0) {
            throw new Exception("Invalid user ID or total price.");
        }

        // Try creating the order and return the order ID if successful
        try {
            return $this->orderDao->createOrder($user_id, $total_price);
        } catch (Exception $e) {
            // Log the error
            error_log("Failed to create order: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all orders for a specific user, including item count per order.
     * @param int $user_id
     * @return array The user's orders.
     */
    public function getUserOrders($user_id) {
        try {
            return $this->orderDao->getUserOrders($user_id);
        } catch (Exception $e) {
            // Log the error
            error_log("Failed to retrieve orders: " . $e->getMessage());
            return [];
        }
    }
}
?>
