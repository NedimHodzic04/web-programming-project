<?php
require_once __DIR__ . '/../dao/CategoryDao.php';

class CategoryService {
    private $categoryDao;

    public function __construct(CategoryDao $categoryDao = null) {
        $this->categoryDao = $categoryDao ?? new CategoryDao();
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
            throw new Exception("Failed to retrieve categories: " . $e->getMessage());
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
            throw new Exception("Category name cannot be empty.");
        }

        try {
            $categoryId = $this->categoryDao->addCategory(trim($name));
            if (!$categoryId) {
                throw new Exception("Failed to add category.");
            }
            return $categoryId;
        } catch (Exception $e) {
            throw new Exception("Error adding category: " . $e->getMessage());
        }
    }
}
?>