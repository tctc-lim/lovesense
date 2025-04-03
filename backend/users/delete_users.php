<?php
require "../auth/db.php";
require "../auth/auth_middleware.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit();
}

$user = verifyToken();
verifyAdmin();

$data = json_decode(file_get_contents("php://input"), true);
$id = $data["id"] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(["error" => "User ID is required"]);
    exit();
}

try {
    $conn->beginTransaction();

    // Fetch user details before deleting (for logging)
    $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = :id");
    $stmt->execute(["id" => $id]);
    $userToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$userToDelete) {
        echo json_encode(["error" => "User not found"]);
        exit();
    }

    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute(["id" => $id]);

    // Reorder IDs sequentially
    $conn->exec("ALTER TABLE users DROP COLUMN id CASCADE");
    $conn->exec("ALTER TABLE users ADD COLUMN id SERIAL PRIMARY KEY");

    // Log activity
    $activityStmt = $conn->prepare("INSERT INTO activities (user_id, action) VALUES (:user_id, :action)");
    $activityStmt->execute([
        ":user_id" => $user["id"],
        ":action" => "Deleted user: " . $userToDelete["name"] . " (" . $userToDelete["email"] . ")"
    ]);

    $conn->commit();

    echo json_encode(["message" => "User deleted and IDs updated"]);
} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Failed to delete user: " . $e->getMessage()]);
}
?>
