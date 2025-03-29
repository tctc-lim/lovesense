<?php
require "../../vendor/autoload.php";
require_once __DIR__ . '/jwt_helper.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Content-Type: application/json");

$secretKey = "your_secret_key"; // Ensure this matches your JWT signing key

function verifyAdmin() {
    $user = verifyToken();

    if (!isset($user["role"]) || strtolower($user["role"]) !== "admin") {
        echo json_encode(["success" => false, "message" => "Forbidden: Admins only"]);
        exit();
    }
}

function verifyToken() {
    $headers = getallheaders();
    $authHeader = $headers["Authorization"] ?? "";

    if (!$authHeader || !str_starts_with($authHeader, "Bearer ")) {
        echo json_encode(["success" => false, "message" => "Missing token"]);
        exit();
    }

    $token = str_replace("Bearer ", "", $authHeader);

    try {
        $decoded = JWT::decode($token, new Key($GLOBALS['secretKey'], "HS256"));
        return (array) $decoded;
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Invalid token: " . $e->getMessage()]);
        exit();
    }
}
