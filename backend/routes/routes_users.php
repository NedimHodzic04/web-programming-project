<?php
/**
 * User Routes
 * 
 * Handles all user-related routes for authentication, 
 * registration, profile management, etc.
 */


/**
 * @OA\Post(
 *     path="/api/register",
 *     summary="Register a new user",
 *     tags={"User"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","password","name"},
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="password", type="string", format="password"),
 *             @OA\Property(property="first_name", type="string"),
 *             @OA\Property(property="last_name", type="string"),
 *             @OA\Property(property="city", type="string"),
 *             @OA\Property(property="address", type="string"),
 *             @OA\Property(property="zip", type="string"),
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="User registered successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="message", type="string")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Bad request"
 *     )
 * )
 */
Flight::route('POST /api/register', function() {
    $data = Flight::request()->data;

    try {
        $result = Flight::user_service()->register($data);
        Flight::json(['status' => 'success', 'message' => $result], 201);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
});

/**
 * @OA\Post(
 *     path="/api/login",
 *     summary="User login",
 *     tags={"User"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"email","password"},
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="password", type="string", format="password")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Login successful",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="name", type="string"),
 *                 @OA\Property(property="email", type="string")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized"
 *     )
 * )
 */
Flight::route('POST /api/login', function() {
    $data = Flight::request()->data;

    try {
        $user = Flight::user_service()->login($data['email'], $data['password']);
        unset($user['password']);
        Flight::json(['status' => 'success', 'data' => $user], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 401);
    }
});

/**
 * @OA\Get(
 *     path="/api/users/{email}",
 *     summary="Get user by email",
 *     tags={"User"},
 *     @OA\Parameter(
 *         name="email",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="string", format="email")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="User found",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id", type="integer"),
 *                 @OA\Property(property="name", type="string"),
 *                 @OA\Property(property="email", type="string")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="User not found"
 *     )
 * )
 */
Flight::route('GET /api/users/@email', function($email) {
    try {
        $user = Flight::user_service()->getUserByEmail($email);
        unset($user['password']);
        Flight::json(['status' => 'success', 'data' => $user], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 404);
    }
});

/**
 * @OA\Put(
 *     path="/api/users/{id}",
 *     summary="Update user",
 *     tags={"User"},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="email", type="string", format="email"),
 *             @OA\Property(property="name", type="string"),
 *             @OA\Property(property="password", type="string", format="password")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="User updated",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string"),
 *             @OA\Property(property="updated", type="boolean")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Update error"
 *     )
 * )
 */
Flight::route('PUT /api/users/@id', function($id) {
    $data = Flight::request()->data;

    try {
        $result = Flight::user_service()->updateUser($id, $data);
        Flight::json(['status' => 'success', 'updated' => $result], 200);
    } catch (Exception $e) {
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], 400);
    }
});
