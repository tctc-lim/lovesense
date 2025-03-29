<?php
require "../../vendor/autoload.php";
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");


// Allow requests from http://127.0.0.1:5500
header("Access-Control-Allow-Origin: http://127.0.0.1:5500");

// Specify allowed methods
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

// Specify allowed headers
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Your existing verify_token.php logic goes here...
// For example:
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // process the POST request
} else {
    // process the GET request
}
?>

// ✅ Get Headers
$headers = function_exists("getallheaders") ? getallheaders() : get_request_headers();
$authHeader = $headers["Authorization"] ?? "";

if (!$authHeader || !str_starts_with($authHeader, "Bearer ")) {
    echo json_encode(["loggedIn" => false, "error" => "Missing token"]);
    exit();
}

$token = str_replace("Bearer ", "", $authHeader);

try {
    $decoded = JWT::decode($token, new Key($secretKey, "HS256"));
    $user = json_decode(json_encode($decoded), true);

    // ✅ Ensure 'id' exists
    if (!isset($user["id"])) {
        error_log("❌ ERROR: 'id' is missing from the token payload!");
        echo json_encode(["loggedIn" => false, "error" => "Invalid token: MISSING_ID"]);
        exit();
    }

    echo json_encode([
        "loggedIn" => true,
        "user" => [
            "id" => $user["id"],
            "name" => $user["name"],
            "email" => $user["email"],
            "role" => $user["role"]
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(["loggedIn" => false, "error" => "Invalid token"]);
}

// ✅ Function to Get Headers (if `getallheaders()` is missing)
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
