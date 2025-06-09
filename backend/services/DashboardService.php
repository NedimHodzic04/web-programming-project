<?php
// DashboardService.php
// Require the specific DAOs that provide dashboard stats
require_once __DIR__ . '/../dao/UserDao.php';    // Make sure paths are correct
require_once __DIR__ . '/../dao/OrderDao.php';
require_once __DIR__ . '/../dao/ProductDao.php';
require_once __DIR__ . '/../dao/CategoryDao.php';

class DashboardService {
    protected $userDao;
    protected $orderDao;
    protected $productDao;
    protected $categoryDao;

    public function __construct() {
        $this->userDao = new UserDao();
        $this->orderDao = new OrderDao();
        $this->productDao = new ProductDao();
        $this->categoryDao = new CategoryDao();
    }

    public function getDashboardStats() {
        $stats = [
            'users' => $this->userDao->getUserCount(),
            'orders' => $this->orderDao->getOrderCount(),
            'products' => $this->productDao->getProductCount(),
            'categories' => $this->categoryDao->getCategoryCount()
        ];
        return $stats;
    }
}
?>