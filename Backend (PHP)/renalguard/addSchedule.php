<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Content-Type: application/json");

// 🔍 DEBUG: Log incoming request
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input);

// Log to file for debugging
$log_file = __DIR__ . '/debug_addSchedule.log';
$log_entry = date('Y-m-d H:i:s') . " | Raw Input: " . $raw_input . "\n";
$log_entry .= "Decoded Data: " . print_r($data, true) . "\n---\n";
file_put_contents($log_file, $log_entry, FILE_APPEND);

$conn = new mysqli("localhost", "root", "", "renalguard", 3306);

if ($conn->connect_error) {
    echo json_encode(["success"=>false,"message"=>"DB connection failed"]);
    exit();
}

$doctor_email   = $conn->real_escape_string($data->doctor_email ?? '');
$available_date = $conn->real_escape_string($data->available_date ?? '');
$start_time     = $conn->real_escape_string($data->start_time ?? '');
$end_time       = $conn->real_escape_string($data->end_time ?? '');
$slot_duration  = 15; // ✅ Fixed at 15 minutes

// Detailed field validation
$missing_fields = [];
if (empty($doctor_email)) $missing_fields[] = 'doctor_email';
if (empty($available_date)) $missing_fields[] = 'available_date';
if (empty($start_time)) $missing_fields[] = 'start_time';
if (empty($end_time)) $missing_fields[] = 'end_time';

if (!empty($missing_fields)) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields: " . implode(', ', $missing_fields),
        "received_data" => [
            "doctor_email" => $doctor_email ?: null,
            "available_date" => $available_date ?: null,
            "start_time" => $start_time ?: null,
            "end_time" => $end_time ?: null
        ]
    ]);
    exit();
}

// 🔹 Get doctor_id from email
$resDoc = $conn->query("SELECT doctor_id FROM doctors WHERE email='$doctor_email' LIMIT 1");
if (!$resDoc || $resDoc->num_rows == 0) {
    echo json_encode(["success"=>false,"message"=>"Doctor not found"]);
    exit();
}
$rowDoc = $resDoc->fetch_assoc();
$doctor_id = $rowDoc['doctor_id'];

// 🔹 Convert to 24-hour
function to24($time) {
    $t = strtotime($time);
    return $t ? date("H:i", $t) : null;
}
$start_24 = to24($start_time);
$end_24 = to24($end_time);
if (!$start_24 || !$end_24) {
    echo json_encode(["success"=>false,"message"=>"Invalid time format"]);
    exit();
}

// 🔹 Calculate max patients
$diff = (strtotime($end_24) - strtotime($start_24)) / 60; // in minutes
if ($diff <= 0) {
    echo json_encode(["success"=>false,"message"=>"End time must be after start time"]);
    exit();
}
$max_patients = floor($diff / $slot_duration);

// 🔹 Insert schedule
$sql = "INSERT INTO doctor_schedule 
        (doctor_id, available_date, start_time, end_time, slot_duration, max_patients, booked_count, status)
        VALUES ('$doctor_id','$available_date','$start_24','$end_24',$slot_duration,$max_patients,0,'active')";

if ($conn->query($sql)) {
    echo json_encode(["success"=>true,"message"=>"Schedule added successfully"]);
} else {
    echo json_encode(["success"=>false,"message"=>$conn->error]);
}

$conn->close();
?>
