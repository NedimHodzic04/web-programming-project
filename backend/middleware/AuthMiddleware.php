<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;
// You might need to add use for other specific exceptions like UnexpectedValueException
// if you encounter issues with malformed tokens not caught by the generic Exception.

class AuthMiddleware {

   /**
    * Verifies the JWT token.
    * If valid, sets Flight::set('user', $decoded_token->user) and Flight::set('jwt_token', $token).
    * If invalid (missing, expired, bad signature, etc.), sends a JSON error response and stops execution.
    * @param string|null $token The JWT token string (without "Bearer " prefix).
    * @return bool True if token is valid and processed, false otherwise (though script usually stops on error).
    */

    public function verifyTokenFromHeaders() {
    error_log("AuthMiddleware: verifyTokenFromHeaders() called.");

    // Normalize headers to lowercase for consistent access
    $headers = array_change_key_case(getallheaders(), CASE_LOWER);
    error_log("AuthMiddleware: Headers received: " . json_encode($headers));

    // Extract the Authorization header (case-insensitive)
    $authHeader = $headers['authorization'] ?? null;

    if (!$authHeader) {
        error_log("AuthMiddleware Error: Missing Authorization header.");
        Flight::response()->status(401);
        Flight::json(['status' => 'error', 'message' => 'Missing Authorization header.']);
        Flight::stop();
        return false;
    }

    // Expect header format: "Bearer TOKEN"
    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        error_log("AuthMiddleware Error: Invalid Authorization header format. Value: $authHeader");
        Flight::response()->status(401);
        Flight::json(['status' => 'error', 'message' => 'Invalid Authorization header format.']);
        Flight::stop();
        return false;
    }

    $token = $matches[1];
    error_log("AuthMiddleware: Token extracted successfully: " . substr($token, 0, 20) . "...");

    // Delegate to token validator
    return $this->verifyToken($token);
}
   public function verifyToken($token){
       if(!$token) {
           Flight::response()->status(401);
           Flight::json(['status' => 'error', 'message' => 'Missing authorization token.']);
           Flight::stop(); // Stop further execution
           return false; 
       }

       try {
           // Ensure Config::JWT_SECRET() is a static method returning your secret key string
           if (!method_exists('Config', 'JWT_SECRET')) {
               error_log("AuthMiddleware CRITICAL Error: Config::JWT_SECRET() method not found.");
               Flight::response()->status(500);
               Flight::json(['status' => 'error', 'message' => 'Server configuration error (JWT secret setup).']);
               Flight::stop();
               return false;
           }
           $secretKey = Config::JWT_SECRET(); // [cite: Config.php]
           if (empty($secretKey)) {
               error_log("AuthMiddleware CRITICAL Error: Config::JWT_SECRET() returned an empty secret.");
               Flight::response()->status(500);
               Flight::json(['status' => 'error', 'message' => 'Server configuration error (JWT secret is empty).']);
               Flight::stop();
               return false;
           }

           $decoded_token = JWT::decode($token, new Key($secretKey, 'HS256'));
           
           // Check if the 'user' object and 'user->role' exist in the decoded token payload
           if (!isset($decoded_token->user) || !is_object($decoded_token->user) || !isset($decoded_token->user->role)) {
               error_log("AuthMiddleware Error: Token is missing 'user' object or 'user->role'. Payload: " . print_r($decoded_token, true));
               Flight::response()->status(401);
               Flight::json(['status' => 'error', 'message' => 'Invalid token: user data or role missing in token payload.']);
               Flight::stop();
               return false;
           }

           Flight::set('user', $decoded_token->user); // Contains id, email, role, first_name etc.
           Flight::set('jwt_token', $token);     // Store the raw token string if needed elsewhere
           return true; // Token is valid and processed

       } catch (ExpiredException $e) {
           error_log("AuthMiddleware Error: Token has expired - " . $e->getMessage());
           Flight::response()->status(401);
           Flight::json(['status' => 'error', 'message' => 'Token has expired. Please log in again.']);
           Flight::stop();
       } catch (SignatureInvalidException $e) {
           error_log("AuthMiddleware Error: Token signature verification failed - " . $e->getMessage());
           Flight::response()->status(401);
           Flight::json(['status' => 'error', 'message' => 'Invalid token signature. Please log in again.']);
           Flight::stop();
       } catch (BeforeValidException $e) {
           error_log("AuthMiddleware Error: Token is not yet valid (check nbf claim) - " . $e->getMessage());
           Flight::response()->status(401);
           Flight::json(['status' => 'error', 'message' => 'Token not yet valid.']);
           Flight::stop();
       } catch (Exception $e) { // Catch other generic JWT (like malformed token from UnexpectedValueException) or unexpected errors
           error_log("AuthMiddleware Error: Token validation error - " . $e->getMessage() . " | Token (first 20 chars): " . substr($token, 0, 20));
           Flight::response()->status(401);
           Flight::json(['status' => 'error', 'message' => 'Invalid token: ' . $e->getMessage()]);
           Flight::stop();
       }
       return false; // Should ideally not be reached if Flight::stop() is called in catch blocks
   }

   /**
    * Authorizes a user based on a single required role.
    * @param string $requiredRole The role string required to access the resource.
    */
   public function authorizeRole($requiredRole) {
       $user = Flight::get('user');
       
       if (!$user || !isset($user->role)) {
           error_log("AuthMiddleware authorizeRole Error: User object or role not set in Flight registry. Cannot authorize.");
           Flight::response()->status(401); // Unauthorized, as user data isn't properly authenticated/set
           Flight::json(['status' => 'error', 'message' => 'Authentication error: User data not available for role check.']);
           Flight::stop();
           return; // Defensive return
       }

       if ($user->role !== $requiredRole) {
           error_log("AuthMiddleware authorizeRole: Access Denied. User role: '{$user->role}', Required role: '{$requiredRole}' for user ID: {$user->id}");
           Flight::response()->status(403); // Forbidden
           Flight::json(['status' => 'error', 'message' => 'Access denied: Insufficient privileges.']);
           Flight::stop();
       }
   }

   /**
    * Authorizes a user based on a list of allowed roles.
    * @param array $roles Array of role strings that are allowed.
    */
   public function authorizeRoles($roles) { 
       $user = Flight::get('user');

       if (!$user || !isset($user->role)) {
           error_log("AuthMiddleware authorizeRoles Error: User object or role not set in Flight registry. Cannot authorize.");
           Flight::response()->status(401);
           Flight::json(['status' => 'error', 'message' => 'Authentication error: User data not available for roles check.']);
           Flight::stop();
           return;
       }

       if (!is_array($roles)) {
            error_log("AuthMiddleware authorizeRoles Error: \$roles parameter passed is not an array. Check route definition.");
            Flight::response()->status(500); // Internal server error - misconfiguration
            Flight::json(['status' => 'error', 'message' => 'Server configuration error (authorization roles definition).']);
            Flight::stop();
            return;
       }

       if (!in_array($user->role, $roles)) {
           error_log("AuthMiddleware authorizeRoles: Access Denied. User role: '{$user->role}', Allowed roles: [" . implode(', ', $roles) . "] for user ID: {$user->id}");
           Flight::response()->status(403); // Forbidden
           Flight::json(['status' => 'error', 'message' => 'Forbidden: Your role (' . htmlspecialchars($user->role) . ') is not allowed for this resource.']);
           Flight::stop();
       }
   }

   /**
    * Authorizes a user based on a specific permission.
    * Assumes permissions are an array within the $user object (e.g., $user->permissions = ['read_product', 'write_product'])
    * @param string $permission The permission string required.
    */
   function authorizePermission($permission) {
       $user = Flight::get('user');

       if (!$user || !isset($user->permissions) || !is_array($user->permissions)) {
           error_log("AuthMiddleware authorizePermission Error: User object or permissions array not set/valid in Flight registry. Cannot authorize.");
           Flight::response()->status(401); // Or 500 if this implies a server logic error in setting up the user object
           Flight::json(['status' => 'error', 'message' => 'Authentication error: User permissions not available.']);
           Flight::stop();
           return;
       }

       if (!in_array($permission, $user->permissions)) {
           error_log("AuthMiddleware authorizePermission: Access Denied. User ID: {$user->id} missing required permission: '{$permission}'. User permissions: [" . implode(', ', $user->permissions) . "]");
           Flight::response()->status(403); // Forbidden
           Flight::json(['status' => 'error', 'message' => 'Access denied: Required permission (' . htmlspecialchars($permission) . ') missing.']);
           Flight::stop();
       }
   }   
}
