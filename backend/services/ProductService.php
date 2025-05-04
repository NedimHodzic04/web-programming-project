<?php
require_once __DIR__ . '/../dao/ProductDao.php';

class ProductService {
    private $dao;

    public function __construct() {
        $this->dao = new ProductDao();
    }

    public function addProduct($data) {
        // Check if product with same name exists
        $existing = $this->dao->searchProducts($data['name']);
        foreach ($existing as $product) {
            if (strtolower($product['name']) === strtolower($data['name'])) {
                throw new Exception("Product already exists with this name.");
            }
        }

        // Basic validation
        if (empty($data['name']) || empty($data['price']) || empty($data['stock_quantity'])) {
            throw new Exception("Name, price, and stock are required.");
        }

        return $this->dao->addProduct(
            $data['name'],
            $data['description'] ?? '',
            $data['category_id'],
            $data['price'],
            $data['stock_quantity'],
            $data['image'] ?? ''
        );
    }

    public function getProduct($id) {
        $product = $this->dao->getProductById($id);
        if (!$product) throw new Exception("Product not found.");
        return $product;
    }

    public function getAll() {
        return $this->dao->getAllProducts();
    }

    public function getByCategory($category_id) {
        return $this->dao->getProductsByCategory($category_id);
    }

    public function updateProduct($id, $data) {
        // Check if product exists
        $existing = $this->dao->getProductById($id);
        if (!$existing) throw new Exception("Product not found.");

        return $this->dao->updateProduct(
            $id,
            $data['name'],
            $data['description'] ?? '',
            $data['category_id'],
            $data['price'],
            $data['stock_quantity'],
            $data['image'] ?? ''
        );
    }

    public function updateStock($id, $quantity) {
        if (!is_numeric($quantity)) throw new Exception("Quantity must be numeric.");
        return $this->dao->updateStock($id, $quantity);
    }

    public function delete($id) {
        return $this->dao->deleteProduct($id);
    }

    public function search($query) {
        return $this->dao->searchProducts($query);
    }

    public function getFeatured($limit = 5) {
        return $this->dao->getFeaturedProducts($limit);
    }
}
