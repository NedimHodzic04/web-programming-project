<?php

/**
 * Product Routes
 *
 * Handles all product-related routes for listing, searching,
 * creating, updating, and deleting products.
 */


// Get all products
/**
 * @OA\Get(
 * path="/api/products",
 * summary="Get all products",
 * tags={"Products"},
 * @OA\Response(response=200, description="List of products")
 * )
 */
Flight::route('GET /api/products', function() {
    try {
        $products = Flight::product_service()->getAll();
        Flight::json(['status' => 'success', 'data' => $products], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

// Get a specific product by ID
/**
 * @OA\Get(
 * path="/api/products/{id}",
 * summary="Get product by ID",
 * tags={"Products"},
 * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 * @OA\Response(response=200, description="Product data"),
 * @OA\Response(response=404, description="Product not found")
 * )
 */
Flight::route('GET /api/products/@id', function($id) {
    try {
        $product = Flight::product_service()->getProduct($id);
        Flight::json(['status' => 'success', 'data' => $product], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 404);
    }
});

// Get products by category
/**
 * @OA\Get(
 * path="/api/products/category/{category_id}",
 * summary="Get products by category ID",
 * tags={"Products"},
 * @OA\Parameter(name="category_id", in="path", required=true, @OA\Schema(type="integer")),
 * @OA\Response(response=200, description="Filtered product list")
 * )
 */
Flight::route('GET /api/products/category/@category_id', function($category_id) {
    try {
        $products = Flight::product_service()->getByCategory($category_id);
        Flight::json(['status' => 'success', 'data' => $products], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

// Add a new product
/**
 * @OA\Post(
 * path="/api/products",
 * summary="Add a new product",
 * tags={"Products"},
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * @OA\Property(property="name", type="string"),
 * @OA\Property(property="description", type="string"),
 * @OA\Property(property="price", type="number", format="float"),
 * @OA\Property(property="category_id", type="integer"),
 * @OA\Property(property="stock", type="integer")
 * )
 * ),
 * @OA\Response(response=201, description="Product created"),
 * @OA\Response(response=400, description="Invalid input")
 * )
 */
Flight::route('POST /api/products', function() {
    $data = Flight::request()->data;

    try {
        $productId = Flight::product_service()->addProduct($data);
        Flight::json(['status' => 'success', 'id' => $productId], 201);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
});

// Update an existing product
/**
 * @OA\Put(
 * path="/api/products/{id}",
 * summary="Update a product",
 * tags={"Products"},
 * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * @OA\Property(property="name", type="string"),
 * @OA\Property(property="description", type="string"),
 * @OA\Property(property="price", type="number", format="float"),
 * @OA\Property(property="category_id", type="integer"),
 * @OA\Property(property="stock", type="integer")
 * )
 * ),
 * @OA\Response(response=200, description="Product updated"),
 * @OA\Response(response=400, description="Invalid input")
 * )
 */
Flight::route('PUT /api/products/@id', function($id) {
    $data = Flight::request()->data;

    try {
        $result = Flight::product_service()->updateProduct($id, $data);
        Flight::json(['status' => 'success', 'updated' => $result], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
});

// Update product stock
/**
 * @OA\Patch(
 * path="/api/products/{id}/stock",
 * summary="Update product stock",
 * tags={"Products"},
 * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * @OA\Property(property="quantity", type="integer")
 * )
 * ),
 * @OA\Response(response=200, description="Stock updated"),
 * @OA\Response(response=400, description="Invalid input")
 * )
 */
Flight::route('PATCH /api/products/@id/stock', function($id) {
    $data = Flight::request()->data;

    try {
        $result = Flight::product_service()->updateStock($id, $data['quantity']);
        Flight::json(['status' => 'success', 'updated' => $result], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
});

// Delete a product
/**
 * @OA\Delete(
 * path="/api/products/{id}",
 * summary="Delete a product",
 * tags={"Products"},
 * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
 * @OA\Response(response=200, description="Product deleted"),
 * @OA\Response(response=400, description="Delete failed")
 * )
 */
Flight::route('DELETE /api/products/@id', function($id) {
    try {
        $result = Flight::product_service()->delete($id);
        Flight::json(['status' => 'success', 'deleted' => $result], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
});

// Search for products
/**
 * @OA\Get(
 * path="/api/products/search/{query}",
 * summary="Search for products",
 * tags={"Products"},
 * @OA\Parameter(name="query", in="path", required=true, @OA\Schema(type="string")),
 * @OA\Response(response=200, description="Search results")
 * )
 */
Flight::route('GET /api/products/search/@query', function($query) {
    try {
        $products = Flight::product_service()->search($query);
        Flight::json(['status' => 'success', 'data' => $products], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

// Get featured products
/**
 * @OA\Get(
 * path="/api/products/featured/{limit}",
 * summary="Get featured products",
 * tags={"Products"},
 * @OA\Parameter(name="limit", in="path", required=true, @OA\Schema(type="integer")),
 * @OA\Response(response=200, description="Featured product list")
 * )
 */
Flight::route('GET /api/products/featured/@limit', function($limit) {
    $limit = (int)$limit ?: 5;

    try {
        $products = Flight::product_service()->getFeatured($limit);
        Flight::json(['status' => 'success', 'data' => $products], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 500);
    }
});

// ... (Assuming your Payment Routes code remains the same below this)

?>