<?php
require "../auth/db.php";
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit();
}

// ✅ Fetch user ID from session (modify this if using JWT)
$userId = $_SESSION['user_id'] ?? null;

// 🔹 Fetch all blogs in ASCENDING ORDER
if ($_SERVER["REQUEST_METHOD"] === "GET" && !isset($_GET["id"])) {
    try {
        $stmt = $conn->prepare("SELECT id, title, image1, content1, image2, content2, tag1, tag2, tag3, thestatus, created_at FROM blogs ORDER BY id ASC");
        $stmt->execute();
        $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $counter = 1; // Start numbering dynamically

        foreach ($blogs as &$blog) {
            $blog["list_number"] = $counter++; // Assign a dynamic list number
        }

        echo json_encode(["success" => true, "blogs" => $blogs]);
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to fetch blogs.", "details" => $e->getMessage()]);
    }
    exit();
}

// 🔹 Fetch a Single Blog by ID
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["id"])) {
    $blogId = $_GET["id"];

    try {
        $stmt = $conn->prepare("SELECT id, title, image1, content1, image2, content2, tag1, tag2, tag3, thestatus, created_at FROM blogs WHERE id = :id");
        $stmt->bindParam(":id", $blogId, PDO::PARAM_INT);
        $stmt->execute();
        $blog = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($blog) {
            echo json_encode(["success" => true, "blog" => $blog]);
        } else {
            echo json_encode(["success" => false, "error" => "Blog not found"]);
        }
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to fetch blog details", "details" => $e->getMessage()]);
    }
    exit();
}

// 🔹 Delete a Blog
if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    $blogId = $_GET["id"] ?? null;
    if (!$blogId) {
        echo json_encode(["error" => "Blog ID is required"]);
        exit();
    }

    try {
        // 🔹 Fetch blog details before deletion
        $stmt = $conn->prepare("SELECT title FROM blogs WHERE id = :id");
        $stmt->bindParam(":id", $blogId, PDO::PARAM_INT);
        $stmt->execute();
        $blog = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$blog) {
            echo json_encode(["error" => "Blog not found"]);
            exit();
        }
        $blogTitle = $blog["title"];

        // 🔹 Delete the blog
        $stmt = $conn->prepare("DELETE FROM blogs WHERE id = :id");
        $stmt->bindParam(":id", $blogId, PDO::PARAM_INT);
        $stmt->execute();

        // ✅ Log the delete activity
        if ($userId) {
            $activityStmt = $conn->prepare("INSERT INTO activities (user_id, action, created_at) VALUES (:user_id, :action, NOW())");
            $activityStmt->execute([
                ":user_id" => $userId,
                ":action"  => "Deleted blog: $blogTitle",
            ]);
        } else {
            error_log("User ID is missing for logging.");
        }

        echo json_encode(["success" => true, "message" => "Blog deleted"]);
    } catch (Exception $e) {
        echo json_encode(["error" => "Database error.", "details" => $e->getMessage()]);
    }
    exit();
}
?>
