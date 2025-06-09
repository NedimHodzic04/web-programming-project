<?php
require_once 'BaseDao.php';

class ProductDao extends BaseDao {
    public function __construct() {
        parent::__construct("products");
    }

    // NEW METHOD FOR DASHBOARD STATS
    public function getProductCount() {
        $stmt = $this->connection->query("SELECT COUNT(*) as count FROM products");
        return $stmt->fetchColumn();
    }


    /**
     * Add a new product to the database
     */
    public function addProduct($name, $description, $category_id, $price, $stock_quantity, $image) {
        $stmt = $this->connection->prepare("
            INSERT INTO products (name, description, category_id, price, stock_quantity, image, added_at) 
            VALUES (:name, :description, :category_id, :price, :stock_quantity, :image, NOW())
        ");
        return $stmt->execute([
            ':name' => $name,
            ':description' => $description,
            ':category_id' => $category_id,
            ':price' => $price,
            ':stock_quantity' => $stock_quantity,
            ':image' => $image
        ]);
    }

    /**
 * Get product by ID, including the category name.
 */
public function getProductById($id) {
    $stmt = $this->connection->prepare("
        SELECT
            p.*,
            c.name AS category_name
        FROM
            products p
        JOIN
            categories c ON p.category_id = c.id
        WHERE
            p.id = :id
    ");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

    /**
     * Get all products
     */
    public function getAllProducts() {
        $stmt = $this->connection->query("
            SELECT p.*, c.name as category_name 
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            ORDER BY p.added_at DESC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get products by category
     */
    public function getProductsByCategory($category_id) {
        $stmt = $this->connection->prepare("
            SELECT * FROM products 
            WHERE category_id = :category_id
            ORDER BY added_at DESC
        ");
        $stmt->execute([':category_id' => $category_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Update product information
     */
    public function updateProduct($id, $name, $description, $category_id, $price, $stock_quantity, $image) {
        $stmt = $this->connection->prepare("
            UPDATE products SET
                name = :name,
                description = :description,
                category_id = :category_id,
                price = :price,
                stock_quantity = :stock_quantity,
                image = :image
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':name' => $name,
            ':description' => $description,
            ':category_id' => $category_id,
            ':price' => $price,
            ':stock_quantity' => $stock_quantity,
            ':image' => $image
        ]);
    }

    /**
     * Update product stock quantity
     */
    public function updateStock($id, $quantity) {
        $stmt = $this->connection->prepare("
            UPDATE products SET
                stock_quantity = stock_quantity + :quantity
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':quantity' => $quantity
        ]);
    }

    /**
     * Delete a product
     */
    public function deleteProduct($id) {
        $stmt = $this->connection->prepare("
            DELETE FROM products 
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Search products by name or description
     */
    public function searchProducts($query) {
        $searchTerm = "%$query%";
        $stmt = $this->connection->prepare("
            SELECT * FROM products 
            WHERE name LIKE :query OR description LIKE :query
            ORDER BY added_at DESC
        ");
        $stmt->execute([':query' => $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get featured products (newest additions)
     */
    public function getFeaturedProducts($limit = 5) {
        $stmt = $this->connection->prepare("
            SELECT * FROM products 
            ORDER BY added_at DESC 
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>