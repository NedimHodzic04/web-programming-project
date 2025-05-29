<?php
require_once 'config.php';


class BaseDao {
   protected $table;
   protected $connection;


   public function __construct($table) {
       $this->table = $table;
       $this->connection = Database::connect();
   }


   public function getAll() {
       $stmt = $this->connection->prepare("SELECT * FROM " . $this->table);
       $stmt->execute();
       return $stmt->fetchAll();
   }


   public function getById($id) {
       $stmt = $this->connection->prepare("SELECT * FROM " . $this->table . " WHERE id = :id");
       $stmt->bindParam(':id', $id);
       $stmt->execute();
       return $stmt->fetch();
   }

   public function getByEmail($email){
    $stmt = $this->connection->prepare("SELECT * FROM ". $this->table . " WHERE email = :email");
    $stmt->bindParam(":email", $email);
    $stmt->execute();
    return $stmt->fetch();
   }

   public function getByName($name){
    $stmt = $this->connection->prepare("SELECT * FROM ". $this->table . " WHERE name = :name");
    $stmt->bindParam(":name", $name);
    $stmt->execute();
    return $stmt->fetch();
   }

   public function insert($data) {
       $columns = implode(", ", array_keys($data));
       $placeholders = ":" . implode(", :", array_keys($data));
       $sql = "INSERT INTO " . $this->table . " ($columns) VALUES ($placeholders)";
       $stmt = $this->connection->prepare($sql);
       // var_dump($sql);
       return $stmt->execute($data);
   }


   public function update($id, $data) {
       $fields = "";
       foreach ($data as $key => $value) {
           $fields .= "$key = :$key, ";
       }
       $fields = rtrim($fields, ", ");
       $sql = "UPDATE " . $this->table . " SET $fields WHERE id = :id";
       $stmt = $this->connection->prepare($sql);
       $data['id'] = $id;
       return $stmt->execute($data);
   }


   public function delete($id) {
       $stmt = $this->connection->prepare("DELETE FROM " . $this->table . " WHERE id = :id");
       $stmt->bindParam(':id', $id);
       return $stmt->execute();
   }

   public function query_unique($sql, $params = []) {
    $stmt = $this->connection->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch();
}
}
?>
