<?php
require_once __DIR__ . '/../dao/BaseDao.php';

class CategoryDao extends BaseDao {
    public function __construct() {
        parent::__construct("categories");
    }

    /**
     * Get all categories with their product counts.
     */
    public function getAllCategories() {
        try {
            $stmt = $this->connection->query("
                SELECT c.*, COUNT(p.id) as product_count 
                FROM categories c
                LEFT JOIN products p ON c.id = p.category_id
                GROUP BY c.id
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Log the error
            error_log("Failed to retrieve categories: " . $e->getMessage());
            return []; // Return an empty array on failure
        }
    }

    /**
     * Add a new category to the database.
     * @param string $name
     * @return int|null The ID of the new category or null if failed.
     */
    public function addCategory($name) {
        // Validate the name
        if (empty($name)) {
            throw new Exception("Category name cannot be empty.");
        }

        try {
            $stmt = $this->connection->prepare("
                INSERT INTO categories (name) VALUES (:name)
            ");
            $stmt->execute([':name' => $name]);
            return $this->connection->lastInsertId(); // Return the ID of the newly inserted category
        } catch (Exception $e) {
            // Log the error
            error_log("Failed to add category: " . $e->getMessage());
            return null; // Return null if the operation fails
        }
    }
}
?>
