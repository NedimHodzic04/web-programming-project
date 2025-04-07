<?php
require_once 'BaseDao.php';

class CategoryDao extends BaseDao {
    public function __construct() {
        parent::__construct("categories");
    }

    public function getAllCategories() {
        $stmt = $this->connection->query("
            SELECT c.*, COUNT(p.id) as product_count 
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id
            GROUP BY c.id
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addCategory($name) {
        $stmt = $this->connection->prepare("
            INSERT INTO categories (name) VALUES (:name)
        ");
        return $stmt->execute([':name' => $name]);
    }
}
?>