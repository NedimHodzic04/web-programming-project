<?php
/**
 * Order Routes
 *
 * Handles all order-related routes for creating and managing orders.
 */

// Important: Move the most specific GET routes to the top
// This prevents broader routes from "catching" more specific URLs.


// Route to get all orders for admin view - THIS MUST BE BEFORE /api/orders/@id
/**
 * @OA\Get(
 * path="/api/orders/all",
 * summary="Get all orders for admin view (Admin only)",
 * tags={"Orders"},
 * security={{"ApiKey":{}}},
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
Flight::route('GET /api/orders/all', function() {
    // AUTHORIZATION: Only admins can access all orders
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());

    $orderService = new OrderService();
    try {
        $orders = $orderService->getAllOrdersForAdmin(); // This calls the correct service method
        Flight::json(['status' => 'success', 'data' => $orders], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $e->getCode() ?: 500);
    }
});


// Route to get detailed information for a single order - THIS MUST BE BEFORE /api/orders/@id
/**
 * @OA\Get(
 * path="/api/orders/{order_id}",
 * summary="Get detailed information for a single order (Admin or Owner)",
 * tags={"Orders"},
 * security={{"ApiKey":{}}},
 * @OA\Parameter(
 * name="order_id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Response(
 * response=200,
 * description="Order details retrieved successfully",
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
 * description="Forbidden - Access denied"
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
Flight::route('GET /api/orders/@order_id', function($order_id) {
    // AUTHORIZATION: Only admins or the owner of the order can access
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE()); // Simplify for now

    $orderService = new OrderService();
    try {
        $orderDetails = $orderService->getOrderDetailsForAdmin($order_id);
        // Authorization check after fetching order data to get user_id from $order
        // if ($loggedInUser->id != $orderDetails['user_id'] && $loggedInUser->role !== Config::ADMIN_ROLE()) {
        //     Flight::halt(403, "Access denied: You can only view your own orders unless you are an admin.");
        // }
        
        Flight::json(['status' => 'success', 'data' => $orderDetails], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 500;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});


// Existing GET /api/orders/user/{user_id}
/**
 * @OA\Get(
 * path="/api/orders/user/{user_id}",
 * summary="Get all orders for a user",
 * tags={"Orders"},
 * security={{"ApiKey":{}}},
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


// POST route
/**
 * @OA\Post(
 * path="/api/orders",
 * summary="Create a new order",
 * tags={"Orders"},
 * security={{"ApiKey":{}}},
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

    if (!isset($data['user_id']) || ($loggedInUser->id != $data['user_id'] && $loggedInUser->role !== Config::ADMIN_ROLE())) {
        Flight::halt(403, "Access denied: You can only create orders for yourself unless you are an admin.");
    }

    try {
        $orderId = Flight::order_service()->createOrder($data['user_id'], $data['total_price']);

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


// DELETE route
/**
 * @OA\Delete(
 * path="/api/orders/{id}",
 * summary="Delete an order (Admin only)",
 * tags={"Orders"},
 * security={{"ApiKey":{}}},
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

// The less specific GET /api/orders route (formerly the second one)
// Should come after /api/orders/all and /api/orders/{id} if those are distinct.
// If /api/orders is meant to be synonymous with /api/orders/all, you can simplify.
// As currently written, this route has an issue if 'getAll' isn't on OrderService.
/**
 * @OA\Get(
 * path="/api/orders",
 * summary="Get all orders (Admin only) - Alternative route",
 * tags={"Orders"},
 * security={{"ApiKey":{}}},
 * @OA\Response(
 * response=200,
 * description="List of all orders",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string"),
 * @OA\Property(property="data", type="array", @OA\Items(type="object"))
 * )
 * )
 * )
 */
Flight::route('GET /api/orders', function() {
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());
    try {
        // Assuming getAll method exists in OrderService
        // If not, use getAllOrdersForAdmin()
        $orders = Flight::order_service()->getAllOrdersForAdmin(); // Corrected method call
        Flight::json(['status' => 'success', 'data' => $orders], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 500;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});