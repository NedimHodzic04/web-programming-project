<?php
require_once 'BaseDao.php';

class UserDao extends BaseDao {
    public function __construct() {
        parent::__construct("users");
    }

    public function register($userData) {
        $stmt = $this->connection->prepare("
            INSERT INTO users (first_name, last_name, email, password, city, address, zip)
            VALUES (:first_name, :last_name, :email, :password, :city, :address, :zip)
        ");
        return $stmt->execute([
            ':first_name' => $userData['first_name'],
            ':last_name' => $userData['last_name'],
            ':email' => $userData['email'],
            ':password' => password_hash($userData['password'], PASSWORD_BCRYPT),
            ':city' => $userData['city'],
            ':address' => $userData['address'],
            ':zip' => $userData['zip']
        ]);
    }

    public function getUserByEmail($email) {
        $stmt = $this->connection->prepare("
            SELECT * FROM users WHERE email = :email
        ");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateUser($id, $userData) {
        $stmt = $this->connection->prepare("
            UPDATE users SET
                first_name = :first_name,
                last_name = :last_name,
                city = :city,
                address = :address,
                zip = :zip
            WHERE id = :id
        ");
        return $stmt->execute([
            ':id' => $id,
            ':first_name' => $userData['first_name'],
            ':last_name' => $userData['last_name'],
            ':city' => $userData['city'],
            ':address' => $userData['address'],
            ':zip' => $userData['zip']
        ]);
    }
}
?>