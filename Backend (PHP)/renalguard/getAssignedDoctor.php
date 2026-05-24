<?php
error_reporting(0); // Suppress all warnings/notices to prevent JSON corruption
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

include 'db.php';

// Check connection from db.php (it defines $conn)
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit();
}

$patient_email = $_GET['email'] ?? $_POST['email'] ?? null;

if (!$patient_email) {
    $input = json_decode(file_get_contents("php://input"), true);
    $patient_email = $input['email'] ?? null;
}

if (!$patient_email) {
    echo json_encode(["success" => false, "message" => "Missing patient email"]);
    $conn->close();
    exit();
}

// SQL to fetch assigned doctor
$sql = "SELECT d.doctor_id, u.name, u.specialization, u.education, u.location, u.phone, u.email 
        FROM users u
        JOIN doctors d ON d.email = u.email
        JOIN patients p ON p.doctor_email = u.email
        WHERE p.email = ?
        LIMIT 1";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(["success" => false, "message" => "SQL Prepare Failed"]);
    $conn->close();
    exit();   
}

$stmt->bind_param("s", $patient_email);

if (!$stmt->execute()) {
    echo json_encode(["success" => false, "message" => "SQL Execute Failed"]);
    $stmt->close();
    $conn->close();
    exit();
}

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        "success" => true,
        "doctor" => [
            "doctor_id" => (string)$row['doctor_id'],
            "name" => (string)$row['name'],
            "specialization" => $row['specialization'],
            "education" => $row['education'],
            "location" => $row['location'],
            "phone" => (string)$row['phone'],
            "email" => (string)$row['email']
        ]
    ]);
} else {
    // Valid JSON response for no assignment case
    echo json_encode(["success" => false, "message" => "No doctor assigned"]);
}

$stmt->close();
$conn->close();
?>
