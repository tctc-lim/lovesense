<?php
require "../auth/db.php";
require "../auth/auth_middleware.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit();
}

$user = verifyToken();
verifyAdmin();

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$name = $data['name'] ?? null;
$email = $data['email'] ?? null;
$role = $data['role'] ?? null;

if (!$id || !$name || !$email || !$role) {
    echo json_encode(["error" => "Missing required fields"]);
    exit();
}

try {
    $stmt = $conn->prepare("UPDATE users SET name = :name, email = :email, role = :role WHERE id = :id");
    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->bindParam(':role', $role, PDO::PARAM_STR);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    // ✅ Log the update activity
    $activityStmt = $conn->prepare("INSERT INTO activities (user_id, action) VALUES (:user_id, :action)");
    $action = "Updated user: $name ($email) - Role changed to $role";
    $activityStmt->execute([":user_id" => $user['id'], ":action" => $action]);

    echo json_encode(["success" => "User updated successfully"]);
} catch (Exception $e) {
    echo json_encode(["error" => "Update failed", "details" => $e->getMessage()]);
}
?>