<?php
/**
 * Cart Routes
 *
 * Handles all cart-related routes for managing shopping carts.
 */


/**
 * @OA\Post(
 * path="/api/carts",
 * summary="Create a new cart for a user",
 * tags={"Carts"},
 * security={{"ApiKey":{}}},
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * required={"user_id"},
 * @OA\Property(property="user_id", type="integer", example=1)
 * )
 * ),
 * @OA\Response(
 * response=201,
 * description="Cart created successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(property="cart_id", type="integer")
 * )
 * ),
 * @OA\Response(
 * response=400,
 * description="Failed to create cart - Invalid user ID"
 * ),
 * @OA\Response(
 * response=401,
 * description="Unauthorized - Token not provided or invalid"
 * ),
 * @OA\Response(
 * response=403,
 * description="Forbidden - Access denied due to user mismatch or insufficient privileges"
 * )
 * )
 */
Flight::route('POST /api/carts', function() {
    $loggedInUser = Flight::get('user');
    $data = Flight::request()->data;

    // AUTHORIZATION: Check if the user_id in the request matches the logged-in user's ID
    // OR if the logged-in user is an admin.
    if (!isset($data['user_id']) || ($loggedInUser->id != $data['user_id'] && $loggedInUser->role !== Config::ADMIN_ROLE())) {
        Flight::halt(403, "Access denied: You can only create a cart for yourself unless you are an admin.");
    }

    try {
        $cartId = Flight::cart_service()->createCart($data['user_id']);
        Flight::json(['status' => 'success', 'cart_id' => $cartId], 201);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Get(
 * path="/api/carts/user/{user_id}",
 * summary="Get cart for a specific user",
 * tags={"Carts"},
 * security={{"BearerAuth":{}}},
 * @OA\Parameter(
 * name="user_id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Response(
 * response=200,
 * description="Cart retrieved successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(property="data", type="object", nullable=true)
 * )
 * ),
 * @OA\Response(
 * response=401,
 * description="Unauthorized - Token not provided or invalid"
 * ),
 * @OA\Response(
 * response=403,
 * description="Forbidden - Access denied due to user mismatch or insufficient privileges"
 * ),
 * @OA\Response(
 * response=404,
 * description="Cart not found for this user"
 * )
 * )
 */
Flight::route('GET /api/carts/user/@user_id', function($user_id) {
    $loggedInUser = Flight::get('user');

    // AUTHORIZATION: Check if the requested user_id matches the logged-in user's ID
    // OR if the logged-in user is an admin.
    if ($loggedInUser->id != $user_id && $loggedInUser->role !== Config::ADMIN_ROLE()) {
        Flight::halt(403, "Access denied: You can only view your own cart unless you are an admin.");
    }

    try {
        $cart = Flight::cart_service()->getCartByUser($user_id);
        if (!$cart) {
            // Return 200 with null data or an empty object if cart doesn't exist but user is authorized
            // You might choose to auto-create here as well, depending on UX.
            Flight::json(['status' => 'success', 'data' => null, 'message' => 'Cart not found for this user.'], 200);
            return;
        }
        Flight::json(['status' => 'success', 'data' => $cart], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 404;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Get(
 * path="/api/carts/{id}",
 * summary="Get cart by ID",
 * tags={"Carts"},
 * security={{"BearerAuth":{}}},
 * @OA\Parameter(
 * name="id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Response(
 * response=200,
 * description="Cart retrieved successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(property="data", type="object")
 * )
 * ),
 * @OA\Response(
 * response=401,
 * description="Unauthorized - Token not provided or invalid"
 * ),
 * @OA\Response(
 * response=403,
 * description="Forbidden - Access denied due to user mismatch or insufficient privileges"
 * ),
 * @OA\Response(
 * response=404,
 * description="Cart not found"
 * )
 * )
 */
Flight::route('GET /api/carts/@id', function($id) {
    $loggedInUser = Flight::get('user');
    
    try {
        $cart = Flight::cart_service()->getCartById($id); // Assuming CartService has getCartById

        if (!$cart) {
            Flight::halt(404, "Cart not found.");
        }

        // AUTHORIZATION: A user can only get their own cart by ID. Admin can get any cart by ID.
        if ($loggedInUser->id != $cart['user_id'] && $loggedInUser->role !== Config::ADMIN_ROLE()) {
            Flight::halt(403, "Access denied: You cannot view this cart unless you are an admin.");
        }

        Flight::json(['status' => 'success', 'data' => $cart], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 404;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Delete(
 * path="/api/carts/{id}",
 * summary="Delete a cart (Admin only)",
 * tags={"Carts"},
 * security={{"BearerAuth":{}}},
 * @OA\Parameter(
 * name="id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Response(
 * response=200,
 * description="Cart deleted successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(property="message", type="string")
 * )
 * ),
 * @OA\Response(
 * response=400,
 * description="Bad request - Failed to delete cart"
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
 * description="Cart not found"
 * )
 * )
 */
Flight::route('DELETE /api/carts/@id', function($id) {
    // AUTHORIZATION: Only admins can delete carts
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());

    try {
        $result = Flight::cart_service()->deleteCart($id); // Assuming CartService has deleteCart
        if ($result) {
            Flight::json(['status' => 'success', 'message' => 'Cart deleted successfully.'], 200);
        } else {
            // If delete() returns false, it likely means the cart wasn't found or another DB issue.
            // Adjust status code based on common scenarios (404 if not found).
            Flight::halt(404, "Cart not found or failed to delete.");
        }
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});