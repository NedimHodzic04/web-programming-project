<?php
/**
 * Cart Items Routes
 * 
 * Handles all cart item-related routes for managing items within shopping carts.
 */

/**
 * @OA\Get(
 *     path="/api/cart-items/{cart_id}",
 *     summary="Get all items in a cart",
 *     tags={"Cart Items"},
 *     @OA\Parameter(
 *         name="cart_id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Items retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Cart not found"
 *     )
 * )
 */
Flight::route('GET /api/cart-items/@cart_id', function($cart_id) {
    try {
        $items = Flight::cartItems_service()->getCartItems($cart_id);
        Flight::json(['status' => 'success', 'data' => $items], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 404);
    }
});

/**
 * @OA\Post(
 *     path="/api/cart-items",
 *     summary="Add item to cart",
 *     tags={"Cart Items"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"cart_id", "product_id", "quantity"},
 *             @OA\Property(property="cart_id", type="integer", example=1),
 *             @OA\Property(property="product_id", type="integer", example=101),
 *             @OA\Property(property="quantity", type="integer", example=2)
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Item added to cart",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="added", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Failed to add item to cart"
 *     )
 * )
 */
Flight::route('POST /api/cart-items', function() {
    $data = Flight::request()->data;
    
    try {
        $result = Flight::cartItems_service()->addToCart(
            $data['cart_id'],
            $data['product_id'],
            $data['quantity']
        );
        Flight::json(['status' => 'success', 'added' => $result], 201);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
});

/**
 * @OA\Put(
 *     path="/api/cart-items/{cart_id}/{product_id}",
 *     summary="Update cart item quantity",
 *     tags={"Cart Items"},
 *     @OA\Parameter(
 *         name="cart_id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Parameter(
 *         name="product_id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"quantity"},
 *             @OA\Property(property="quantity", type="integer", example=3)
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Item quantity updated",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="updated", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Failed to update item"
 *     )
 * )
 */
Flight::route('PUT /api/cart-items/@cart_id/@product_id', function($cart_id, $product_id) {
    $data = Flight::request()->data;
    
    try {
        $result = Flight::cartItems_service()->updateQuantity(
            $cart_id,
            $product_id,
            $data['quantity']
        );
        Flight::json(['status' => 'success', 'updated' => $result], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
});

/**
 * @OA\Delete(
 *     path="/api/cart-items/{cart_id}/{product_id}",
 *     summary="Remove item from cart",
 *     tags={"Cart Items"},
 *     @OA\Parameter(
 *         name="cart_id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Parameter(
 *         name="product_id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Item removed from cart",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="removed", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Failed to remove item"
 *     )
 * )
 */
Flight::route('DELETE /api/cart-items/@cart_id/@product_id', function($cart_id, $product_id) {
    try {
        $result = Flight::cartItems_service()->removeItem($cart_id, $product_id);
        Flight::json(['status' => 'success', 'removed' => $result], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
});

/**
 * @OA\Delete(
 *     path="/api/cart-items/{cart_id}",
 *     summary="Clear all items from cart",
 *     tags={"Cart Items"},
 *     @OA\Parameter(
 *         name="cart_id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="All items cleared from cart",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="cleared", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Failed to clear items"
 *     )
 * )
 */
Flight::route('DELETE /api/cart-items/@cart_id', function($cart_id) {
    try {
        $result = Flight::cartItems_service()->clearCart($cart_id);
        Flight::json(['status' => 'success', 'cleared' => $result], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
});
