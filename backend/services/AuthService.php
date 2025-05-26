<?php
require_once __DIR__ . '/../dao/AuthDao.php'; // Correct path to AuthDao
require_once __DIR__ . '/../dao/config.php'; // For Config constants
use Firebase\JWT\JWT;
// Key class from Firebase\JWT\JWT is not strictly needed for encoding if not decoding here

class AuthService {
    private $auth_dao;

    public function __construct() {
        $this->auth_dao = new AuthDao();
    }

    public function get_user_by_email($email){
        return $this->auth_dao->get_user_by_email($email);
    }

    public function register($entity) {  
        // Validate all required fields
        $requiredFields = ["email", "password", "first_name", "last_name", "city", "address", "zip"];
        foreach ($requiredFields as $field) {
            if (!isset($entity[$field]) || trim($entity[$field]) === '') {
                // Throw an exception with a 400 Bad Request code
                throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required.", 400);
            }
        }

        $email_exists = $this->auth_dao->get_user_by_email($entity['email']);
        if($email_exists){
            throw new Exception("Email already registered.", 409); // 409 Conflict
        }

        $entity['password'] = password_hash($entity['password'], PASSWORD_BCRYPT);
        
        // Assign a default role for new users
        // Ensure your 'users' table has a 'role' column!
        $entity['role'] = $entity['role'] ?? Config::USER_ROLE(); // Default to 'user' role

        // The AuthDao->insert() method MUST be able to handle all fields in $entity
        $newUserId = $this->auth_dao->insert($entity); 

        if (!$newUserId) {
            // This might happen if the DAO's insert method returns false or 0 on failure
            throw new Exception("Failed to create user in the database.", 500);
        }

        $newUser = $this->auth_dao->getById($newUserId); // Assumes AuthDao has getById
        if (!$newUser) {
            throw new Exception("Failed to retrieve newly created user after registration.", 500);
        }
        unset($newUser['password']); // Never return the hashed password
        return $newUser;         
    }

    public function login($entity) {  
        // Input validation
        if (empty($entity['email']) || empty($entity['password'])) {
            throw new Exception("Email and password are required.", 400);
        }

        $user = $this->auth_dao->get_user_by_email($entity['email']);
        
        // Combine checks for clarity and to prevent timing attacks slightly
        if(!$user || !password_verify($entity['password'], $user['password'])) {
            // Log this attempt for security monitoring if possible, but don't reveal which part was wrong
            error_log("Failed login attempt for email: " . $entity['email']);
            throw new Exception("Invalid email or password.", 401); // 401 Unauthorized
        }

        // Unset password before adding to JWT payload or returning user object
        $userForPayloadAndReturn = $user; // Create a copy to modify
        unset($userForPayloadAndReturn['password']);
        
        // Determine token duration. Prefers Config::JWT_TOKEN_DURATION() if method exists, otherwise defaults to 86400s (24 hours).
        // For a permanent fix, ensure Config::JWT_TOKEN_DURATION() is defined as a static method in your Config.php file.
        $token_duration = 86400; // Default to 24 hours (86400 seconds)
        if (method_exists('Config', 'JWT_TOKEN_DURATION')) {
            $configured_duration = Config::JWT_TOKEN_DURATION();
            if (is_numeric($configured_duration) && $configured_duration > 0) {
                $token_duration = (int)$configured_duration;
            }
        }

        $jwt_payload = [
            'iat' => time(), // Issued at
            'exp' => time() + $token_duration, // Expiration time
            'user' => [ // This 'user' object in JWT is what frontend Utils.parseJwt(token).user expects
                'id' => $userForPayloadAndReturn['id'],
                'email' => $userForPayloadAndReturn['email'],
                'first_name' => $userForPayloadAndReturn['first_name'],
                'last_name' => $userForPayloadAndReturn['last_name'],
                'role' => $userForPayloadAndReturn['role'] 
            ]
        ];

        $token = JWT::encode(
            $jwt_payload,
            Config::JWT_SECRET(), // Ensure Config::JWT_SECRET() is a defined static method returning your secret key
            'HS256'
        );

        // Return token and the modified user data (without password)
        return ['token' => $token, 'user' => $userForPayloadAndReturn];         
    }
}
