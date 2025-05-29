<?php

class Config {
    public static function DB_NAME()
   {
       return 'autoparts'; 
   }
   public static function DB_PORT()
   {
       return  3306;
   }
   public static function DB_USER()
   {
       return 'neda';
   }
   public static function DB_PASSWORD()
   {
       return '0807';
   }
   public static function DB_HOST()
   {
       return '127.0.0.1';
   }

   public static function JWT_SECRET() {
       // IMPORTANT: Replace 'your_key_string' with a strong, random, and unique secret key for production!
       // You can generate one using: bin2hex(random_bytes(32)) or similar.
       return 'Pasvord';
   }

    public static function JWT_TOKEN_DURATION() {
        return 3600; // Example: 1 hour in seconds
        // return 86400; // Example: 24 hours in seconds
    }

   // Define user roles as constants
   public static function USER_ROLE() {
       return 'user';
   }

   public static function ADMIN_ROLE() {
       return 'admin';
   }
}

class Database {
   private static $connection = null;

   public static function connect() {
       if (self::$connection === null) {
           try {
               self::$connection = new PDO(
                   "mysql:host=" . Config::DB_HOST() . ";dbname=" . Config::DB_NAME(),
                   Config::DB_USER(),
                   Config::DB_PASSWORD(),
                   [
                       PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                       PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                   ]
               );
           } catch (PDOException $e) {
               // Instead of die(), throw an exception for better error handling flow
               throw new Exception("Database connection failed: " . $e->getMessage());
           }
       }
       return self::$connection;
   }
}