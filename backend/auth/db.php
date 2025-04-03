<?php
$host = "localhost"; // Change if using a different host
$dbname = "lovesense-admin-db"; // Replace with your database name
$user = "postgres"; // Replace with your database username
$pass = "postgres"; // Replace with your database password

try {
    $conn = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["error" => "Connection failed: " . $e->getMessage()]));
}
?>
