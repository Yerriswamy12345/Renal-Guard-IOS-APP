<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "renalguard", 3306);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

$input = json_decode(file_get_contents("php://input"), true);
$email = $_POST['email'] ?? $input['email'] ?? null;
$password = $_POST['password'] ?? $input['password'] ?? null;

if (!$email) {
    echo json_encode(["success" => false, "message" => "Missing email"]);
    $conn->close();
    exit();
}

if (!empty($password)) {
    // 1. Try updating admin table
    $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $password, $email);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => true, "message" => "Admin password updated successfully"]);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    // 2. Try updating users table (for Admin role)
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ? AND role = 'Admin'");
    $stmt->bind_param("ss", $password, $email);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(["success" => true, "message" => "Admin password updated successfully"]);
    } else {
        echo json_encode(["success" => false, "message" => "Admin not found or no changes made"]);
    }
    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "No password provided"]);
}

$conn->close();
?>
