<?php
require_once __DIR__ . '/../dao/OrderDao.php';
require_once __DIR__ . '/../dao/UserDao.php'; // Required for user details for order creation

class OrderService {
    private $orderDao;
    private $userDao; // To potentially fetch user details for order creation/validation

    public function __construct() {
        $this->orderDao = new OrderDao();
        $this->userDao = new UserDao(); // Initialize UserDao
    }

    // NEW SERVICE METHOD: Get all orders for admin view (no change needed here, it uses the DAO)
    public function getAllOrdersForAdmin() {
        try {
            return $this->orderDao->getAllOrdersWithDetails();
        } catch (Exception $e) {
            error_log("Failed to retrieve all orders for admin: " . $e->getMessage());
            throw new Exception("Failed to retrieve orders.");
        }
    }

    // CORRECTED SERVICE METHOD: Get detailed info for a single order
    public function getOrderDetailsForAdmin($order_id) {
        try {
            $details = $this->orderDao->getOrderDetails($order_id);
            if (empty($details)) {
                throw new Exception("Order details not found.", 404);
            }

            // Extract common order info from the first item
            $orderInfo = [
                'order_id' => $details[0]['order_id'],
                'total_price' => $details[0]['total_price'],
                'order_date' => $details[0]['order_date'],
                // 'status' => $details[0]['status'], // REMOVED: Since orders table doesn't have status
                'customer_name' => $details[0]['first_name'] . ' ' . $details[0]['last_name'],
                'shipping_address' => $details[0]['shipping_address'] . ', ' . $details[0]['shipping_city'] . ', ' . $details[0]['shipping_zip'],
                'items' => []
            ];

            // Loop through all items to build the items list
            foreach ($details as $item) {
                $orderInfo['items'][] = [
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'product_price' => $item['product_price'], // Now using product_price
                    'item_total' => $item['item_total']
                ];
            }
            return $orderInfo;
        } catch (Exception $e) {
            error_log("Failed to retrieve order details for ID " . $order_id . ": " . $e->getMessage());
            throw new Exception("Failed to retrieve order details.");
        }
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
        // Basic check if user exists (optional, but good practice)
        $user = $this->userDao->getById($user_id);
        if (!$user) {
            throw new Exception("User with ID {$user_id} not found.");
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