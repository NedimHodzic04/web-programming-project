<?php
require_once __DIR__ . '/../dao/BaseDao.php'; // Adjust path if necessary

class CategoryDao extends BaseDao {
    public function __construct() {
        parent::__construct("categories");
    }

    // Existing method for dashboard stats
    public function getCategoryCount() {
        $stmt = $this->connection->query("SELECT COUNT(*) as count FROM categories");
        return $stmt->fetchColumn();
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
                ORDER BY c.name ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Failed to retrieve categories: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get a single category by ID.
     * @param int $id The ID of the category.
     * @return array|null The category data or null if not found.
     */
    public function getCategoryById($id) {
        $stmt = $this->connection->prepare("SELECT * FROM categories WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Add a new category to the database.
     * @param string $name
     * @return int|null The ID of the new category or null if failed.
     */
    public function addCategory($name) {
        try {
            $stmt = $this->connection->prepare("
                INSERT INTO categories (name) VALUES (:name)
            ");
            $stmt->execute([':name' => $name]);
            return $this->connection->lastInsertId();
        } catch (PDOException $e) { // Catch PDOException for specific DB errors
            error_log("Failed to add category: " . $e->getMessage());
            // If the name already exists due to a unique constraint, you might throw a specific exception here
            if ($e->getCode() === '23000') { // SQLSTATE for integrity constraint violation
                throw new Exception("Category name already exists.", 409); // 409 Conflict
            }
            throw new Exception("Database error adding category: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Failed to add category (general exception): " . $e->getMessage());
            throw new Exception("Error adding category: " . $e->getMessage());
        }
    }

    /**
     * Update an existing category.
     * @param int $id The ID of the category to update.
     * @param string $name The new name of the category.
     * @return bool True on success, false on failure.
     */
    public function updateCategory($id, $name) {
        try {
            $stmt = $this->connection->prepare("
                UPDATE categories SET name = :name WHERE id = :id
            ");
            return $stmt->execute([':name' => $name, ':id' => $id]);
        } catch (PDOException $e) {
            error_log("Failed to update category ID " . $id . ": " . $e->getMessage());
            if ($e->getCode() === '23000') { // Integrity constraint violation
                throw new Exception("Category name already exists or is invalid.", 409);
            }
            throw new Exception("Database error updating category: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Failed to update category (general exception): " . $e->getMessage());
            throw new Exception("Error updating category: " . $e->getMessage());
        }
    }

    /**
     * Delete a category.
     * @param int $id The ID of the category to delete.
     * @return bool True on success, false on failure.
     */
    public function deleteCategory($id) {
        try {
            $stmt = $this->connection->prepare("DELETE FROM categories WHERE id = :id");
            return $stmt->execute([':id' => $id]);
        } catch (PDOException $e) {
            error_log("Failed to delete category ID " . $id . ": " . $e->getMessage());
            if ($e->getCode() === '23000') { // Integrity constraint violation (e.g., foreign key constraint)
                throw new Exception("Cannot delete category: it has associated products.", 409);
            }
            throw new Exception("Database error deleting category: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("Failed to delete category (general exception): " . $e->getMessage());
            throw new Exception("Error deleting category: " . $e->getMessage());
        }
    }
}
?>