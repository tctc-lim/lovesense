<?php
// Fetch environment variables
$host = "127.0.0.1";
$port = 3306;
$dbname = "u100944103_lovesense_db";
$user = "u100944103_lovesense";
$pass = "Admin@lovesense2488";

// Create a PDO instance and connect to MySQL
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname";
    $conn = new PDO($dsn, $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // ✅ No echo here
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Database connection failed: " . $e->getMessage()
    ]);
    exit();
}
