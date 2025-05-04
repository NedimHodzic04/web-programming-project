<?php
/**
 * Order Routes
 * 
 * Handles all order-related routes for creating and managing orders.
 */

/**
 * @OA\Post(
 *     path="/api/orders",
 *     summary="Create a new order",
 *     tags={"Orders"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"user_id", "total_price"},
 *             @OA\Property(property="user_id", type="integer"),
 *             @OA\Property(property="total_price", type="number", format="float")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Order created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="order_id", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Failed to create order"
 *     )
 * )
 */
Flight::route('POST /api/orders', function() {
    $data = Flight::request()->data;
    
    try {
        // Create the order first
        $orderId = Flight::order_service()->createOrder($data['user_id'], $data['total_price']);
        
        Flight::json([
            'status' => 'success', 
            'order_id' => $orderId
        ], 201);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
});

/**
 * @OA\Get(
 *     path="/api/orders/user/{user_id}",
 *     summary="Get all orders for a user",
 *     tags={"Orders"},
 *     @OA\Parameter(
 *         name="user_id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Orders retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="user_id", type="integer"),
 *                     @OA\Property(property="total_price", type="number", format="float"),
 *                     @OA\Property(property="created_at", type="string", format="date-time")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="No orders found for the given user"
 *     )
 * )
 */
Flight::route('GET /api/orders/user/@user_id', function($user_id) {
    try {
        $orders = Flight::order_service()->getUserOrders($user_id);
        Flight::json(['status' => 'success', 'data' => $orders], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 404);
    }
});
