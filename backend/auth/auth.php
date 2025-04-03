<?php
require "../../vendor/autoload.php";
require "db.php"; // Ensure this file contains a valid `$conn` (PDO connection)
use Firebase\JWT\JWT;

header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

$secretKey = "your_secret_key";

// ✅ Get JSON data securely
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// ✅ Validate input
if (!isset($data["email"]) || !isset($data["password"])) {
    echo json_encode(["error" => "Email and password are required"]);
    exit();
}

$email = trim($data["email"]);
$password = $data["password"];

// ✅ Check if email exists
$stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = :email");
$stmt->bindParam(":email", $email, PDO::PARAM_STR);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ✅ Validate password
if (!$user || !password_verify($password, $user["password"])) {
    http_response_code(401); // Send Unauthorized status
    echo json_encode(["success" => false, "error" => "Invalid credentials"]);
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
    "success" => true,
    "token" => $token,
    "user" => [
        "id" => $user["id"],
        "name" => $user["name"],
        "email" => $user["email"],
        "role" => $user["role"]
    ]
]);
?>
