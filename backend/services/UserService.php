<?php
require_once __DIR__ . '/../dao/UserDao.php';

class UserService {
    private $dao;

    public function __construct() {
        $this->dao = new UserDao();
    }

    public function register($userData) {
        // Check if user already exists
        if ($this->dao->getUserByEmail($userData['email'])) {
            throw new Exception("User already registered with this email!");
        }

        // Basic input validation (example: required fields)
        $requiredFields = ['first_name', 'last_name', 'email', 'password', 'city', 'address', 'zip'];
        foreach ($requiredFields as $field) {
            if (empty($userData[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }

        // Register new user
        $this->dao->register($userData);
        return "User successfully registered.";
    }

    public function login($email, $password) {
        $user = $this->dao->getUserByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception("Invalid email or password!");
        }

        return $user;
    }

    public function getUserByEmail($email) {
        $user = $this->dao->getUserByEmail($email);

        if (!$user) {
            throw new Exception("User not found with email: $email");
        }

        return $user;
    }

    public function updateUser($id, $userData) {
        // Optional: Validate if user exists before update
        $existingUser = $this->dao->getUserByEmail($userData['email']);
        if (!$existingUser || $existingUser['id'] != $id) {
            throw new Exception("User not found or mismatch.");
        }

        return $this->dao->updateUser($id, $userData);
    }
}
?>
