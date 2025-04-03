<?php
require "./backend/auth/db.php";

header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit();
}

// 🔹 Pagination Parameters
$page = isset($_GET["page"]) ? (int)$_GET["page"] : 1;
$limit = 9; // Fixed limit per page
$offset = ($page - 1) * $limit;

try {
    // ✅ Count total blogs for pagination
    $countStmt = $conn->query("SELECT COUNT(*) as total FROM blogs WHERE status = 'COMPLETED'");
    $totalBlogs = $countStmt->fetch(PDO::FETCH_ASSOC)["total"];
    $totalPages = ceil($totalBlogs / $limit);

    // ✅ Fetch blogs with Pagination
    $stmt = $conn->prepare("SELECT id, title, image1, content1, created_at 
                            FROM blogs 
                            WHERE status = 'COMPLETED' 
                            ORDER BY created_at DESC 
                            LIMIT :limit OFFSET :offset"); 

    $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
    $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
    $stmt->execute();
    $blogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "blogs" => $blogs,
        "totalBlogs" => $totalBlogs,
        "totalPages" => $totalPages,
        "currentPage" => $page
    ]);
} catch (Exception $e) {
    echo json_encode(["error" => "Failed to fetch blogs", "details" => $e->getMessage()]);
}
?>
