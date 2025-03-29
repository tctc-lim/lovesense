<?php
require "../auth/db.php"; // Database connection
require "../../vendor/autoload.php"; // Load JWT library
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ✅ Allow requests from the frontend
header("Access-Control-Allow-Origin: *"); // Change * to your frontend URL if needed
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// ✅ Handle CORS preflight request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

// ✅ Check for Authorization Header
$headers = getallheaders();
$authHeader = $headers["Authorization"] ?? "";
if (!$authHeader || !str_starts_with($authHeader, "Bearer ")) {
    echo json_encode(["error" => "Missing or invalid token"]);
    exit();
}

$token = str_replace("Bearer ", "", $authHeader);
$secretKey = "your_secret_key"; // Use your actual secret key

try {
    // ✅ Decode JWT Token
    $decoded = JWT::decode($token, new Key($secretKey, "HS256"));
    $user = json_decode(json_encode($decoded), true);
    $userId = $user["id"] ?? null;

    if (!$userId) {
        echo json_encode(["error" => "Invalid token"]);
        exit();
    }

    // ✅ Fetch user activities
    $stmt = $conn->prepare("SELECT action, timestamp FROM activities WHERE user_id = :user_id ORDER BY timestamp DESC LIMIT 10");
    $stmt->execute([":user_id" => $userId]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($activities);

} catch (Exception $e) {
    echo json_encode(["error" => "Invalid token"]);
    exit();
}
?>
