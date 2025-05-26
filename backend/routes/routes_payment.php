<?php
/**
 * Payment Routes
 *
 * Handles all payment-related routes for processing and managing payments.
 */


/**
 * @OA\Post(
 * path="/api/payments",
 * summary="Process a payment for an order",
 * tags={"Payments"},
 * security={{"BearerAuth":{}}},
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * required={"order_id", "user_id", "total_amount"},
 * @OA\Property(property="order_id", type="integer"),
 * @OA\Property(property="user_id", type="integer"),
 * @OA\Property(property="total_amount", type="number", format="float")
 * )
 * ),
 * @OA\Response(
 * response=201,
 * description="Payment processed successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(property="payment_id", type="integer")
 * )
 * ),
 * @OA\Response(
 * response=400,
 * description="Failed to process payment"
 * ),
 * @OA\Response(
 * response=401,
 * description="Unauthorized - Token not provided or invalid"
 * ),
 * @OA\Response(
 * response=403,
 * description="Forbidden - Access denied due to insufficient privileges or user mismatch"
 * )
 * )
 */
Flight::route('POST /api/payments', function() {
    $loggedInUser = Flight::get('user'); // Get user data from authentication middleware

    $data = Flight::request()->data;

    // AUTHORIZATION: Ensure the payment is being processed for the logged-in user,
    // or by an admin for any user.
    if (!isset($data['user_id']) || ($loggedInUser->id != $data['user_id'] && $loggedInUser->role !== Config::ADMIN_ROLE())) {
        Flight::halt(403, "Access denied: You can only process payments for your own user ID unless you are an admin.");
    }

    try {
        $paymentId = Flight::payment_service()->processPayment(
            $data['order_id'],
            $data['user_id'],
            $data['total_amount']
        );

        Flight::json([
            'status' => 'success',
            'payment_id' => $paymentId
        ], 201);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400; // Use exception code if available, otherwise 400
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Get(
 * path="/api/payments/order/{order_id}",
 * summary="Get payments for a specific order",
 * tags={"Payments"},
 * security={{"BearerAuth":{}}},
 * @OA\Parameter(
 * name="order_id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Response(
 * response=200,
 * description="Payments retrieved successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string", example="success"),
 * @OA\Property(
 * property="data",
 * type="array",
 * @OA\Items(
 * type="object",
 * @OA\Property(property="id", type="integer"),
 * @OA\Property(property="order_id", type="integer"),
 * @OA\Property(property="user_id", type="integer"),
 * @OA\Property(property="amount", type="number", format="float"),
 * @OA\Property(property="date", type="string", format="date-time")
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
 * description="Forbidden - Access denied due to insufficient privileges or user mismatch"
 * ),
 * @OA\Response(
 * response=404,
 * description="No payments found for the given order or order not found"
 * )
 * )
 */
Flight::route('GET /api/payments/order/@order_id', function($order_id) {
    $loggedInUser = Flight::get('user');

    try {
        // Fetch the order first to check its user_id for authorization
        $order = Flight::order_service()->getById($order_id); // Assuming getById exists in OrderService
        if (!$order) {
            Flight::halt(404, "Order not found.");
        }

        // AUTHORIZATION: User can only see payments for their own order unless they are an admin
        if ($loggedInUser->id != $order['user_id'] && $loggedInUser->role !== Config::ADMIN_ROLE()) {
            Flight::halt(403, "Access denied: You can only view payments for your own orders unless you are an admin.");
        }

        $payments = Flight::payment_service()->getPaymentsForOrder($order_id);

        if (empty($payments)) {
            // If no payments are found, Flight::json with a message and 404 (or empty array)
            Flight::json(['status' => 'success', 'data' => [], 'message' => 'No payments found for the given order.'], 200); // Changed to 200 with empty array, as per common API practice for 'no results'
            return;
        }

        Flight::json(['status' => 'success', 'data' => $payments], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 404;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});