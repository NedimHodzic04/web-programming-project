<?php
require_once __DIR__ . '/../dao/CartDao.php';
class CartService {
    private $cartDao;

    public function __construct() {
        $this->cartDao = new CartDao();
    }

    // Create a new cart for a user
    public function createCart($user_id) {
        return $this->cartDao->createCart($user_id);
    }

    // Get the cart for a user
    public function getCartByUser($user_id) {
        return $this->cartDao->getCartByUser($user_id);
    }
}
?>
