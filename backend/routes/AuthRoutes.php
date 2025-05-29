<?php
use Firebase\JWT\JWT;
// Key class from Firebase\JWT\JWT is not directly used in this file if AuthService handles all JWT ops

Flight::group('/auth', function() {

    /**
     * @OA\Post(
     * path="/auth/register",
     * summary="Register a new user",
     * description="Add a new user to the database.",
     * tags={"auth"},
     * @OA\RequestBody(
     * description="User registration details",
     * required=true,
     * @OA\MediaType(
     * mediaType="application/json",
     * @OA\Schema(
     * required={"email","password","first_name","last_name","city","address","zip"},
     * @OA\Property(property="email", type="string", format="email", example="newuser@example.com"),
     * @OA\Property(property="password", type="string", format="password", example="password123"),
     * @OA\Property(property="first_name", type="string", example="John"),
     * @OA\Property(property="last_name", type="string", example="Doe"),
     * @OA\Property(property="city", type="string", example="New York"),
     * @OA\Property(property="address", type="string", example="123 Main St"),
     * @OA\Property(property="zip", type="string", example="10001")
     * )
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="User registered successfully",
     * @OA\JsonContent(
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="message", type="string", example="User registered successfully."),
     * @OA\Property(property="data", type="object", description="Registered user details (excluding password)") 
     * )
     * ),
     * @OA\Response(response=400, description="Bad request - Missing required fields or invalid input (e.g., 'First name is required.')"),
     * @OA\Response(response=409, description="Conflict - Email already registered"),
     * @OA\Response(response=500, description="Internal server error during registration")
     * )
     */
    Flight::route("POST /register", function () {
        $data = Flight::request()->data->getData();
        try {
            // AuthService->register() returns the new user data directly on success,
            // or throws an Exception on error.
            $registeredUser = Flight::auth_service()->register($data);

            Flight::json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'data' => $registeredUser // $registeredUser is the actual user data
            ], 201); // HTTP 201 Created

        } catch (Exception $e) {
            $statusCode = $e->getCode();
            // Ensure status code is a valid HTTP error code
            if (!is_numeric($statusCode) || $statusCode < 400 || $statusCode >= 600) {
                $statusCode = 500; // Default to 500 if code is invalid or not an HTTP error code
            }
            error_log("AuthRoutes POST /register Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
            Flight::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $statusCode);
        }
    });

    /**
     * @OA\Post(
     * path="/auth/login",
     * tags={"auth"},
     * summary="Login to system using email and password",
     * @OA\RequestBody(
     * description="Credentials",
     * required=true,
     * @OA\JsonContent(
     * required={"email","password"},
     * @OA\Property(property="email", type="string", example="testuser_sunday_2336@example.com", description="User email address"),
     * @OA\Property(property="password", type="string", example="password123", description="User password")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Login successful. User data and JWT.",
     * @OA\JsonContent(
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="message", type="string", example="User logged in successfully."),
     * @OA\Property(property="data", type="object",
     * @OA\Property(property="token", type="string", description="JWT token"),
     * @OA\Property(property="user", type="object", description="User details (excluding password)")
     * )
     * )
     * ),
     * @OA\Response(response=400, description="Bad request - Email and password are required"),
     * @OA\Response(response=401, description="Unauthorized - Invalid email or password"),
     * @OA\Response(response=500, description="Internal server error during login")
     * )
     */
    Flight::route('POST /login', function() {
        $data = Flight::request()->data->getData();
        try {
            // AuthService->login() returns ['token' => ..., 'user' => ...] on success,
            // or throws an Exception on error.
            $loginServiceResult = Flight::auth_service()->login($data);

            // If no exception, it's a success. $loginServiceResult IS the data.
            Flight::json([
                'status' => 'success',
                'message' => 'User logged in successfully',
                'data' => $loginServiceResult // This directly holds 'token' and 'user'
            ], 200);

        } catch (Exception $e) {
            $statusCode = $e->getCode();
            if (!is_numeric($statusCode) || $statusCode < 400 || $statusCode >= 600) {
                $statusCode = 500; 
            }
            error_log("AuthRoutes POST /login Error: " . $e->getMessage() . " (Code: " . $e->getCode() . ") - Trace: " . $e->getTraceAsString());
            Flight::json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], $statusCode);
        }
    });
});
?>
