<?php
// Include necessary files and dependencies
require "../auth/db.php";
require_once "../../vendor/autoload.php";  // Ensure proper path to vendor
require_once "../auth/auth_middleware.php"; // Include the middleware for token validation

// Your secret key to sign the JWT tokens (keep this secret and stored securely)
$secretKey = "your_secret_key";  // Ensure this key is consistent with the one in auth_middleware.php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight CORS requests
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit();
}

// Handle GET request (Fetch blogs with pagination or a single blog by ID)
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    // Fetch all blogs with pagination
    if (!isset($_GET["id"])) {
        // Pagination parameters
        $page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
        $limit = 7;  // Number of blogs per page
        $offset = ($page - 1) * $limit;

        try {
            // Fetch total count for pagination
            $totalStmt = $conn->query("SELECT COUNT(*) as total FROM blogs");
            $totalBlogs = $totalStmt->fetch(PDO::FETCH_ASSOC)["total"];
            $totalPages = ceil($totalBlogs / $limit);

            // Fetch the blogs
            $stmt = $conn->prepare("SELECT id, title, read_time, image1, content1, image2, content2, tag1, tag2, tag3, status, created_at 
                                    FROM blogs 
                                    ORDER BY id ASC 
                                    LIMIT :limit OFFSET :offset");
            $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
            $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
            $stmt->execute();
            $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $counter = $offset + 1;  // Adjust blog numbering for pagination
            foreach ($blogs as &$blog) {
                $blog["list_number"] = $counter++;
            }

            echo json_encode([
                "success" => true,
                "current_page" => $page,
                "total_pages" => $totalPages,
                "total_blogs" => $totalBlogs,
                "blogs" => $blogs
            ]);
        } catch (Exception $e) {
            echo json_encode(["error" => "Failed to fetch blogs.", "details" => $e->getMessage()]);
        }
    } 
    // Fetch a single blog by ID
    elseif (isset($_GET["id"])) {
        $blogId = $_GET["id"];

        try {
            // Fetch the specific blog
            $stmt = $conn->prepare("SELECT id, title, read_time, image1, content1, image2, content2, tag1, tag2, tag3, status, created_at 
                                    FROM blogs 
                                    WHERE id = :id");
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
    }
    exit();
}

// Handle DELETE request (Delete a blog by ID)
if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    // Ensure Authorization header is provided
    if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
        echo json_encode(["error" => "Authorization token required"]);
        exit();
    }

    // Extract token from Authorization header
    $headers = getallheaders();
    $authHeader = $headers["Authorization"] ?? "";
    $token = str_replace("Bearer ", "", $authHeader);
    $secretKey = "your_secret_key";

    // Decode token
    try {
        $decoded = JWT::decode($token, new Key($secretKey, "HS256"));
        $user = json_decode(json_encode($decoded), true);
        if (!isset($user["id"])) {
            echo json_encode(["error" => "Invalid token: MISSING_ID"]);
            exit();
        }
        $userId = $user["id"];
    } catch (Exception $e) {
        echo json_encode(["error" => "Invalid token"]);
        exit();
    }

    // Get the blog ID from the request
    $blogId = $_GET["id"] ?? null;
    if (!$blogId) {
        echo json_encode(["error" => "Blog ID is required"]);
        exit();
    }

    try {
        // Fetch blog details before deletion
        $stmt = $conn->prepare("SELECT title FROM blogs WHERE id = :id");
        $stmt->bindParam(":id", $blogId, PDO::PARAM_INT);
        $stmt->execute();
        $blog = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$blog) {
            echo json_encode(["error" => "Blog not found"]);
            exit();
        }
        $blogTitle = $blog["title"];

        // Delete the blog
        $stmt = $conn->prepare("DELETE FROM blogs WHERE id = :id");
        $stmt->bindParam(":id", $blogId, PDO::PARAM_INT);
        $stmt->execute();

        // Log the delete action
        $logStmt = $conn->prepare("INSERT INTO activities (user_id, action) VALUES (:user_id, :action)");
        $logStmt->execute([
            ":user_id" => $userId,
            ":action" => "Deleted blog ID: $blogId - Title: $blogTitle"
        ]);

        echo json_encode(["success" => true, "message" => "Blog deleted successfully"]);
    } catch (Exception $e) {
        echo json_encode(["error" => "Failed to delete blog.", "details" => $e->getMessage()]);
    }
    exit();
}


if ($_SERVER["REQUEST_METHOD"] === "PUT") {
    try {
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            echo json_encode(["success" => false, "error" => "Authorization token required"]);
            exit();
        }

        // Extract token from Authorization header
        $headers = getallheaders();
        $authHeader = $headers["Authorization"] ?? "";
        $token = str_replace("Bearer ", "", $authHeader);
        $secretKey = "your_secret_key";

        // Decode token
        try {
            $decoded = JWT::decode($token, new Key($secretKey, "HS256"));
            $user = json_decode(json_encode($decoded), true);
            if (!isset($user["id"])) {
                echo json_encode(["error" => "Invalid token: MISSING_ID"]);
                exit();
            }
            $userId = $user["id"];
        } catch (Exception $e) {
            echo json_encode(["error" => "Invalid token"]);
            exit();
        }

        $inputData = json_decode(file_get_contents("php://input"), true);

        $blogId = $inputData["id"];
        $title = $inputData["title"] ?? null;
        $readTime = $inputData["read_time"] ?? null;
        $content1 = $inputData["content1"] ?? null;
        $content2 = $inputData["content2"] ?? null;
        $tag1 = $inputData["tag1"] ?? null;
        $tag2 = $inputData["tag2"] ?? null;
        $tag3 = $inputData["tag3"] ?? null;
        $status = $inputData["status"] ?? "PENDING";

        if (empty($title) || empty($content1)) {
            echo json_encode(["success" => false, "error" => "Title and Content 1 are required fields."]);
            exit();
        }

        $stmt = $conn->prepare("UPDATE blogs SET title = ?, read_time = ?, content1 = ?, content2 = ?, tag1 = ?, tag2 = ?, tag3 = ?, status = ? WHERE id = ?");
        $stmt->execute([
            $title, $readTime, $content1, $content2, $tag1, $tag2, $tag3, $status, $blogId
        ]);

        // Log the update action
        $logStmt = $conn->prepare("INSERT INTO activities (user_id, action) VALUES (:user_id, :action)");
        $logStmt->execute([
            ":user_id" => $userId,
            ":action" => "Updated blog ID: $blogId - Title: $title"
        ]);

        echo json_encode(["success" => true, "message" => "Blog updated successfully"]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    exit();
}
