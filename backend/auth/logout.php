<?php
header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

require "db.php";  // Database connection

// Get the Authorization header
$headers = getallheaders();
$authHeader = $headers["Authorization"] ?? '';

if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    echo json_encode(["error" => "Token missing"]);
    exit();
}

$token = $matches[1];

// Save token in a blacklist table (optional)
try {
    $stmt = $conn->prepare("INSERT INTO token_blacklist (token) VALUES (?)");
    $stmt->execute([$token]);
} catch (PDOException $e) {
    echo json_encode(["error" => "Database error"]);
    exit();
}

echo json_encode(["success" => true, "message" => "Logged out successfully"]);
exit();
?>
