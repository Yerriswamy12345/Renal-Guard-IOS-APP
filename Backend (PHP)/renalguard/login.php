<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$conn = new mysqli("localhost", "root", "", "renalguard", 3306);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed", "data" => null]);
    exit();
}

$email = $conn->real_escape_string($data['email'] ?? '');
$password = $conn->real_escape_string($data['password'] ?? '');
$role = $conn->real_escape_string($data['role'] ?? '');
$fcm_token = $conn->real_escape_string($data['fcm_token'] ?? '');

if (!$email || !$password || !$role) {
    echo json_encode(["success" => false, "message" => "All fields are required", "data" => null]);
    exit();
}

if ($role === "admin") {
    $result = $conn->query("SELECT * FROM admin WHERE username='$email'");
    if ($result && $result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if ($password === $admin['password']) {
            echo json_encode([
                "success" => true, 
                "message" => "Login successful",
                "data" => [
                    "user" => [
                        "id" => 1,
                        "name" => "Admin",
                        "email" => $email, 
                        "phone" => "",
                        "role" => "admin"
                    ]
                ]
            ]);
            exit();
        } else {
            echo json_encode(["success" => false, "message" => "Incorrect password", "data" => null]);
            exit();
        }
    }
    // Fallback to users table for admin role
    $result = $conn->query("SELECT * FROM users WHERE email='$email' AND role='Admin'");
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if ($password === $user['password']) {
            echo json_encode([
                "success" => true,
                "message" => "Login successful",
                "data" => [
                    "user" => [
                        "id" => (int)$user['id'],
                        "name" => $user['name'],
                        "email" => $user['email'],
                        "phone" => $user['phone'],
                        "role" => $user['role']
                    ]
                ]
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Incorrect password", "data" => null]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Admin not found or role mismatch", "data" => null]);
    }
} else {
    $result = $conn->query("SELECT * FROM users WHERE email='$email' AND role='$role'");
    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if ($password === $user['password']) {
            if ($fcm_token) {
                $conn->query("UPDATE users SET fcm_token='$fcm_token' WHERE email='$email'");
            }
            echo json_encode([
                "success" => true,
                "message" => "Login successful",
                "data" => [
                    "user" => [
                        "id" => (int)$user['id'],
                        "name" => $user['name'],
                        "email" => $user['email'],
                        "phone" => $user['phone'],
                        "role" => $user['role']
                    ]
                ]
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "Incorrect password", "data" => null]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "User not found or role mismatch", "data" => null]);
    }
}

$conn->close();
?>
