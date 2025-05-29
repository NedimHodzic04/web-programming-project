<?php
/**
 * Category Routes
 *
 * Handles all category-related routes for managing product categories.
 */

/**
 * @OA\Get(
 * path="/api/categories",
 * summary="Get all categories",
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
        $categories = Flight::category_service()->getAllCategories();
        Flight::json(['status' => 'success', 'data' => $categories], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 500;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Post(
 * path="/api/categories",
 * summary="Add a new category",
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
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('POST /api/categories', function() {
    // AUTHORIZATION: Only admins can add categories
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());

    $data = Flight::request()->data;

    try {
        $categoryId = Flight::category_service()->addCategory($data['name']);
        Flight::json([
            'status' => 'success',
            'category_id' => $categoryId
        ], 201);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

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
 * @OA\Property(property="updated", type="boolean")
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
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('PUT /api/categories/@id', function($id) {
    // AUTHORIZATION: Only admins can update categories
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());
    $data = Flight::request()->data;
    try {
        // Assuming CategoryService has an update method (inherits from BaseService)
        $result = Flight::category_service()->update($id, $data);
        Flight::json(['status' => 'success', 'updated' => $result], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

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
 * @OA\Property(property="deleted", type="boolean")
 * )
 * ),
 * @OA\Response(
 * response=400,
 * description="Failed to delete category"
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
Flight::route('DELETE /api/categories/@id', function($id) {
    // AUTHORIZATION: Only admins can delete categories
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());
    try {
        // Assuming CategoryService extends BaseService or has a delete method
        $result = Flight::category_service()->delete($id);
        if ($result) {
            Flight::json(['status' => 'success', 'deleted' => $result], 200);
        } else {
            Flight::halt(404, "Category not found or failed to delete.");
        }
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});