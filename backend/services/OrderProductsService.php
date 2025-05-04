<?php
require_once __DIR__ . '/../dao/OrderProductsDao.php';

class OrderProductsService {
    private $orderProductsDao;

    public function __construct() {
        $this->orderProductsDao = new OrderProductsDao();
    }

    /**
     * Add multiple products to an order.
     * @param int $order_id
     * @param array $products (each item: ['product_id' => ..., 'quantity' => ...])
     */
    public function addProductsToOrder($order_id, $products) {
        foreach ($products as $product) {
            $this->orderProductsDao->addOrderProduct(
                $order_id,
                $product['product_id'],
                $product['quantity']
            );
        }
    }

    /**
     * Retrieve all products associated with a specific order.
     */
    public function getProductsByOrder($order_id) {
        return $this->orderProductsDao->getOrderProducts($order_id);
    }
}
?>
