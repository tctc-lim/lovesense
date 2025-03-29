<?php
require "../../vendor/autoload.php";
require "db.php"; // Ensure this file contains a valid `$conn` (PDO connection)
use Firebase\JWT\JWT;

header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

$secretKey = "your_secret_key";

$data = json_decode(file_get_contents("php://input"), true);
$email = $data["email"] ?? null;
$password = $data["password"] ?? null;

if (!$email || !$password) {
    echo json_encode(["error" => "Email and password are required"]);
    exit();
}

// ✅ Fetch user from DB
$stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = :email");
$stmt->bindParam(":email", $email, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user["password"])) {
    echo json_encode(["error" => "Invalid credentials"]);
    exit();
}

// ✅ Generate JWT Token
$payload = [
    "id" => $user["id"],
    "name" => $user["name"],
    "email" => $user["email"],
    "role" => $user["role"],
    "iat" => time(),
    "exp" => time() + 3600 // Token expires in 1 hour
];

$token = JWT::encode($payload, $secretKey, "HS256");

// ✅ Return token and user data
echo json_encode([
    "token" => $token,
    "user" => [
        "id" => $user["id"],
        "name" => $user["name"],
        "email" => $user["email"],
        "role" => $user["role"]
    ]
]);
?>
