<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));

$conn = new mysqli("localhost", "root", "", "renalguard", 3306);
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed"]));
}

// Extract and sanitize input
$name = $conn->real_escape_string(trim($data->name ?? ''));
$email = $conn->real_escape_string(trim($data->email ?? ''));
$phone = $conn->real_escape_string(trim($data->phone ?? ''));
$password = $conn->real_escape_string(trim($data->password ?? ''));
$role = $conn->real_escape_string(trim($data->role ?? ''));

// 1️⃣ Check for empty fields
if (empty($name) || empty($email) || empty($phone) || empty($password) || empty($role)) {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    exit();
}

// 2️⃣ Validate name (only alphabets and spaces)
if (!preg_match("/^[A-Za-z ]+$/", $name)) {
    echo json_encode(["success" => false, "message" => "Name should contain only alphabets"]);
    exit();
}

// 3️⃣ Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    exit();
}

// 4️⃣ Validate phone number (only digits and exactly 10 digits)
if (!preg_match("/^[0-9]{10}$/", $phone)) {
    echo json_encode(["success" => false, "message" => "Phone number must be exactly 10 digits"]);
    exit();
}

// 5️⃣ Validate password (min 8 chars, at least one number and one special character)
if (!preg_match("/^(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{8,}$/", $password)) {
    echo json_encode(["success" => false, "message" => "Password must be at least 8 characters long and contain at least one number and one special character"]);
    exit();
}

// 6️⃣ Check if email already exists
$checkEmail = $conn->query("SELECT id FROM users WHERE email = '$email'");
if ($checkEmail->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Account already exists, please login"]);
    exit();
}

// 7️⃣ Get next available ID (reuse deleted ones)
$result = $conn->query("
    SELECT MIN(t1.id + 1) AS next_id
    FROM users t1
    LEFT JOIN users t2 ON t1.id + 1 = t2.id
    WHERE t2.id IS NULL
");
$row = $result->fetch_assoc();
$nextId = $row['next_id'] ?? 1;

if (!$nextId || $nextId == 0) {
    $nextId = 1;
}

// 8️⃣ Insert the new user (plain password, no hashing)
$sql = "INSERT INTO users (id, name, email, phone, password, role)
        VALUES ('$nextId', '$name', '$email', '$phone', '$password', '$role')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => true, "message" => "Registration successful"]);
} else {
    echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
}

$conn->close();
?>
