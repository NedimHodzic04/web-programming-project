<?php
/**
 * Category Routes
 * 
 * Handles all category-related routes for managing product categories.
 */

/**
 * @OA\Get(
 *     path="/api/categories",
 *     summary="Get all categories",
 *     tags={"Categories"},
 *     @OA\Response(
 *         response=200,
 *         description="Categories retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="name", type="string")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error while retrieving categories"
 *     )
 * )
 */
Flight::route('GET /api/categories', function() {
    try {
        $categories = Flight::category_service()->getAllCategories();
        Flight::json(['status' => 'success', 'data' => $categories], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

/**
 * @OA\Post(
 *     path="/api/categories",
 *     summary="Add a new category",
 *     tags={"Categories"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"name"},
 *             @OA\Property(property="name", type="string", example="Electronics")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Category created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="category_id", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid input or failed to create category"
 *     )
 * )
 */
Flight::route('POST /api/categories', function() {
    $data = Flight::request()->data;
    
    try {
        $categoryId = Flight::category_service()->addCategory($data['name']);
        Flight::json([
            'status' => 'success', 
            'category_id' => $categoryId
        ], 201);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
});
