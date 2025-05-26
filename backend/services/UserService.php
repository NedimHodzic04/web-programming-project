<?php
require_once __DIR__ . '/../dao/UserDao.php'; // Ensure path is correct
require_once __DIR__ . '/../dao/config.php'; // For roles if needed here

class UserService {
    private $dao;

    public function __construct() {
        $this->dao = new UserDao();
    }

    // Removed the register method here, as it's handled by AuthService
    // The register method in AuthService should call UserDao's register
    
    // Removed the login method here, as it's handled by AuthService

    public function getUserByEmail($email) {
        try {
            $user = $this->dao->getUserByEmail($email);
            if (!$user) {
                throw new Exception("User not found with email: $email", 404);
            }
            return $user;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve user by email: " . $e->getMessage(), $e->getCode());
        }
    }

    public function getUserById($id) { // Added this as you might need to fetch by ID
        try {
            $user = $this->dao->getById($id);
            if (!$user) {
                throw new Exception("User not found with ID: $id", 404);
            }
            return $user;
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve user by ID: " . $e->getMessage(), $e->getCode());
        }
    }

    public function getAllUsers() { // Added this for admin functionality
        try {
            return $this->dao->getAll();
        } catch (Exception $e) {
            throw new Exception("Failed to retrieve all users: " . $e->getMessage(), $e->getCode());
        }
    }

    public function updateUser($id, $userData) {
        // You might want to get the existing user first to validate ownership or admin rights
        $existingUser = $this->dao->getById($id);
        if (!$existingUser) {
            throw new Exception("User not found for update.", 404);
        }

        // Ensure email isn't changed to an already existing email (if email is part of update)
        if (isset($userData['email']) && $userData['email'] !== $existingUser['email']) {
            if ($this->dao->getUserByEmail($userData['email'])) {
                throw new Exception("Email already taken by another user.", 409);
            }
        }

        try {
            return $this->dao->update($id, $userData);
        } catch (Exception $e) {
            throw new Exception("Failed to update user: " . $e->getMessage(), $e->getCode());
        }
    }

    public function deleteUser($id) { // Added for admin functionality
        try {
            $existingUser = $this->dao->getById($id);
            if (!$existingUser) {
                throw new Exception("User not found for deletion.", 404);
            }
            return $this->dao->delete($id);
        } catch (Exception $e) {
            throw new Exception("Failed to delete user: " . $e->getMessage(), $e->getCode());
        }
    }

    public function updateRole($userId, $newRole) { // For admin only to update user roles
        try {
            $user = $this->dao->getById($userId);
            if (!$user) {
                throw new Exception("User not found.", 404);
            }
            // Basic validation for newRole (e.g., check if it's a valid role string)
            if (!in_array($newRole, [Config::USER_ROLE(), Config::ADMIN_ROLE()])) { // Use your Config roles
                throw new Exception("Invalid role specified.", 400);
            }
            return $this->dao->updateRole($userId, $newRole); // This calls the new method in UserDao
        } catch (Exception $e) {
            throw new Exception("Failed to update user role: " . $e->getMessage(), $e->getCode());
        }
    }
}