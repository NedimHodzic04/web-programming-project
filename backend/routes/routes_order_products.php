<?php
/**
 * Order Products Routes
 * 
 * Handles all routes related to products within orders.
 */


/**
 * @OA\Get(
 *     path="/api/order-products/{order_id}",
 *     summary="Get products in a specific order",
 *     tags={"Order Products"},
 *     @OA\Parameter(
 *         name="order_id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Products retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="product_id", type="integer"),
 *                     @OA\Property(property="name", type="string"),
 *                     @OA\Property(property="quantity", type="integer"),
 *                     @OA\Property(property="price", type="number", format="float")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Order or products not found"
 *     )
 * )
 */
Flight::route('GET /api/order-products/@order_id', function($order_id) {
    try {
        $products = Flight::orderProducts_service()->getProductsByOrder($order_id);
        Flight::json(['status' => 'success', 'data' => $products], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 404);
    }
});

/**
 * @OA\Post(
 *     path="/api/order-products/{order_id}",
 *     summary="Add products to an order",
 *     tags={"Order Products"},
 *     @OA\Parameter(
 *         name="order_id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"products"},
 *             @OA\Property(
 *                 property="products",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     required={"product_id", "quantity"},
 *                     @OA\Property(property="product_id", type="integer"),
 *                     @OA\Property(property="quantity", type="integer")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Products added to order successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="message", type="string", example="Products added to order successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid input or failed to add products"
 *     )
 * )
 */
Flight::route('POST /api/order-products/@order_id', function($order_id) {
    $data = Flight::request()->data;
    
    try {
        if (!isset($data['products']) || !is_array($data['products'])) {
            throw new Exception("Products array is required");
        }
        
        Flight::orderProducts_service()->addProductsToOrder($order_id, $data['products']);
        
        Flight::json([
            'status' => 'success', 
            'message' => 'Products added to order successfully'
        ], 201);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
});
