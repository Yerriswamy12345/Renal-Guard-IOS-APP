<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"));
$conn = new mysqli("localhost", "root", "", "renalguard", 3306);

$appointment_id = intval($data->appointment_id ?? 0);
$patient_email  = $conn->real_escape_string($data->patient_email ?? '');
$status         = $conn->real_escape_string($data->status ?? '');

// allowed statuses
$allowed = ['attended','missed','cancelled'];

if (!$appointment_id || !$patient_email || !in_array($status, $allowed)) {
    echo json_encode(["success"=>false,"message"=>"Invalid request"]);
    exit();
}

// check if appointment belongs to this patient (Case Insensitive Email)
$chk = $conn->query("SELECT * FROM appointments WHERE appointment_id=$appointment_id AND LOWER(patient_email)=LOWER('$patient_email')");
if (!$chk || $chk->num_rows == 0) {
    // Fallback: Try checking without email to see if ID exists, then check email loosely? 
    // Or just trust the ID if we want to be less strict? No, security.
    echo json_encode(["success"=>false,"message"=>"Appointment not found for this patient"]);
    exit();
}

// update
$sql = "UPDATE appointments SET status='$status' WHERE appointment_id=$appointment_id";
if ($conn->query($sql)) {
    echo json_encode(["success"=>true,"message"=>"Appointment updated successfully","new_status"=>$status]);
} else {
    echo json_encode(["success"=>false,"message"=>$conn->error]);
}

$conn->close();
?>
