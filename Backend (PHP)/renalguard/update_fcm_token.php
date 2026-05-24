<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "renalguard", 3306);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"));
$email = $conn->real_escape_string($data->email ?? '');
$token = $conn->real_escape_string($data->fcm_token ?? '');

if (!$email || !$token) {
    echo json_encode(["success" => false, "message" => "Email and token required"]);
    exit();
}

$stmt = $conn->prepare("UPDATE users SET fcm_token=? WHERE email=?");
$stmt->bind_param("ss", $token, $email);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Token updated"]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
