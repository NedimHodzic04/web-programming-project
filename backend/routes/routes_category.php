<?php
// Ensure the CategoryService is available
// This require_once is for Flight to know the class exists for registration.
// Once Flight registers it, you use Flight::category_service().
require_once __DIR__ . '/../services/CategoryService.php';
require_once __DIR__ . '/../dao/CategoryDao.php'; // Make sure DAO is included for the service constructor


// --- REGISTER CATEGORY SERVICE WITH FLIGHT ---
// This is crucial for using Flight::category_service()
Flight::register('category_service', 'CategoryService');


// --- PUBLIC ROUTE: GET ALL CATEGORIES (FOR DROPDOWN/GENERAL USE) ---
/**
 * @OA\Get(
 * path="/api/categories",
 * summary="Get all categories (Publicly accessible for dropdowns etc.)",
 * tags={"Categories"},
 * @OA\Response(
 * response=200,
 * description="Categories retrieved successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(
 * property="data",
 * type="array",
 * @OA\Items(
 * type="object",
 * @OA\Property(property="id", type="integer"),
 * @OA\Property(property="name", type="string")
 * )
 * )
 * )
 * ),
 * @OA\Response(
 * response=500,
 * description="Server error while retrieving categories"
 * )
 * )
 */
Flight::route('GET /api/categories', function() {
    try {
        // Use Flight's registered service instance
        $categories = Flight::category_service()->getAllCategories();
        Flight::json(['status' => 'success', 'data' => $categories], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 500;
        error_log("Error fetching public categories: " . $e->getMessage());
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

// --- ADMIN ROUTE: GET ALL CATEGORIES FOR ADMIN PANEL (REQUIRES AUTH) ---
/**
 * @OA\Get(
 * path="/api/categories/admin",
 * summary="Get all categories for Admin Panel (Admin only, includes product count)",
 * tags={"Categories"},
 * security={{"ApiKey":{}}},
 * @OA\Response(
 * response=200,
 * description="List of all categories for admin",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string"),
 * @OA\Property(property="data", type="array", @OA\Items(type="object"))
 * )
 * ),
 * @OA\Response(
 * response=401,
 * description="Unauthorized - Token not provided or invalid"
 * ),
 * @OA\Response(
 * response=403,
 * description="Forbidden - Admin access required"
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('GET /api/categories/admin', function() {
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());

    try {
        // Use Flight's registered service instance
        $categories = Flight::category_service()->getAllCategories();
        Flight::json(['status' => 'success', 'data' => $categories], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 500;
        error_log("Error fetching admin categories: " . $e->getMessage());
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});


// --- GET SINGLE CATEGORY BY ID (FOR EDIT MODAL) ---
/**
 * @OA\Get(
 * path="/api/categories/{id}",
 * summary="Get a single category by ID (Admin only)",
 * tags={"Categories"},
 * security={{"ApiKey":{}}},
 * @OA\Parameter(
 * name="id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Response(
 * response=200,
 * description="Category retrieved successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string"),
 * @OA\Property(property="data", type="object",
 * @OA\Property(property="id", type="integer"),
 * @OA\Property(property="name", type="string")
 * )
 * )
 * ),
 * @OA\Response(
 * response=401,
 * description="Unauthorized - Token not provided or invalid"
 * ),
 * @OA\Response(
 * response=403,
 * description="Forbidden - Admin access required"
 * ),
 * @OA\Response(
 * response=404,
 * description="Category not found"
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('GET /api/categories/@id', function($id) {
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());

    try {
        // Use Flight's registered service instance
        $category = Flight::category_service()->getCategoryById($id);
        Flight::json(['status' => 'success', 'data' => $category], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 500;
        error_log("Error fetching category ID " . $id . ": " . $e->getMessage());
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});


// --- POST /api/categories (ADD CATEGORY) ---
/**
 * @OA\Post(
 * path="/api/categories",
 * summary="Add a new category (Admin only)",
 * tags={"Categories"},
 * security={{"ApiKey":{}}},
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * required={"name"},
 * @OA\Property(property="name", type="string", example="Electronics")
 * )
 * ),
 * @OA\Response(
 * response=201,
 * description="Category created successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(property="category_id", type="integer")
 * )
 * ),
 * @OA\Response(
 * response=400,
 * description="Invalid input or failed to create category"
 * ),
 * @OA\Response(
 * response=401,
 * description="Unauthorized - Token not provided or invalid"
 * ),
 * @OA\Response(
 * response=403,
 * description="Forbidden - Admin access required"
 * ),
 * @OA\Response(
 * response=409,
 * description="Conflict - Category name already exists"
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('POST /api/categories', function() {
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());

    $data = Flight::request()->data->getData();

    try {
        $name = $data['name'] ?? null;
        if (!is_string($name)) {
            throw new Exception("Category name is required and must be a string.", 400);
        }

        // Use Flight's registered service instance
        $categoryId = Flight::category_service()->addCategory($name);
        Flight::json([
            'status' => 'success',
            'message' => 'Category added successfully',
            'category_id' => $categoryId
        ], 201);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        error_log("Error adding category: " . $e->getMessage());
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

// --- PUT /api/categories/{id} (UPDATE CATEGORY) ---
/**
 * @OA\Put(
 * path="/api/categories/{id}",
 * summary="Update an existing category (Admin only)",
 * tags={"Categories"},
 * security={{"BearerAuth":{}}},
 * @OA\Parameter(
 * name="id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * required={"name"},
 * @OA\Property(property="name", type="string", example="New Category Name")
 * )
 * ),
 * @OA\Response(
 * response=200,
 * description="Category updated successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(property="updated", type="boolean", example="true")
 * )
 * ),
 * @OA\Response(
 * response=400,
 * description="Invalid input or failed to update category"
 * ),
 * @OA\Response(
 * response=401,
 * description="Unauthorized - Token not provided or invalid"
 * ),
 * @OA\Response(
 * response=403,
 * description="Forbidden - Admin access required"
 * ),
 * @OA\Response(
 * response=404,
 * description="Category not found"
 * ),
 * @OA\Response(
 * response=409,
 * description="Conflict - Category name already exists or is invalid"
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('PUT /api/categories/@id', function($id) {
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());
    
    $data = Flight::request()->data->getData();

    try {
        $name = $data['name'] ?? null;
        if (!is_string($name)) {
            throw new Exception("Category name is required and must be a string.", 400);
        }

        // Use Flight's registered service instance
        $result = Flight::category_service()->updateCategory($id, $name);
        
        Flight::json(['status' => 'success', 'message' => 'Category updated successfully', 'updated' => $result], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        error_log("Error updating category ID " . $id . ": " . $e->getMessage());
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

// --- DELETE /api/categories/{id} (DELETE CATEGORY) ---
/**
 * @OA\Delete(
 * path="/api/categories/{id}",
 * summary="Delete a category (Admin only)",
 * tags={"Categories"},
 * security={{"BearerAuth":{}}},
 * @OA\Parameter(
 * name="id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Response(
 * response=200,
 * description="Category deleted successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(property="deleted", type="boolean", example="true")
 * )
 * ),
 * @OA\Response(
 * response=400,
 * description="Bad request - Delete failed"
 * ),
 * @OA\Response(
 * response=401,
 * description="Unauthorized - Token not provided or invalid"
 * ),
 * @OA\Response(
 * response=403,
 * description="Forbidden - Admin access required"
 * ),
 * @OA\Response(
 * response=404,
 * description="Category not found"
 * ),
 * @OA\Response(
 * response=409,
 * description="Conflict - Cannot delete category: it has associated products."
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('DELETE /api/categories/@id', function($id) {
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());
    try {
        // Use Flight's registered service instance
        $categoryService = Flight::category_service();
        $result = $categoryService->deleteCategory($id);
        
        Flight::json(['status' => 'success', 'message' => 'Category deleted successfully', 'deleted' => $result], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        error_log("Error deleting category ID " . $id . ": " . $e->getMessage());
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});