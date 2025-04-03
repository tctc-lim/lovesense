<?php
require "../auth/db.php"; // Database connection
require "../../vendor/autoload.php"; // Load JWT library
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

session_start();

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

// ✅ Get form data
$title = $_POST["title"] ?? null;
$readTime = $_POST["read-time"] ?? null;
$content1 = $_POST["content1"] ?? null;
$content2 = $_POST["content2"] ?? null;
$tag1 = $_POST["tag1"] ?? null;
$tag2 = $_POST["tag2"] ?? null;
$tag3 = $_POST["tag3"] ?? null;
$status = $_POST["status"] ?? "PENDING";

if (!$title || !$readTime || !$content1 || !$content2) {
    echo json_encode(["error" => "All required fields must be filled"]);
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
        echo json_encode(["error" => "Image {$i} upload failed"]);
        exit();
    }

    $imageName = time() . "_" . basename($_FILES[$imageKey]["name"]);
    $imagePath = $uploadDir . $imageName;

    if (!move_uploaded_file($_FILES[$imageKey]["tmp_name"], $imagePath)) {
        echo json_encode(["error" => "Failed to upload Image {$i}"]);
        exit();
    }

    $imagePaths[$i] = $imagePath;
}

// ✅ Save to database
try {
    $stmt = $conn->prepare("
        INSERT INTO blogs (title, read_time, image1, image2, content1, content2, tag1, tag2, tag3, status) 
        VALUES (:title, :read_time, :image1, :image2, :content1, :content2, :tag1, :tag2, :tag3, :status)
    ");
    $stmt->execute([
        ":title" => $title,
        ":read_time" => $readTime,
        ":image1" => $imagePaths[1],
        ":image2" => $imagePaths[2],
        ":content1" => $content1,
        ":content2" => $content2,
        ":tag1" => $tag1,
        ":tag2" => $tag2,
        ":tag3" => $tag3,
        ":status" => $status,

    ]);

    // ✅ Log the activity
    $activityStmt = $conn->prepare("INSERT INTO activities (user_id, action) VALUES (:user_id, :action)");
    $activityStmt->execute([":user_id" => $userId, ":action" => "Created a new blog: $title"]);

    echo json_encode(["success" => "Blog added successfully"]);
} catch (Exception $e) {
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
