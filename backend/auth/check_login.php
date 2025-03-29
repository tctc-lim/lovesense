<?php
header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

require "../../vendor/autoload.php";
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secretKey = "your_secret_key";

// ✅ Get Headers (Case-Insensitive)
$headers = function_exists("getallheaders") ? array_change_key_case(getallheaders(), CASE_LOWER) : get_request_headers();
$authHeader = $headers["authorization"] ?? "";

if (!$authHeader || !str_starts_with($authHeader, "Bearer ")) {
    echo json_encode(["loggedIn" => false, "error" => "Missing or invalid token"]);
    exit();
}

$token = str_replace("Bearer ", "", $authHeader);

try {
    $decoded = JWT::decode($token, new Key($secretKey, "HS256"));
    $user = json_decode(json_encode($decoded), true); // Convert to associative array

    // Debugging: Print JWT payload to check structure
    error_log(json_encode($user));

    echo json_encode([
        "loggedIn" => true,
        "user" => [
            "id" => $user["id"] ?? "MISSING_ID",
            "name" => $user["name"] ?? "Unknown",
            "email" => $user["email"] ?? "Unknown",
            "role" => $user["role"] ?? "Unknown"
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(["loggedIn" => false, "error" => "Invalid token"]);
}

// ✅ Fallback function to get headers (if `getallheaders()` is missing)
function get_request_headers() {
    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) === "HTTP_") {
            $headerName = str_replace(" ", "-", ucwords(str_replace("_", " ", strtolower(substr($key, 5)))));
            $headers[$headerName] = $value;
        }
    }
    return $headers;
}
?>
