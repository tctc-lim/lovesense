<?php
require "../auth/db.php";
require "../auth/auth_middleware.php";

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit();
}

$user = verifyToken();
verifyAdmin();

$data = json_decode(file_get_contents("php://input"), true);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? null;
    $email = $_POST['email'] ?? null;
    $password = $_POST['password'] ?? null;
    $role = $_POST['role'] ?? 'blog_poster'; // Default role

    if (!$name || !$email || !$password) {
        echo json_encode(["error" => "All fields are required."]);
        exit();
    }

    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)");
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':role', $role);

        if ($stmt->execute()) {
            // ✅ Log the activity
            $adminId = $user['id']; // Get the admin's ID
            $action = "Created a new user: $name ($email) with role $role";
            $logStmt = $conn->prepare("INSERT INTO activities (user_id, action) VALUES (:user_id, :action)");
            $logStmt->execute([':user_id' => $adminId, ':action' => $action]);

            echo json_encode(["success" => "User created successfully."]);
        } else {
            echo json_encode(["error" => "Failed to create user."]);
        }
    } catch (Exception $e) {
        echo json_encode(["error" => "Database error.", "details" => $e->getMessage()]);
    }
}
?>