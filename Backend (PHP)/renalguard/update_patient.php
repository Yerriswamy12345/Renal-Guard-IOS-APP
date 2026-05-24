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

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

$patient_id = $conn->real_escape_string($data['patient_id'] ?? '');
$name       = $conn->real_escape_string($data['name'] ?? '');
$age        = $conn->real_escape_string($data['age'] ?? '');
$gender     = $conn->real_escape_string($data['gender'] ?? '');
$email      = $conn->real_escape_string($data['email'] ?? '');
$mobile     = $conn->real_escape_string($data['phone'] ?? $data['mobile'] ?? ''); // handle both 'phone' (swift) and 'mobile' (php) keys if mismatch? Swift uses "mobile" in state var name but likely will send "mobile" or "phone"?
// NetworkManager parameters key matters. I'll use "phone" in Swift to match DB column usually, but PHP asked for "mobile".
// I'll support both in PHP or fix Swift to send what PHP expects.
// PHP original was $_POST['mobile']. I will keys to 'mobile' in PHP extraction.

// Validate required fields
if (!$patient_id || !$name || !$age || !$gender || !$email || !$mobile) {
    echo json_encode(["success" => false, "message" => "All fields are required"]);
    $conn->close();
    exit();
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format"]);
    $conn->close();
    exit();
}

// Update patient in database
$sql = "UPDATE patients 
        SET name='$name', age='$age', gender='$gender', email='$email', phone='$mobile'
        WHERE patient_id='$patient_id'";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => true, "message" => "Patient updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Update failed: " . $conn->error]);
}

$conn->close();
?>
