<?php
// require_once __DIR__ . '/../dao/CategoryDao.php'; // Keep this line as it is the DAO for category service

class CategoryService {
    private $categoryDao;

    public function __construct() {
        // Instantiate the CategoryDao directly, similar to ProductService
        $this->categoryDao = new CategoryDao();
    }

    /**
     * Get all categories.
     * @return array List of categories with product counts.
     * @throws Exception If database operation fails
     */
    public function getAllCategories() {
        try {
            return $this->categoryDao->getAllCategories();
        } catch (Exception $e) {
            // Re-throw with a more specific message or log
            throw new Exception("Failed to retrieve categories: " . $e->getMessage(), 500); // Default to 500
        }
    }

    /**
     * Get a single category by ID.
     * @param int $id
     * @return array The category data.
     * @throws Exception If category not found or database error.
     */
    public function getCategoryById($id) {
        if (!is_numeric($id) || $id <= 0) {
            throw new Exception("Invalid category ID.", 400);
        }
        try {
            $category = $this->categoryDao->getCategoryById($id);
            if (!$category) {
                throw new Exception("Category not found.", 404);
            }
            return $category;
        } catch (Exception $e) {
            // Let the DAO handle specific PDOExceptions and convert them to more general ones
            throw new Exception("Failed to retrieve category: " . $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Add a new category.
     * @param string $name The name of the category.
     * @return int The ID of the new category.
     * @throws Exception If validation fails or database operation fails
     */
    public function addCategory($name) {
        if (empty(trim($name))) {
            throw new Exception("Category name cannot be empty.", 400);
        }

        try {
            // The DAO now handles the 'name already exists' exception with code 409
            $categoryId = $this->categoryDao->addCategory(trim($name));
            if (!$categoryId) {
                // This case should ideally be caught by PDOException in DAO or return 0 from lastInsertId
                throw new Exception("Failed to add category - database operation returned no ID.", 500);
            }
            return $categoryId;
        } catch (Exception $e) {
            // Re-throw the exception from DAO, potentially with its specific code (e.g., 409)
            throw new Exception("Error adding category: " . $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Update an existing category.
     * @param int $id The ID of the category.
     * @param string $name The new name of the category.
     * @return bool True on success.
     * @throws Exception If validation fails, category not found, or database operation fails.
     */
    public function updateCategory($id, $name) {
        if (!is_numeric($id) || $id <= 0) {
            throw new Exception("Invalid category ID for update.", 400);
        }
        if (empty(trim($name))) {
            throw new Exception("Category name cannot be empty.", 400);
        }

        try {
            // Check if category exists before attempting update
            $existingCategory = $this->categoryDao->getCategoryById($id);
            if (!$existingCategory) {
                throw new Exception("Category with ID " . $id . " not found for update.", 404);
            }

            // The DAO now handles the 'name already exists' exception with code 409
            $result = $this->categoryDao->updateCategory($id, trim($name));
            if (!$result) {
                // This could mean no rows were affected, but no error occurred.
                // Could return false to indicate no change or throw a specific exception if truly failed.
                // For simplicity, we'll assume true means a change occurred.
                // If the name is the same, PDO->execute() returns true, but no rows are affected.
                // The current DAO returns true if execute succeeds.
            }
            return true; // Assuming success if no exception is thrown
        } catch (Exception $e) {
            throw new Exception("Error updating category: " . $e->getMessage(), $e->getCode() ?: 500);
        }
    }

    /**
     * Delete a category.
     * @param int $id The ID of the category to delete.
     * @return bool True on success.
     * @throws Exception If category not found or database operation fails.
     */
    public function deleteCategory($id) {
        if (!is_numeric($id) || $id <= 0) {
            throw new Exception("Invalid category ID for delete.", 400);
        }
        try {
            // Optional: Check if category exists before attempting delete
            $existingCategory = $this->categoryDao->getCategoryById($id);
            if (!$existingCategory) {
                throw new Exception("Category with ID " . $id . " not found for deletion.", 404);
            }

            // The DAO handles the foreign key constraint exception with code 409
            $result = $this->categoryDao->deleteCategory($id);
            if (!$result) {
                // This scenario (return false but no exception) might mean ID not found, but we check above.
                // Or some other unhandled DAO issue.
                throw new Exception("Failed to delete category - operation returned false.", 500);
            }
            return true;
        } catch (Exception $e) {
            throw new Exception("Error deleting category: " . $e->getMessage(), $e->getCode() ?: 500);
        }
    }
}