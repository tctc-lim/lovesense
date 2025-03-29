<?php
require_once '../../vendor/autoload.php'; // Ensure you have Firebase JWT installed

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!class_exists("JWTHelper")) { // Prevent class redeclaration
    class JWTHelper
    {
        private static $secret_key = "your_secret_key"; // Change this to a strong, unique key
        private static $algorithm = "HS256";

        // Generate JWT Token
        public static function generateToken($userId, $email, $role, $name)
        {
            $payload = [
                "userId" => $userId,
                "name" => $name,
                "email" => $email,
                "role" => $role,
                "iat" => time(), // Issued at
                "exp" => time() + (60 * 60 * 24) // Expires in 24 hours
            ];
            return JWT::encode($payload, self::$secret_key, self::$algorithm);
        }

        public static function verifyToken($token)
        {
            try {
                $decoded = JWT::decode($token, new Key(self::$secret_key, self::$algorithm));
                return (array) $decoded;
            } catch (Exception $e) {
                return null;
            }
        }
    }
}
?>
