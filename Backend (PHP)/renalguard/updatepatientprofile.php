<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

// Connect to MySQL
$conn = new mysqli("localhost", "root", "", "renalguard", 3306);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

// Get raw POST body
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// Get fields from JSON
$email = $data['email'] ?? '';
$name = $data['name'] ?? '';
$phone = $data['phone'] ?? '';
$password = $data['password'] ?? ''; 

if (empty($email)) {
    echo json_encode(["success" => false, "message" => "Email is required"]);
    $conn->close();
    exit();
}

// Check if patient exists
$stmt = $conn->prepare("SELECT password FROM users WHERE email = ? AND role = 'Patient'");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Patient not found"]);
    $conn->close();
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Update profile
if (!empty($password)) {
    $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ?, password = ? WHERE email = ? AND role = 'Patient'");
    $stmt->bind_param("ssss", $name, $phone, $password, $email);
} else {
    $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE email = ? AND role = 'Patient'");
    $stmt->bind_param("sss", $name, $phone, $email);
}

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Profile updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Update failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
