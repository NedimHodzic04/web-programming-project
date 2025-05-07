<?php
/**
 * Payment Routes
 * 
 * Handles all payment-related routes for processing and managing payments.
 */


/**
 * @OA\Post(
 *     path="/api/payments",
 *     summary="Process a payment for an order",
 *     tags={"Payments"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"order_id", "user_id", "total_amount"},
 *             @OA\Property(property="order_id", type="integer"),
 *             @OA\Property(property="user_id", type="integer"),
 *             @OA\Property(property="total_amount", type="number", format="float")
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Payment processed successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="payment_id", type="integer")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Failed to process payment"
 *     )
 * )
 */
Flight::route('POST /api/payments', function() {
    $data = Flight::request()->data;
    
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
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
});

/**
 * @OA\Get(
 *     path="/api/payments/order/{order_id}",
 *     summary="Get payments for a specific order",
 *     tags={"Payments"},
 *     @OA\Parameter(
 *         name="order_id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Payments retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="order_id", type="integer"),
 *                     @OA\Property(property="user_id", type="integer"),
 *                     @OA\Property(property="amount", type="number", format="float"),
 *                     @OA\Property(property="date", type="string", format="date-time")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="No payments found for the given order"
 *     )
 * )
 */
Flight::route('GET /api/payments/order/@order_id', function($order_id) {
    try {
        $payments = Flight::payment_service()->getPaymentsForOrder($order_id);
        Flight::json(['status' => 'success', 'data' => $payments], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 404);
    }
});
