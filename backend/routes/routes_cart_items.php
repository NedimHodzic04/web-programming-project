<?php
/**
 * Cart Items Routes
 *
 * Handles all cart item-related routes for managing items within shopping carts.
 */

/**
 * @OA\Get(
 * path="/api/cart-items/{cart_id}",
 * summary="Get all items in a cart",
 * tags={"Cart Items"},
 * security={{"ApiKey":{}}},
 * @OA\Parameter(
 * name="cart_id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Response(
 * response=200,
 * description="Items retrieved successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(property="data", type="array", @OA\Items(type="object"))
 * )
 * ),
 * @OA\Response(
 * response=401,
 * description="Unauthorized - Token not provided or invalid"
 * ),
 * @OA\Response(
 * response=403,
 * description="Forbidden - Access denied due to cart mismatch or insufficient privileges"
 * ),
 * @OA\Response(
 * response=404,
 * description="Cart not found"
 * )
 * )
 */
Flight::route('GET /api/cart-items/@cart_id', function($cart_id) {
    $loggedInUser = Flight::get('user');

    // Verify ownership of the cart
    $cart = Flight::cart_service()->getCartById($cart_id); // Assuming CartService has getCartById
    if (!$cart) {
        Flight::halt(404, "Cart not found.");
    }
    if ($loggedInUser->id != $cart['user_id'] && $loggedInUser->role !== Config::ADMIN_ROLE()) {
        Flight::halt(403, "Access denied: You can only view items from your own cart unless you are an admin.");
    }

    try {
        $items = Flight::cartItems_service()->getCartItems($cart_id);
        Flight::json(['status' => 'success', 'data' => $items], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 404;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Post(
 * path="/api/cart-items",
 * summary="Add item to cart (or update quantity if it exists)",
 * tags={"Cart Items"},
 * security={{"ApiKey":{}}},
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * required={"product_id", "quantity"},
 * @OA\Property(property="product_id", type="integer", example=101),
 * @OA\Property(property="quantity", type="integer", example=2)
 * )
 * ),
 * @OA\Response(
 * response=201,
 * description="Item added/updated in cart",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(property="added", type="boolean"),
 * @OA\Property(property="cart_id", type="integer")
 * )
 * ),
 * @OA\Response(
 * response=400,
 * description="Bad request - Invalid input"
 * ),
 * @OA\Response(
 * response=401,
 * description="Unauthorized - Token not provided or invalid"
 * ),
 * @OA\Response(
 * response=403,
 * description="Forbidden - Access denied due to user mismatch"
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('POST /api/cart-items', function() {
    $loggedInUser = Flight::get('user');
    $data = Flight::request()->data;

    // Get the user's cart (or create if it doesn't exist)
    $cart = Flight::cart_service()->getCartById($loggedInUser->id);
    if (!$cart) {
        $cartId = Flight::cart_service()->createCart($loggedInUser->id);
        $cart = Flight::cart_service()->getCartById($cartId);
    }
    $user_cart_id = $cart['id'];

    // If a cart_id is explicitly provided in the body, it must match the user's actual cart_id or the user must be an admin.
    // However, for typical "add to cart" flow, the frontend doesn't send cart_id; it's determined by the user's session.
    // If you allow an admin to add to any cart, uncomment this check:
    /*
    if (isset($data['cart_id']) && $data['cart_id'] != $user_cart_id && $loggedInUser->role !== Config::ADMIN_ROLE()) {
        Flight::halt(403, "Access denied: You can only modify your own cart unless you are an admin.");
    }
    */

    try {
        $result = Flight::cartItems_service()->addToCart(
            $user_cart_id, // Always use the determined user's cart ID
            $data['product_id'],
            $data['quantity']
        );
        // It's good practice to return the actual cart ID here
        Flight::json(['status' => 'success', 'added' => $result, 'cart_id' => $user_cart_id], 201);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Put(
 * path="/api/cart-items/{cart_id}/{product_id}",
 * summary="Update cart item quantity",
 * tags={"Cart Items"},
 * security={{"ApiKey":{}}},
 * @OA\Parameter(
 * name="cart_id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Parameter(
 * name="product_id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * required={"quantity"},
 * @OA\Property(property="quantity", type="integer", example=3)
 * )
 * ),
 * @OA\Response(
 * response=200,
 * description="Item quantity updated",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(property="updated", type="boolean")
 * )
 * ),
 * @OA\Response(
 * response=400,
 * description="Bad request - Invalid input"
 * ),
 * @OA\Response(
 * response=401,
 * description="Unauthorized - Token not provided or invalid"
 * ),
 * @OA\Response(
 * response=403,
 * description="Forbidden - Access denied due to cart mismatch or insufficient privileges"
 * ),
 * @OA\Response(
 * response=404,
 * description="Cart or product not found in cart"
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('PUT /api/cart-items/@cart_id/@product_id', function($cart_id, $product_id) {
    $loggedInUser = Flight::get('user');

    // Verify ownership of the cart
    $cart = Flight::cart_service()->getCartById($cart_id);
    if (!$cart) {
        Flight::halt(404, "Cart not found.");
    }
    if ($loggedInUser->id != $cart['user_id'] && $loggedInUser->role !== Config::ADMIN_ROLE()) {
        Flight::halt(403, "Access denied: You can only modify items in your own cart unless you are an admin.");
    }

    $data = Flight::request()->data;

    try {
        $result = Flight::cartItems_service()->updateQuantity(
            $cart_id,
            $product_id,
            $data['quantity']
        );
        Flight::json(['status' => 'success', 'updated' => $result], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Delete(
 * path="/api/cart-items/{cart_id}/{product_id}",
 * summary="Remove item from cart",
 * tags={"Cart Items"},
 * security={{"ApiKey":{}}},
 * @OA\Parameter(
 * name="cart_id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Parameter(
 * name="product_id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Response(
 * response=200,
 * description="Item removed from cart",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(property="removed", type="boolean")
 * )
 * ),
 * @OA\Response(
 * response=400,
 * description="Bad request - Invalid input"
 * ),
 * @OA\Response(
 * response=401,
 * description="Unauthorized - Token not provided or invalid"
 * ),
 * @OA\Response(
 * response=403,
 * description="Forbidden - Access denied due to cart mismatch or insufficient privileges"
 * ),
 * @OA\Response(
 * response=404,
 * description="Cart or product not found in cart"
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('DELETE /api/cart-items/@cart_id/@product_id', function($cart_id, $product_id) {
    $loggedInUser = Flight::get('user');

    // Verify ownership of the cart
    $cart = Flight::cart_service()->getCartById($cart_id);
    if (!$cart) {
        Flight::halt(404, "Cart not found.");
    }
    if ($loggedInUser->id != $cart['user_id'] && $loggedInUser->role !== Config::ADMIN_ROLE()) {
        Flight::halt(403, "Access denied: You can only remove items from your own cart unless you are an admin.");
    }

    try {
        $result = Flight::cartItems_service()->removeItem($cart_id, $product_id);
        Flight::json(['status' => 'success', 'removed' => $result], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Delete(
 * path="/api/cart-items/clear/{cart_id}",
 * summary="Clear all items from cart",
 * tags={"Cart Items"},
 * security={{"ApiKey":{}}},
 * @OA\Parameter(
 * name="cart_id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Response(
 * response=200,
 * description="All items cleared from cart",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(property="cleared", type="boolean")
 * )
 * ),
 * @OA\Response(
 * response=400,
 * description="Bad request - Invalid input"
 * ),
 * @OA\Response(
 * response=401,
 * description="Unauthorized - Token not provided or invalid"
 * ),
 * @OA\Response(
 * response=403,
 * description="Forbidden - Access denied due to cart mismatch or insufficient privileges"
 * ),
 * @OA\Response(
 * response=404,
 * description="Cart not found"
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('DELETE /api/cart-items/clear/@cart_id', function($cart_id) { // Changed path to include /clear/ for distinction
    $loggedInUser = Flight::get('user');

    // Verify ownership of the cart
    $cart = Flight::cart_service()->getCartById($cart_id);
    if (!$cart) {
        Flight::halt(404, "Cart not found.");
    }
    if ($loggedInUser->id != $cart['user_id'] && $loggedInUser->role !== Config::ADMIN_ROLE()) {
        Flight::halt(403, "Access denied: You can only clear your own cart unless you are an admin.");
    }

    try {
        $result = Flight::cartItems_service()->clearCart($cart_id);
        Flight::json(['status' => 'success', 'cleared' => $result], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});