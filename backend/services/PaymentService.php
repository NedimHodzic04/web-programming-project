<?php
require_once __DIR__ . '/../dao/PaymentDao.php';
require_once __DIR__ . '/../dao/OrderDao.php'; // Optional, if you want to check order validity

class PaymentService {
    private $paymentDao;
    private $orderDao;

    public function __construct() {
        $this->paymentDao = new PaymentDao();
        $this->orderDao = new OrderDao(); // Optional: validate order before payment
    }

    /**
     * Processes a payment for a given order.
     */
    public function processPayment($order_id, $user_id, $total_amount) {
        // Optional: Check if the order exists and belongs to the user
        $order = $this->orderDao->getUserOrders($order_id);
        if (!$order || $order['user_id'] != $user_id) {
            throw new Exception("Invalid order or user mismatch.");
        }

        // Optional: Check if already paid
        $existingPayments = $this->paymentDao->getOrderPayments($order_id);
        if (!empty($existingPayments)) {
            throw new Exception("Payment already processed for this order.");
        }

        // Process payment (simulate here)
        // In real apps: integrate Stripe, PayPal, etc.

        return $this->paymentDao->createPayment($order_id, $user_id, $total_amount);
    }

    public function getPaymentsForOrder($order_id) {
        return $this->paymentDao->getOrderPayments($order_id);
    }
}
?>
