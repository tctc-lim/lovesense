<?php
require "../auth/db.php";
require "../auth/auth_middleware.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit();
}

$user = verifyToken();
if ($user["role"] === "blog_poster") {
    echo json_encode(["error" => "Access denied. Only admins can view users."]);
    exit();
}

$search = isset($_GET["search"]) ? "%".$_GET["search"]."%" : "%";

try {
    $stmt = $conn->prepare("SELECT id, name, email, role FROM users WHERE name LIKE :search OR email LIKE :search ORDER BY id ASC");
    $stmt->bindParam(":search", $search, PDO::PARAM_STR);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "users" => $users]);
} catch (Exception $e) {
    echo json_encode(["error" => "Failed to fetch users.", "details" => $e->getMessage()]);
}
?>
