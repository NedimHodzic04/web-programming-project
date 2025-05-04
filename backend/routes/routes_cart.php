<?php
/**
 * Cart Routes
 * 
 * Handles all cart-related routes for managing shopping carts.
 */


/**
 * @OA\Post(
 *     path="/api/carts",
 *     summary="Create a new cart for a user",
 *     tags={"Carts"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"user_id"},
 *             @OA\Property(property="user_id", type="integer", example=1)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Cart created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="cart_id", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Failed to create cart"
 *     )
 * )
 */
Flight::route('POST /api/carts', function() {
    $data = Flight::request()->data;
    
    try {
        $cartId = Flight::cart_service()->createCart($data['user_id']);
        Flight::json(['status' => 'success', 'cart_id' => $cartId], 201);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
});

/**
 * @OA\Get(
 *     path="/api/carts/user/{user_id}",
 *     summary="Get cart for a specific user",
 *     tags={"Carts"},
 *     @OA\Parameter(
 *         name="user_id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Cart retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Cart not found"
 *     )
 * )
 */
Flight::route('GET /api/carts/user/@user_id', function($user_id) {
    try {
        $cart = Flight::cart_service()->getCartByUser($user_id);
        Flight::json(['status' => 'success', 'data' => $cart], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 404);
    }
});
