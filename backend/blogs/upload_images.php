<?php
require "../auth/db.php"; // Database connection
require "../../vendor/autoload.php"; // Load JWT library
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// ✅ Allow CORS for frontend requests
header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// ✅ Handle CORS preflight request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

// ✅ Ensure request is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["error" => "Invalid request method"]);
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
$secretKey = "your_secret_key"; // Use the same secret key from login

try {
    // ✅ Decode Token
    $decoded = JWT::decode($token, new Key($secretKey, "HS256"));
    $user = json_decode(json_encode($decoded), true);

    if (!isset($user["id"])) {
        echo json_encode(["error" => "Invalid token: MISSING_ID"]);
        exit();
    }

    $userId = $user["id"]; // Extract user ID

} catch (Exception $e) {
    echo json_encode(["error" => "Invalid token"]);
    exit();
}

// ✅ Get Blog ID
$id = $_POST["id"] ?? null;
if (!$id) {
    echo json_encode(["error" => "Blog ID is required"]);
    exit();
}

// ✅ Handle image upload
$uploadDir = "uploads/";
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$imagePaths = [];
for ($i = 1; $i <= 2; $i++) {
    $imageKey = "image$i";
    if (!isset($_FILES[$imageKey]) || $_FILES[$imageKey]["error"] !== UPLOAD_ERR_OK) {
        continue;
    }

    $imageName = time() . "_" . basename($_FILES[$imageKey]["name"]);
    $imagePath = $uploadDir . $imageName;

    if (move_uploaded_file($_FILES[$imageKey]["tmp_name"], $imagePath)) {
        $imagePaths[$imageKey] = $imagePath;
    } else {
        echo json_encode(["error" => "Failed to upload Image {$i}"]);
        exit();
    }
}

// ✅ Update images in the database if they exist
if (!empty($imagePaths)) {
    $query = "UPDATE blogs SET ";
    $params = [];
    $types = "";

    foreach ($imagePaths as $key => $path) {
        $query .= "$key = ?, ";
        $params[] = $path;
        $types .= "s";
    }

    $query = rtrim($query, ", ") . " WHERE id = ?";
    $params[] = $id;
    $types .= "i";

    $stmt = $conn->prepare($query);
    if ($stmt->execute($params)) {
        echo json_encode(["success" => true, "message" => "Images updated successfully"]);
    } else {
        echo json_encode(["error" => "Failed to update images"]);
    }
}

$conn = null;
?>