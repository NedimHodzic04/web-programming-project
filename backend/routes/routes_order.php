<?php
/**
 * Order Routes
 *
 * Handles all order-related routes for creating and managing orders.
 */

/**
 * @OA\Post(
 * path="/api/orders",
 * summary="Create a new order",
 * tags={"Orders"},
 * security={{"BearerAuth":{}}},
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * required={"user_id", "total_price"},
 * @OA\Property(property="user_id", type="integer"),
 * @OA\Property(property="total_price", type="number", format="float"),
 * @OA\Property(
 * property="products",
 * type="array",
 * description="Array of products in the order",
 * @OA\Items(
 * type="object",
 * required={"product_id", "quantity"},
 * @OA\Property(property="product_id", type="integer"),
 * @OA\Property(property="quantity", type="integer")
 * )
 * )
 * )
 * ),
 * @OA\Response(
 * response=201,
 * description="Order created successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(property="order_id", type="integer")
 * )
 * ),
 * @OA\Response(
 * response=400,
 * description="Failed to create order - Invalid input"
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
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('POST /api/orders', function() {
    $loggedInUser = Flight::get('user');
    $data = Flight::request()->data;

    // AUTHORIZATION: User can only create orders for themselves unless they are an admin.
    if (!isset($data['user_id']) || ($loggedInUser->id != $data['user_id'] && $loggedInUser->role !== Config::ADMIN_ROLE())) {
        Flight::halt(403, "Access denied: You can only create orders for yourself unless you are an admin.");
    }

    try {
        // Create the order first
        $orderId = Flight::order_service()->createOrder($data['user_id'], $data['total_price']);

        // If products are provided in the payload, add them to the order
        if (isset($data['products']) && is_array($data['products'])) {
            Flight::orderProducts_service()->addProductsToOrder($orderId, $data['products']);
        }
        
        Flight::json([
            'status' => 'success',
            'order_id' => $orderId
        ], 201);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Get(
 * path="/api/orders/user/{user_id}",
 * summary="Get all orders for a user",
 * tags={"Orders"},
 * security={{"BearerAuth":{}}},
 * @OA\Parameter(
 * name="user_id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Response(
 * response=200,
 * description="Orders retrieved successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(
 * property="data",
 * type="array",
 * @OA\Items(
 * type="object",
 * @OA\Property(property="id", type="integer"),
 * @OA\Property(property="user_id", type="integer"),
 * @OA\Property(property="total_price", type="number", format="float"),
 * @OA\Property(property="order_date", type="string", format="date-time")
 * )
 * )
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
 * description="No orders found for the given user or user not found"
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('GET /api/orders/user/@user_id', function($user_id) {
    $loggedInUser = Flight::get('user');

    // AUTHORIZATION: Check if the requested user_id matches the logged-in user's ID
    // OR if the logged-in user is an admin.
    if ($loggedInUser->id != $user_id && $loggedInUser->role !== Config::ADMIN_ROLE()) {
        Flight::halt(403, "Access denied: You can only view your own orders unless you are an admin.");
    }

    try {
        $orders = Flight::order_service()->getUserOrders($user_id);
        Flight::json(['status' => 'success', 'data' => $orders], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 404;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Get(
 * path="/api/orders",
 * summary="Get all orders (Admin only)",
 * tags={"Orders"},
 * security={{"BearerAuth":{}}},
 * @OA\Response(
 * response=200,
 * description="List of all orders",
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
Flight::route('GET /api/orders', function() {
    // AUTHORIZATION: Only admins can get a list of all orders
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());
    try {
        $orders = Flight::order_service()->getAll(); // Assuming getAll method exists in OrderService
        Flight::json(['status' => 'success', 'data' => $orders], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 500;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Get(
 * path="/api/orders/{id}",
 * summary="Get a single order by ID",
 * tags={"Orders"},
 * security={{"BearerAuth":{}}},
 * @OA\Parameter(
 * name="id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Response(
 * response=200,
 * description="Order retrieved successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string"),
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
 * description="Order not found"
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('GET /api/orders/@id', function($id) {
    $loggedInUser = Flight::get('user');
    try {
        $order = Flight::order_service()->getById($id); // Assuming getById method exists in OrderService
        if (!$order) {
            Flight::halt(404, "Order not found.");
        }
        // AUTHORIZATION: User can get their own order by ID. Admin can get any order by ID.
        if ($loggedInUser->id != $order['user_id'] && $loggedInUser->role !== Config::ADMIN_ROLE()) {
            Flight::halt(403, "Access denied: You can only view your own orders unless you are an admin.");
        }
        Flight::json(['status' => 'success', 'data' => $order], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 404;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Delete(
 * path="/api/orders/{id}",
 * summary="Delete an order (Admin only)",
 * tags={"Orders"},
 * security={{"BearerAuth":{}}},
 * @OA\Parameter(
 * name="id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Response(
 * response=200,
 * description="Order deleted successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string"),
 * @OA\Property(property="deleted", type="boolean")
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
 * description="Order not found"
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('DELETE /api/orders/@id', function($id) {
    // AUTHORIZATION: Only admins can delete orders
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());
    try {
        $result = Flight::order_service()->delete($id); // Assuming delete method exists in OrderService
        if ($result) {
            Flight::json(['status' => 'success', 'deleted' => $result], 200);
        } else {
            Flight::halt(404, "Order not found or failed to delete.");
        }
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});