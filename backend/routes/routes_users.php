<?php
/**
 * User Routes
 *
 * Handles all user-related routes for authentication,
 * registration, profile management, etc.
 */


/**
 * @OA\Post(
 * path="/api/register",
 * summary="Register a new user",
 * tags={"User"},
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * required={"email","password","first_name","last_name","city","address","zip"},
 * @OA\Property(property="email", type="string", format="email"),
 * @OA\Property(property="password", type="string", format="password"),
 * @OA\Property(property="first_name", type="string"),
 * @OA\Property(property="last_name", type="string"),
 * @OA\Property(property="city", type="string"),
 * @OA\Property(property="address", type="string"),
 * @OA\Property(property="zip", type="string")
 * )
 * ),
 * @OA\Response(
 * response=201,
 * description="User registered successfully",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string"),
 * @OA\Property(property="message", type="string"),
 * @OA\Property(property="data", type="object")
 * )
 * ),
 * @OA\Response(
 * response=400,
 * description="Bad request - Missing required fields or invalid input"
 * ),
 * @OA\Response(
 * response=409,
 * description="Conflict - Email already registered"
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('POST /api/register', function() {
    $data = Flight::request()->data;

    try {
        // Call AuthService for registration (it now throws exceptions)
        $registeredUser = Flight::auth_service()->register($data);
        Flight::json(['status' => 'success', 'message' => 'User registered successfully.', 'data' => $registeredUser], 201);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400; // Use exception code (e.g., 409 for conflict)
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Post(
 * path="/api/login",
 * summary="User login",
 * tags={"User"},
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * required={"email","password"},
 * @OA\Property(property="email", type="string", format="email"),
 * @OA\Property(property="password", type="string", format="password")
 * )
 * ),
 * @OA\Response(
 * response=200,
 * description="Login successful",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string"),
 * @OA\Property(
 * property="data",
 * type="object",
 * @OA\Property(property="user", type="object"),
 * @OA\Property(property="token", type="string")
 * )
 * )
 * ),
 * @OA\Response(
 * response=400,
 * description="Bad request - Email and password are required"
 * ),
 * @OA\Response(
 * response=401,
 * description="Unauthorized - Invalid email or password"
 * )
 * )
 */
Flight::route('POST /api/login', function() {
    $data = Flight::request()->data;

    try {
        // Call AuthService for login (it now throws exceptions)
        $loginResult = Flight::auth_service()->login($data); // This returns token and user data
        Flight::json(['status' => 'success', 'data' => $loginResult], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 401;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Get(
 * path="/api/users/{email}",
 * summary="Get user by email",
 * tags={"User"},
 * security={{"ApiKey":{}}},
 * @OA\Parameter(
 * name="email",
 * in="path",
 * required=true,
 * @OA\Schema(type="string", format="email")
 * ),
 * @OA\Response(
 * response=200,
 * description="User found",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string"),
 * @OA\Property(
 * property="data",
 * type="object",
 * @OA\Property(property="id", type="integer"),
 * @OA\Property(property="first_name", type="string"),
 * @OA\Property(property="last_name", type="string"),
 * @OA\Property(property="email", type="string"),
 * @OA\Property(property="role", type="string")
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
 * description="User not found"
 * )
 * )
 */
Flight::route('GET /api/users/@email', function($email) {
    $loggedInUser = Flight::get('user'); // Get user data from authentication middleware

    // AUTHORIZATION: Only admin can fetch any user by email.
    // A regular user can only fetch their own profile by email.
    if ($loggedInUser->email !== $email && $loggedInUser->role !== Config::ADMIN_ROLE()) {
        Flight::halt(403, "Access denied: You can only view your own profile unless you are an admin.");
    }

    try {
        $user = Flight::user_service()->getUserByEmail($email);
        unset($user['password']); // Always remove password before sending to client
        Flight::json(['status' => 'success', 'data' => $user], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 404;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Get(
 * path="/api/users",
 * summary="Get all users (Admin only)",
 * tags={"User"},
 * security={{"ApiKey":{}}},
 * @OA\Response(
 * response=200,
 * description="List of all users",
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
Flight::route('GET /api/users', function() {
    // AUTHORIZATION: Only admins can get a list of all users
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());

    try {
        $users = Flight::user_service()->getAllUsers(); // Assuming you've added this method to UserService
        // Remove passwords from all users before sending
        $users = array_map(function($user) {
            unset($user['password']);
            return $user;
        }, $users);
        Flight::json(['status' => 'success', 'data' => $users], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 500;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});


/**
 * @OA\Put(
 * path="/api/users/{id}",
 * summary="Update user",
 * tags={"User"},
 * security={{"ApiKey":{}}},
 * @OA\Parameter(
 * name="id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * @OA\Property(property="email", type="string", format="email"),
 * @OA\Property(property="first_name", type="string"),
 * @OA\Property(property="last_name", type="string"),
 * @OA\Property(property="city", type="string"),
 * @OA\Property(property="address", type="string"),
 * @OA\Property(property="zip", type="string")
 * )
 * ),
 * @OA\Response(
 * response=200,
 * description="User updated",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string"),
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
 * description="Forbidden - Access denied due to user mismatch or insufficient privileges"
 * ),
 * @OA\Response(
 * response=404,
 * description="User not found"
 * ),
 * @OA\Response(
 * response=409,
 * description="Conflict - Email already taken"
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('PUT /api/users/@id', function($id) {
    $loggedInUser = Flight::get('user'); // Get user data from authentication middleware

    // AUTHORIZATION: User can update their own profile OR Admin can update any profile
    if ($loggedInUser->id != $id && $loggedInUser->role !== Config::ADMIN_ROLE()) {
        Flight::halt(403, "Access denied: You can only update your own profile unless you are an admin.");
    }

    $data = Flight::request()->data;

    try {
        $result = Flight::user_service()->updateUser($id, $data);
        Flight::json(['status' => 'success', 'updated' => $result], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Delete(
 * path="/api/users/{id}",
 * summary="Delete user (Admin only)",
 * tags={"User"},
 * security={{"ApiKey":{}}},
 * @OA\Parameter(
 * name="id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\Response(
 * response=200,
 * description="User deleted",
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
 * description="User not found"
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('DELETE /api/users/@id', function($id) {
    // AUTHORIZATION: Only admins can delete users
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());

    try {
        $result = Flight::user_service()->deleteUser($id); // Assuming deleteUser method in UserService
        Flight::json(['status' => 'success', 'deleted' => $result], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});

/**
 * @OA\Patch(
 * path="/api/users/{id}/role",
 * summary="Update user role (Admin only)",
 * tags={"User"},
 * security={{"ApiKey":{}}},
 * @OA\Parameter(
 * name="id",
 * in="path",
 * required=true,
 * @OA\Schema(type="integer")
 * ),
 * @OA\RequestBody(
 * required=true,
 * @OA\JsonContent(
 * required={"role"},
 * @OA\Property(property="role", type="string", example="admin", enum={"user", "admin"})
 * )
 * ),
 * @OA\Response(
 * response=200,
 * description="User role updated",
 * @OA\JsonContent(
 * @OA\Property(property="status", type="string"),
 * @OA\Property(property="message", type="string"),
 * @OA\Property(property="updated", type="boolean")
 * )
 * ),
 * @OA\Response(
 * response=400,
 * description="Bad request - New role is required or invalid"
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
 * description="User not found"
 * ),
 * @OA\Response(
 * response=500,
 * description="Internal server error"
 * )
 * )
 */
Flight::route('PATCH /api/users/@id/role', function($id) {
    // AUTHORIZATION: Only admins can change user roles
    Flight::auth_middleware_instance()->authorizeRole(Config::ADMIN_ROLE());

    $data = Flight::request()->data;
    $newRole = $data['role'] ?? null;

    if (!$newRole) {
        Flight::halt(400, "New role is required.");
    }

    try {
        $result = Flight::user_service()->updateRole($id, $newRole); // Assuming updateRole method in UserService
        Flight::json(['status' => 'success', 'message' => "User role updated.", 'updated' => $result], 200);
    } catch (Exception $e) {
        $statusCode = $e->getCode() ?: 400;
        Flight::json(['status' => 'error', 'message' => $e->getMessage()], $statusCode);
    }
});