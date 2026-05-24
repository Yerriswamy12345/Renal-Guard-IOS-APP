<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

// Get JSON input
$data = json_decode(file_get_contents("php://input"));

// Connect to MySQL
$conn = new mysqli("localhost", "root", "", "renalguard", 3306);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

// Sanitize input
$name = $conn->real_escape_string($data->name ?? '');
$age = (int)($data->age ?? 0);
$email = $conn->real_escape_string($data->email ?? '');
$phone = $conn->real_escape_string($data->phone ?? '');
$doctor_email = $conn->real_escape_string($data->doctor_email ?? '');
$gender = $conn->real_escape_string($data->gender ?? '');

// Validations
if (!$name || !$age || !$email || !$phone || !$doctor_email || !$gender) {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    $conn->close();
    exit();
}

// Name validation
if (!preg_match("/^[A-Za-z ]+$/", $name)) {
    echo json_encode(["success" => false, "message" => "Name can only contain letters and spaces"]);
    $conn->close();
    exit();
}

// Age validation
if ($age <= 0 || $age > 150) {
    echo json_encode(["success" => false, "message" => "Age must be between 1 and 150"]);
    $conn->close();
    exit();
}

// Gender validation
$validGenders = ["Male", "Female", "Others"];
if (!in_array($gender, $validGenders)) {
    echo json_encode(["success" => false, "message" => "Invalid gender selected"]);
    $conn->close();
    exit();
}

// Email validation
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    $conn->close();
    exit();
}

// Phone validation
if (!preg_match("/^\d{10}$/", $phone)) {
    echo json_encode(["success" => false, "message" => "Phone number must be 10 digits"]);
    $conn->close();
    exit();
}

// Get reusable smallest missing ID
$idResult = $conn->query("
    SELECT COALESCE(MIN(t1.id + 1), 1) AS next_id
    FROM patients t1
    LEFT JOIN patients t2 ON t1.id + 1 = t2.id
    WHERE t2.id IS NULL
");
$row = $idResult->fetch_assoc();
$nextId = $row['next_id'] ?? 1;

// Generate patient_id
$patient_id = 'PAT' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

// Insert new patient
$sql = "INSERT INTO patients (id, name, age, email, phone, patient_id, doctor_email, gender)
        VALUES ('$nextId', '$name', '$age', '$email', '$phone', '$patient_id', '$doctor_email', '$gender')";

if ($conn->query($sql)) {
    echo json_encode(["success" => true, "message" => "Patient added successfully", "patient_id" => $patient_id]);
} else {
    echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
}

$conn->close();
?>
